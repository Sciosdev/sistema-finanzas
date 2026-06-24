<?php

use App\Models\User;
use App\Services\Finance\FinanceBackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->originalDatabaseDefault = config('database.default');
    $this->originalMysqlConnection = config('database.connections.mysql');
    $this->originalExternalBackupPath = config('finance.external_backup_path');
    File::deleteDirectory(storage_path('app/private/finance-backups'));
    File::deleteDirectory(storage_path('app/private/testing-external-backups'));
});

afterEach(function () {
    config()->set('database.default', $this->originalDatabaseDefault);
    config()->set('database.connections.mysql', $this->originalMysqlConnection);
    config()->set('finance.external_backup_path', $this->originalExternalBackupPath);
    File::deleteDirectory(storage_path('app/private/finance-backups'));
    File::deleteDirectory(storage_path('app/private/testing-external-backups'));
    Carbon::setTestNow();
});

function configureMysqlBackupConnectionForTests(): void
{
    config()->set('database.default', 'mysql');
    config()->set('database.connections.mysql.driver', 'mysql');
    config()->set('database.connections.mysql.database', 'finanzas_test');
    config()->set('database.connections.mysql.username', 'root');
    config()->set('database.connections.mysql.password', 'secret');
    config()->set('database.connections.mysql.host', '127.0.0.1');
    config()->set('database.connections.mysql.port', '3306');
}

function zipEntriesForFinanceBackup(string $path): array
{
    $zip = new ZipArchive();
    $zip->open($path);

    $entries = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entries[] = $zip->getNameIndex($index);
    }

    $zip->close();

    return $entries;
}

function zipTextEntryForFinanceBackup(string $path, string $entry): string
{
    $zip = new ZipArchive();
    $zip->open($path);
    $contents = (string) $zip->getFromName($entry);
    $zip->close();

    return $contents;
}

function fakeDatabaseDumpFinanceBackupService(): FinanceBackupService
{
    return new class extends FinanceBackupService {
        protected function runMysqlDump(array $connection, string $path): void
        {
            File::put($path, "-- fake dump for {$connection['database']}\n");
        }
    };
}

function fakeFullFinanceBackupService(): FinanceBackupService
{
    return new class extends FinanceBackupService {
        public function createDatabaseBackup(): array
        {
            $directory = storage_path('app/private/finance-backups/database');
            File::ensureDirectoryExists($directory);

            $filename = 'fake-db.sql';
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            File::put($path, "-- fake dump\n");

            return [
                'ok' => true,
                'type' => 'database',
                'name' => $filename,
                'path' => 'finance-backups/database/' . $filename,
                'absolute_path' => $path,
                'size' => filesize($path),
                'created_at' => now(),
                'message' => 'Backup de BD creado.',
            ];
        }
    };
}

function fakeMigrationFinanceBackupService(): FinanceBackupService
{
    return new class extends FinanceBackupService {
        protected function createPortableDatabaseExport(string $path): array
        {
            File::put($path, "-- portable dump\nCREATE TABLE `users` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`));\n");

            return [
                'driver' => 'mysql',
                'database' => 'finanzas_test',
                'tables' => 1,
                'rows' => 0,
            ];
        }
    };
}

it('creates a database backup sql file in private storage', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');
    configureMysqlBackupConnectionForTests();

    $result = fakeDatabaseDumpFinanceBackupService()->createDatabaseBackup();

    expect($result['ok'])->toBeTrue();
    expect($result['type'])->toBe('database');
    expect($result['name'])->toEndWith('.sql');
    expect($result['path'])->toBe('finance-backups/database/' . $result['name']);
    expect(File::exists($result['absolute_path']))->toBeTrue();
    expect(File::get($result['absolute_path']))->toContain('finanzas_test');
});

it('creates a full backup zip without env by default and excludes bulky folders', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $result = fakeFullFinanceBackupService()->createFullBackup(false);

    expect($result['ok'])->toBeTrue();
    expect($result['type'])->toBe('full');
    expect($result['name'])->toEndWith('.zip');
    expect(File::exists($result['absolute_path']))->toBeTrue();

    $entries = collect(zipEntriesForFinanceBackup($result['absolute_path']));

    expect($entries)->toContain('database-backup/fake-db.sql');
    expect($entries)->toContain('composer.json');
    expect($entries)->toContain('artisan');
    expect($entries->contains('.env'))->toBeFalse();
    expect($entries->first(fn (string $entry) => str_starts_with($entry, 'vendor/')))->toBeNull();
    expect($entries->first(fn (string $entry) => str_starts_with($entry, '.git/')))->toBeNull();
    expect($entries->first(fn (string $entry) => str_starts_with($entry, 'node_modules/')))->toBeNull();
    expect($entries->first(fn (string $entry) => str_starts_with($entry, 'storage/logs/')))->toBeNull();
    expect($entries->first(fn (string $entry) => str_starts_with($entry, 'storage/app/private/finance-backups/')))->toBeNull();
});

it('creates a full backup zip with env when requested', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $result = fakeFullFinanceBackupService()->createFullBackup(true);
    $entries = collect(zipEntriesForFinanceBackup($result['absolute_path']));

    expect($result['ok'])->toBeTrue();
    expect($entries)->toContain('.env');
});

it('creates a migration package zip with portable sql and restore metadata', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $result = fakeMigrationFinanceBackupService()->createMigrationPackage();

    expect($result['ok'])->toBeTrue();
    expect($result['type'])->toBe('migration');
    expect($result['name'])->toEndWith('.zip');
    expect(File::exists($result['absolute_path']))->toBeTrue();

    $entries = collect(zipEntriesForFinanceBackup($result['absolute_path']));

    expect($entries)->toContain('database/sistema-finanzas.sql');
    expect($entries)->toContain('manifest.json');
    expect($entries)->toContain('RESTORE_LOCAL.md');
    expect(zipTextEntryForFinanceBackup($result['absolute_path'], 'database/sistema-finanzas.sql'))->toContain('CREATE TABLE `users`');
    expect(zipTextEntryForFinanceBackup($result['absolute_path'], 'manifest.json'))->toContain('sistema-finanzas-migration');
    expect(zipTextEntryForFinanceBackup($result['absolute_path'], 'manifest.json'))->not->toContain('secret');
});

it('stores a generated database backup flash with a protected download link', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    app()->instance(FinanceBackupService::class, fakeDatabaseDumpFinanceBackupService());
    $user = User::factory()->create();
    configureMysqlBackupConnectionForTests();

    $this->actingAs($user)
        ->post(route('finance.security.backups.database'))
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionHas('backup_download', fn (array $download) => $download['type'] === 'database'
            && str_ends_with($download['name'], '.sql'));
});

it('stores a generated migration package flash with a protected download link', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    app()->instance(FinanceBackupService::class, fakeMigrationFinanceBackupService());
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('finance.security.backups.migration'))
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionHas('backup_download', fn (array $download) => $download['type'] === 'migration'
            && str_ends_with($download['name'], '.zip'));
});

it('downloads an existing private backup file from a protected route', function () {
    $user = User::factory()->create();
    $directory = storage_path('app/private/finance-backups/database');
    File::ensureDirectoryExists($directory);
    File::put($directory . DIRECTORY_SEPARATOR . 'manual.sql', '-- manual backup');

    $this->actingAs($user)
        ->get(route('finance.security.backups.download', ['type' => 'database', 'filename' => 'manual.sql']))
        ->assertOk()
        ->assertHeader('content-disposition');
});

it('rejects manipulated backup download paths', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/finanzas/seguridad/backups/database/%2E%2E%2F.env')
        ->assertNotFound();
});

it('requires authentication to generate and download backups', function () {
    $this->post(route('finance.security.backups.database'))->assertRedirect(route('login'));
    $this->post(route('finance.security.backups.full'))->assertRedirect(route('login'));
    $this->post(route('finance.security.backups.migration'))->assertRedirect(route('login'));
    $this->get(route('finance.security.backups.download', ['type' => 'database', 'filename' => 'manual.sql']))
        ->assertRedirect(route('login'));
});

it('returns a clear error when mysqldump is missing', function () {
    configureMysqlBackupConnectionForTests();

    $result = (new FinanceBackupService('Z:\\missing\\mysqldump.exe'))->createDatabaseBackup();

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('mysqldump');
});

it('copies the latest local backup to the configured external path', function () {
    $localDirectory = storage_path('app/private/finance-backups/database');
    $externalDirectory = storage_path('app/private/testing-external-backups');
    File::ensureDirectoryExists($localDirectory);
    File::ensureDirectoryExists($externalDirectory);
    File::put($localDirectory . DIRECTORY_SEPARATOR . 'manual.sql', '-- manual backup');
    config()->set('finance.external_backup_path', $externalDirectory);

    $result = app(FinanceBackupService::class)->copyLatestBackupToExternal();

    expect($result['ok'])->toBeTrue();
    expect($result['name'])->toBe('manual.sql');
    expect(File::exists($externalDirectory . DIRECTORY_SEPARATOR . 'finance-backups' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'manual.sql'))->toBeTrue();
});

it('returns a clear error when external backup path is not configured', function () {
    $localDirectory = storage_path('app/private/finance-backups/database');
    File::ensureDirectoryExists($localDirectory);
    File::put($localDirectory . DIRECTORY_SEPARATOR . 'manual.sql', '-- manual backup');
    config()->set('finance.external_backup_path', null);

    $result = app(FinanceBackupService::class)->copyLatestBackupToExternal();

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('FINANCE_EXTERNAL_BACKUP_PATH');
});
