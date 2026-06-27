<?php

use App\Models\User;
use App\Services\Finance\FinanceBuildDeployService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
    File::deleteDirectory(buildTestRoot());
    File::deleteDirectory(storage_path('app/private/build-deploys-tmp'));
});

function buildTestRoot(): string
{
    return storage_path('app/testing/build-deploy');
}

function buildOwner(): User
{
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    return $owner;
}

/**
 * Servicio con rutas redirigidas a un sandbox temporal para no tocar el
 * public/build real ni el storage de producción durante las pruebas.
 */
function bindBuildService(): FinanceBuildDeployService
{
    $service = new class extends FinanceBuildDeployService {
        protected function buildPath(): string
        {
            return buildTestRoot() . '/public-build';
        }

        protected function baseDir(): string
        {
            return buildTestRoot() . '/store';
        }
    };

    app()->instance(FinanceBuildDeployService::class, $service);

    return $service;
}

/**
 * @param array<string, string> $files
 */
function makeBuildZip(array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'bz') . '.zip';

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();

    return $path;
}

function uploadZip(string $zipPath, string $clientName = 'build.zip'): UploadedFile
{
    return new UploadedFile($zipPath, $clientName, 'application/zip', null, true);
}

function testBuildPath(string $relative = ''): string
{
    return buildTestRoot() . '/public-build' . ($relative !== '' ? '/' . $relative : '');
}

it('shows the build deploy section to the finance owner', function () {
    $owner = buildOwner();

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Actualizar assets / public build')
        ->assertSee('Subir y montar build');
});

it('does not show the build deploy section to a normal user', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.security.index'))
        ->assertForbidden();
});

it('forbids a normal user from uploading a build', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $zip = makeBuildZip(['manifest.json' => '{"a":1}']);

    $this->actingAs($user)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertForbidden();

    @unlink($zip);
});

it('rejects a file that is not a zip', function () {
    $owner = buildOwner();
    bindBuildService();

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => UploadedFile::fake()->create('build.txt', 10),
            'confirm_build' => '1',
        ])
        ->assertSessionHas('error');

    expect(is_dir(testBuildPath()))->toBeFalse();
});

it('requires the confirmation checkbox', function () {
    $owner = buildOwner();
    bindBuildService();

    $zip = makeBuildZip(['manifest.json' => '{"a":1}']);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
        ])
        ->assertSessionHasErrors('confirm_build');

    @unlink($zip);
});

it('rejects a zip without manifest.json', function () {
    $owner = buildOwner();
    bindBuildService();

    $zip = makeBuildZip(['assets/app.js' => 'console.log(1)']);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertSessionHas('error');

    expect(is_dir(testBuildPath()))->toBeFalse();

    @unlink($zip);
});

it('rejects a zip with path traversal', function () {
    $owner = buildOwner();
    bindBuildService();

    $zip = makeBuildZip([
        'manifest.json' => '{"a":1}',
        'assets/../../evil.js' => 'x',
    ]);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertSessionHas('error');

    expect(is_dir(testBuildPath()))->toBeFalse();

    @unlink($zip);
});

it('rejects a zip containing dangerous files', function () {
    $owner = buildOwner();
    bindBuildService();

    foreach ([
        ['manifest.json' => '{"a":1}', 'shell.php' => '<?php echo 1;'],
        ['manifest.json' => '{"a":1}', '.env' => 'APP_KEY=secret'],
        ['manifest.json' => '{"a":1}', '.htaccess' => 'Deny from all'],
    ] as $files) {
        $zip = makeBuildZip($files);

        $this->actingAs($owner)
            ->post(route('finance.build.upload'), [
                'build' => uploadZip($zip),
                'confirm_build' => '1',
            ])
            ->assertSessionHas('error');

        expect(is_dir(testBuildPath()))->toBeFalse();

        @unlink($zip);
    }
});

it('accepts a zip with manifest.json at the root and mounts it', function () {
    $owner = buildOwner();
    bindBuildService();

    $zip = makeBuildZip([
        'manifest.json' => '{"root":1}',
        'assets/app.js' => 'console.log("hi")',
    ]);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');

    expect(is_file(testBuildPath('manifest.json')))->toBeTrue()
        ->and(File::get(testBuildPath('manifest.json')))->toBe('{"root":1}')
        ->and(is_file(testBuildPath('assets/app.js')))->toBeTrue();

    @unlink($zip);
});

it('accepts a zip nested under build/ and mounts it as public/build/manifest.json', function () {
    $owner = buildOwner();
    bindBuildService();

    $zip = makeBuildZip([
        'build/manifest.json' => '{"nested":1}',
        'build/assets/app.js' => 'console.log("hi")',
    ]);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');

    expect(is_file(testBuildPath('manifest.json')))->toBeTrue()
        ->and(File::get(testBuildPath('manifest.json')))->toBe('{"nested":1}')
        ->and(is_file(testBuildPath('assets/app.js')))->toBeTrue()
        ->and(is_file(testBuildPath('build/manifest.json')))->toBeFalse();

    @unlink($zip);
});

it('backs up the previous build before mounting the new one', function () {
    $owner = buildOwner();
    bindBuildService();

    File::ensureDirectoryExists(testBuildPath());
    File::put(testBuildPath('manifest.json'), 'OLD');

    $zip = makeBuildZip(['manifest.json' => 'NEW']);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertSessionHas('success');

    expect(File::get(testBuildPath('manifest.json')))->toBe('NEW');

    $backupsDir = buildTestRoot() . '/store/backups';
    $backups = File::directories($backupsDir);
    expect($backups)->toHaveCount(1)
        ->and(File::get($backups[0] . '/manifest.json'))->toBe('OLD');

    @unlink($zip);
});

it('runs optimize:clear after mounting the build', function () {
    $owner = buildOwner();
    bindBuildService();

    $zip = makeBuildZip(['manifest.json' => '{"a":1}']);

    $this->actingAs($owner)
        ->post(route('finance.build.upload'), [
            'build' => uploadZip($zip),
            'confirm_build' => '1',
        ])
        ->assertSessionHas('maintenance_result', fn (array $result) => str_contains($result['action'], 'optimize:clear'));

    @unlink($zip);
});

it('rolls back to the previous build', function () {
    buildOwner();
    $service = bindBuildService();

    $zipV1 = makeBuildZip(['manifest.json' => 'V1']);
    expect($service->deployFromZip($zipV1)['ok'])->toBeTrue();
    expect(File::get(testBuildPath('manifest.json')))->toBe('V1');

    $zipV2 = makeBuildZip(['manifest.json' => 'V2']);
    expect($service->deployFromZip($zipV2)['ok'])->toBeTrue();
    expect(File::get(testBuildPath('manifest.json')))->toBe('V2');

    $result = $service->rollback();

    expect($result['ok'])->toBeTrue()
        ->and(File::get(testBuildPath('manifest.json')))->toBe('V1');

    @unlink($zipV1);
    @unlink($zipV2);
});

it('cleans up old build backups keeping the most recent', function () {
    buildOwner();
    $service = bindBuildService();

    $backupsDir = buildTestRoot() . '/store/backups';
    foreach (['build-1', 'build-2', 'build-3'] as $index => $name) {
        File::ensureDirectoryExists($backupsDir . '/' . $name);
        File::put($backupsDir . '/' . $name . '/manifest.json', $name);
        // mtimes escalonados para un orden determinista (más reciente = build-3).
        touch($backupsDir . '/' . $name, time() + $index);
    }

    $result = $service->cleanupBackups(1);

    expect($result['ok'])->toBeTrue()
        ->and($result['deleted'])->toBe(2);

    $remaining = File::directories($backupsDir);
    expect($remaining)->toHaveCount(1)
        ->and(basename($remaining[0]))->toBe('build-3');
});

it('does not touch the database when deploying a build', function () {
    buildOwner();
    $service = bindBuildService();

    $usersBefore = User::count();

    $zip = makeBuildZip(['manifest.json' => '{"a":1}']);
    expect($service->deployFromZip($zip)['ok'])->toBeTrue();

    expect(User::count())->toBe($usersBefore);

    @unlink($zip);
});

it('never uses shell execution or process helpers in the build deploy code', function () {
    // Sin funciones de sistema ni Process => imposible ejecutar npm/shell/compilar.
    foreach ([
        base_path('app/Services/Finance/FinanceBuildDeployService.php'),
        base_path('app/Http/Controllers/Finance/FinanceBuildDeployController.php'),
    ] as $file) {
        $contents = file_get_contents($file);
        foreach (['shell_exec', 'exec(', 'passthru', 'proc_open', 'system(', 'popen(', 'Symfony\\Component\\Process', 'Process::'] as $forbidden) {
            expect(str_contains($contents, $forbidden))->toBeFalse("'{$forbidden}' no debe aparecer en {$file}");
        }
    }
});

it('only ever calls the optimize:clear artisan command', function () {
    // Único Artisan::call permitido: optimize:clear. Nada de migrate/db:wipe/etc.
    $contents = file_get_contents(base_path('app/Services/Finance/FinanceBuildDeployService.php'));

    preg_match_all("/Artisan::call\\(\\s*'([^']+)'/", $contents, $matches);

    expect($matches[1])->not->toBeEmpty()
        ->and(array_unique($matches[1]))->toBe(['optimize:clear']);
});
