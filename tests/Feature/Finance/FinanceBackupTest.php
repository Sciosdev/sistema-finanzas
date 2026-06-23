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
    File::deleteDirectory(storage_path('app/private/finance-backups'));
});

afterEach(function () {
    config()->set('database.default', $this->originalDatabaseDefault);
    config()->set('database.connections.mysql', $this->originalMysqlConnection);
    File::deleteDirectory(storage_path('app/private/finance-backups'));
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
    $this->get(route('finance.security.backups.download', ['type' => 'database', 'filename' => 'manual.sql']))
        ->assertRedirect(route('login'));
});

it('returns a clear error when mysqldump is missing', function () {
    configureMysqlBackupConnectionForTests();

    $result = (new FinanceBackupService('Z:\\missing\\mysqldump.exe'))->createDatabaseBackup();

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('mysqldump');
});
