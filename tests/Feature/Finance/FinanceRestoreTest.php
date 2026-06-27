<?php

use App\Models\User;
use App\Services\Finance\FinanceBackupService;
use App\Services\Finance\FinanceRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
    File::deleteDirectory(storage_path('app/private/finance-backups'));
    File::deleteDirectory(storage_path('app/finance-restore-tmp'));
});

function restoreOwner(): User
{
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    return $owner;
}

function makeRealBackupZip(string $type, string $name, ?string $sqlContent = "-- dump\nCREATE TABLE demo (id int);"): string
{
    $dir = storage_path('app/private/finance-backups/' . $type);
    File::ensureDirectoryExists($dir);
    $path = $dir . DIRECTORY_SEPARATOR . $name;

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($sqlContent !== null) {
        $zip->addFromString('database/sistema-finanzas.sql', $sqlContent);
    }
    $zip->addFromString('manifest.json', '{"type":"sistema-finanzas-migration"}');
    $zip->close();

    return $path;
}

function bindRestoreFakes(bool $backupOk = true, bool $restoreOk = true): void
{
    app()->instance(FinanceBackupService::class, new class($backupOk) extends FinanceBackupService {
        public function __construct(private bool $backupOk)
        {
        }

        public function createMigrationPackage(): array
        {
            return $this->backupOk
                ? ['ok' => true, 'type' => 'migration', 'name' => 'auto-pre-restore.zip', 'absolute_path' => '/tmp/x', 'size' => 10, 'created_at' => now(), 'message' => 'ok']
                : ['ok' => false, 'message' => 'backup falló'];
        }
    });

    app()->instance(FinanceRestoreService::class, new class($restoreOk) extends FinanceRestoreService {
        public function __construct(private bool $restoreOk)
        {
        }

        public function restoreFromZip(string $zipPath): array
        {
            return $this->restoreOk
                ? ['ok' => true, 'message' => 'restaurado']
                : ['ok' => false, 'message' => 'falló restore'];
        }
    });
}

it('shows the restore section to the finance owner', function () {
    $owner = restoreOwner();

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Restaurar (reemplaza TODA la base)')
        ->assertSee('Escribe RESTAURAR');
});

it('shows a per-row restore button for each saved backup', function () {
    $owner = restoreOwner();
    makeRealBackupZip('migration', 'finanzas-migration-row.zip');

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('financeConfirmRestore', false)
        ->assertSee('migration::finanzas-migration-row.zip', false);
});

it('forbids a normal user from restoring', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->post(route('finance.security.restore.backup'), ['backup' => 'migration::x.zip', 'confirm_phrase' => 'RESTAURAR'])
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('finance.security.restore.upload'), ['confirm_phrase' => 'RESTAURAR'])
        ->assertForbidden();
});

it('requires the exact confirmation phrase to restore', function () {
    $owner = restoreOwner();

    $this->actingAs($owner)
        ->post(route('finance.security.restore.backup'), ['backup' => 'migration::x.zip', 'confirm_phrase' => 'restaurar'])
        ->assertSessionHasErrors('confirm_phrase');

    $this->actingAs($owner)
        ->post(route('finance.security.restore.backup'), ['backup' => 'migration::x.zip'])
        ->assertSessionHasErrors('confirm_phrase');
});

it('restores a saved backup after creating an automatic backup', function () {
    $owner = restoreOwner();
    bindRestoreFakes(backupOk: true, restoreOk: true);
    makeRealBackupZip('migration', 'finanzas-migration-test.zip');

    $this->actingAs($owner)
        ->post(route('finance.security.restore.backup'), [
            'backup' => 'migration::finanzas-migration-test.zip',
            'confirm_phrase' => 'RESTAURAR',
        ])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success')
        ->assertSessionHas('backup_download', fn (array $download) => $download['type'] === 'migration'
            && $download['name'] === 'auto-pre-restore.zip');
});

it('does not restore when the automatic pre-backup fails', function () {
    $owner = restoreOwner();
    bindRestoreFakes(backupOk: false, restoreOk: true);
    makeRealBackupZip('migration', 'finanzas-migration-test.zip');

    $this->actingAs($owner)
        ->post(route('finance.security.restore.backup'), [
            'backup' => 'migration::finanzas-migration-test.zip',
            'confirm_phrase' => 'RESTAURAR',
        ])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('error')
        ->assertSessionMissing('success');
});

it('restores from an uploaded zip package', function () {
    $owner = restoreOwner();
    bindRestoreFakes(backupOk: true, restoreOk: true);

    $this->actingAs($owner)
        ->post(route('finance.security.restore.upload'), [
            'package' => UploadedFile::fake()->create('backup.zip', 5),
            'confirm_phrase' => 'RESTAURAR',
        ])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');
});

it('extracts the sql from a real package and rejects packages without sql', function () {
    $service = new class extends FinanceRestoreService {
        public ?string $captured = null;

        protected function importSql(string $sql): void
        {
            $this->captured = $sql;
        }
    };

    $withSql = makeRealBackupZip('migration', 'with-sql.zip', "-- dump\nCREATE TABLE finanzas_demo (id int);");
    $result = $service->restoreFromZip($withSql);

    expect($result['ok'])->toBeTrue()
        ->and($service->captured)->toContain('CREATE TABLE finanzas_demo');

    $withoutSql = makeRealBackupZip('migration', 'no-sql.zip', null);
    $bad = $service->restoreFromZip($withoutSql);

    expect($bad['ok'])->toBeFalse()
        ->and($bad['message'])->toContain('.sql');
});

it('never uses system functions in the restore code', function () {
    foreach ([
        base_path('app/Services/Finance/FinanceRestoreService.php'),
        base_path('app/Http/Controllers/Finance/FinanceRestoreController.php'),
    ] as $file) {
        $contents = file_get_contents($file);
        foreach (['shell_exec', 'exec(', 'passthru', 'proc_open', 'system('] as $forbidden) {
            expect(str_contains($contents, $forbidden))->toBeFalse("'{$forbidden}' no debe aparecer en {$file}");
        }
    }
});
