<?php

use App\Services\Finance\FinanceReleasePackager;
use Illuminate\Support\Facades\File;

/**
 * Crea un proyecto falso mínimo en un directorio temporal para no comprimir el
 * vendor/ real (que es enorme y lento) y poder validar incluidos/excluidos.
 */
function makeFakeProject(array $files): string
{
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'release-test-' . uniqid();
    File::ensureDirectoryExists($base);

    foreach ($files as $relative => $contents) {
        $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    return $base;
}

function fullValidProject(): array
{
    return [
        'vendor/autoload.php' => '<?php // autoload',
        'public/build/manifest.json' => '{}',
        'public/index.php' => '<?php // front controller',
        '.env.example' => 'APP_ENV=local',
        '.env' => 'APP_KEY=base64:SECRETVALUE',
        'composer.json' => '{}',
        'composer.lock' => '{}',
        'artisan' => '#!/usr/bin/env php',
        'app/Models/User.php' => '<?php // model',
        'routes/web.php' => '<?php // routes',
        'config/finance.php' => '<?php return [];',
        'database/migrations/0001_init.php' => '<?php // migration',
        'database/database.sqlite' => 'SQLITEDATA',
        'bootstrap/app.php' => '<?php // bootstrap',
        'bootstrap/cache/services.php' => '<?php // compiled cache',
        'bootstrap/cache/.gitignore' => '*',
        'node_modules/pkg/index.js' => 'module.exports = {}',
        'storage/logs/laravel.log' => 'log line',
        'storage/framework/sessions/sess_1' => 'session',
        '.git/config' => '[core]',
        'storage/app/private/finance-backups/full/x.zip' => 'backup',
    ];
}

afterEach(function () {
    foreach (File::glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'release-test-*') as $dir) {
        File::deleteDirectory($dir);
    }
    foreach (File::glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'release-out-*') as $dir) {
        File::deleteDirectory($dir);
    }
});

function buildReleaseInto(string $base): array
{
    $out = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'release-out-' . uniqid();

    return [(new FinanceReleasePackager($base))->build($out), $out];
}

function zipEntries(string $zipPath): array
{
    $zip = new ZipArchive();
    expect($zip->open($zipPath))->toBeTrue();

    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }
    $zip->close();

    return $entries;
}

it('validates that vendor and build are present', function () {
    $missingVendor = makeFakeProject([
        'public/build/manifest.json' => '{}',
        '.env.example' => 'x',
        'composer.lock' => '{}',
    ]);

    $problems = (new FinanceReleasePackager($missingVendor))->validate();

    expect($problems)->not->toBeEmpty()
        ->and(implode(' ', $problems))->toContain('vendor/autoload.php');
});

it('fails to build when prerequisites are missing', function () {
    $base = makeFakeProject(['composer.json' => '{}']);

    [$result] = buildReleaseInto($base);

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('No se puede generar el paquete');
});

it('generates a release zip with the expected name', function () {
    $base = makeFakeProject(fullValidProject());

    [$result] = buildReleaseInto($base);

    expect($result['ok'])->toBeTrue()
        ->and($result['name'])->toStartWith('release-finanzas-hostgator-')
        ->and($result['name'])->toEndWith('.zip')
        ->and(is_file($result['path']))->toBeTrue();
});

it('includes the production artifacts', function () {
    $base = makeFakeProject(fullValidProject());

    [$result] = buildReleaseInto($base);
    $entries = zipEntries($result['path']);

    expect($entries)
        ->toContain('vendor/autoload.php')
        ->toContain('public/build/manifest.json')
        ->toContain('.env.example')
        ->toContain('composer.json')
        ->toContain('composer.lock')
        ->toContain('artisan')
        ->toContain('app/Models/User.php')
        ->toContain('routes/web.php')
        ->toContain('DEPLOY_HOSTGATOR.md');
});

it('excludes secrets and runtime files', function () {
    $base = makeFakeProject(fullValidProject());

    [$result] = buildReleaseInto($base);
    $entries = zipEntries($result['path']);

    expect($entries)
        ->not->toContain('.env')
        ->not->toContain('node_modules/pkg/index.js')
        ->not->toContain('storage/logs/laravel.log')
        ->not->toContain('storage/framework/sessions/sess_1')
        ->not->toContain('.git/config')
        ->not->toContain('bootstrap/cache/services.php')
        ->not->toContain('database/database.sqlite')
        ->not->toContain('storage/app/private/finance-backups/full/x.zip');
});

it('does not leak the env secret value into the zip', function () {
    $base = makeFakeProject(fullValidProject());

    [$result] = buildReleaseInto($base);

    $contents = (string) file_get_contents($result['path']);

    expect($contents)->not->toContain('SECRETVALUE');
});
