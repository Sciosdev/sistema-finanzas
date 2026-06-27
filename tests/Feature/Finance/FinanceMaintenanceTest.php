<?php

use App\Models\User;
use App\Services\Finance\FinanceBackupService;
use App\Services\Finance\FinanceMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('flags destructive migration content and ignores safe content', function () {
    $service = app(FinanceMaintenanceService::class);

    expect($service->isDestructiveMigrationContent('Schema::dropIfExists("finance_movements");'))->toBeTrue()
        ->and($service->isDestructiveMigrationContent('$table->dropColumn("color");'))->toBeTrue()
        ->and($service->isDestructiveMigrationContent('DB::statement("TRUNCATE TABLE x");'))->toBeTrue()
        ->and($service->isDestructiveMigrationContent('$table->string("name");'))->toBeFalse()
        ->and($service->isDestructiveMigrationContent('$table->decimal("amount", 12, 2)->nullable();'))->toBeFalse();
});

it('reports zero pending migrations when the database is up to date', function () {
    $status = app(FinanceMaintenanceService::class)->status();

    expect($status['pending_count'])->toBe(0)
        ->and($status['has_pending'])->toBeFalse()
        ->and($status['has_destructive_pending'])->toBeFalse()
        ->and($status['pending'])->toBe([]);
});

afterEach(function () {
    config()->set('finance.owner_email', null);
});

function maintenanceOwner(): User
{
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    return $owner;
}

function bindOkBackupService(): void
{
    app()->instance(FinanceBackupService::class, new class extends FinanceBackupService {
        public function createMigrationPackage(): array
        {
            return [
                'ok' => true,
                'type' => 'migration',
                'name' => 'auto-backup-test.zip',
                'absolute_path' => '/tmp/auto-backup-test.zip',
                'size' => 1234,
                'created_at' => now(),
                'message' => 'Backup automático listo.',
            ];
        }
    });
}

function bindFailingBackupService(): void
{
    app()->instance(FinanceBackupService::class, new class extends FinanceBackupService {
        public function createMigrationPackage(): array
        {
            return ['ok' => false, 'message' => 'No se pudo crear el backup.'];
        }
    });
}

it('shows the maintenance section to the finance owner', function () {
    $owner = maintenanceOwner();

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Mantenimiento')
        ->assertSee('Estado de migraciones')
        ->assertSee('Backup automático antes de migrar')
        ->assertSee('Confirmo: crear backup automático y ejecutar las migraciones pendientes');
});

it('hides maintenance from a normal user by forbidding the security screen', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.security.index'))
        ->assertForbidden();
});

it('forbids a normal user from running migrations', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->post(route('finance.maintenance.run-migrations'), ['confirm_backup' => '1'])
        ->assertForbidden();
});

it('requires the backup confirmation to run migrations', function () {
    $owner = maintenanceOwner();

    Artisan::shouldReceive('call')->never();

    $this->actingAs($owner)
        ->post(route('finance.maintenance.run-migrations'), [])
        ->assertSessionHasErrors('confirm_backup');
});

it('runs only migrate --force when confirmed', function () {
    $owner = maintenanceOwner();
    bindOkBackupService();

    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('Nothing to migrate.');

    $this->actingAs($owner)
        ->post(route('finance.maintenance.run-migrations'), ['confirm_backup' => '1'])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');
});

it('ignores any arbitrary command sent in the request and still runs only migrate --force', function () {
    $owner = maintenanceOwner();
    bindOkBackupService();

    // Aunque manden un "command" malicioso, el sistema lo ignora por completo.
    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('Nothing to migrate.');

    $this->actingAs($owner)
        ->post(route('finance.maintenance.run-migrations'), [
            'confirm_backup' => '1',
            'command' => 'migrate:fresh',
        ])
        ->assertRedirect(route('finance.security.index'));
});

it('creates an automatic backup before migrating and offers it for download', function () {
    $owner = maintenanceOwner();
    bindOkBackupService();

    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('Nothing to migrate.');

    $this->actingAs($owner)
        ->post(route('finance.maintenance.run-migrations'), ['confirm_backup' => '1'])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success')
        ->assertSessionHas('backup_download', fn (array $download) => $download['type'] === 'migration'
            && $download['name'] === 'auto-backup-test.zip');
});

it('does not run migrations if the automatic backup fails', function () {
    $owner = maintenanceOwner();
    bindFailingBackupService();

    Artisan::shouldReceive('call')->never();

    $this->actingAs($owner)
        ->post(route('finance.maintenance.run-migrations'), ['confirm_backup' => '1'])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('error');
});

it('runs only optimize:clear when clearing cache', function () {
    $owner = maintenanceOwner();

    Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('Cleared.');

    $this->actingAs($owner)
        ->post(route('finance.maintenance.clear-cache'), ['confirm_clear' => '1'])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');
});

it('requires confirmation to clear cache', function () {
    $owner = maintenanceOwner();

    Artisan::shouldReceive('call')->never();

    $this->actingAs($owner)
        ->post(route('finance.maintenance.clear-cache'), [])
        ->assertSessionHasErrors('confirm_clear');
});

it('never references dangerous commands anywhere in the maintenance code', function () {
    $files = [
        base_path('app/Services/Finance/FinanceMaintenanceService.php'),
        base_path('app/Http/Controllers/Finance/FinanceMaintenanceController.php'),
        base_path('routes/web.php'),
        base_path('resources/views/finance/security/index.blade.php'),
    ];

    foreach (['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe', 'schema:dump', 'shell_exec', 'exec(', 'passthru', 'proc_open', 'system('] as $forbidden) {
        foreach ($files as $file) {
            expect(str_contains(file_get_contents($file), $forbidden))->toBeFalse("'{$forbidden}' no debe aparecer en {$file}");
        }
    }
});
