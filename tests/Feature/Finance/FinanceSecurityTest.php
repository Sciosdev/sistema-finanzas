<?php

use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\SystemFailure;
use App\Models\User;
use App\Services\Finance\FinanceBackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

afterEach(function () {
    File::deleteDirectory(storage_path('app/private/finance-backups'));
    File::deleteDirectory(storage_path('app/private/finance-exports'));
    Carbon::setTestNow();
});

function failingFinanceBackupServiceForSecurity(string $message): FinanceBackupService
{
    return new class($message) extends FinanceBackupService {
        public function __construct(private readonly string $failureMessage)
        {
            parent::__construct();
        }

        public function createDatabaseBackup(): array
        {
            return [
                'ok' => false,
                'message' => $this->failureMessage,
            ];
        }

        public function createFullBackup(bool $includeEnv = false): array
        {
            return [
                'ok' => false,
                'message' => $this->failureMessage,
            ];
        }

        public function listBackups(): array
        {
            return [
                'database' => [],
                'full' => [],
            ];
        }
    };
}

function createSecuritySnapshot(User $user, string $entityType, string $expiresAt, ?string $restoredAt = null): DeleteSnapshot
{
    return DeleteSnapshot::create([
        'user_id' => $user->id,
        'token' => str()->random(40),
        'entity_type' => $entityType,
        'table_name' => 'finance_movements',
        'entity_id' => random_int(100, 999),
        'payload' => ['id' => random_int(100, 999), 'user_id' => $user->id],
        'relations_payload' => null,
        'expires_at' => $expiresAt,
        'restored_at' => $restoredAt,
    ]);
}

it('shows the security screen with backups exports snapshots and failures', function () {
    Carbon::setTestNow('2026-06-22 12:00:00');
    $user = User::factory()->create();

    $databaseDirectory = storage_path('app/private/finance-backups/database');
    $fullDirectory = storage_path('app/private/finance-backups/full');
    $exportDirectory = storage_path('app/private/finance-exports');
    File::ensureDirectoryExists($databaseDirectory);
    File::ensureDirectoryExists($fullDirectory);
    File::ensureDirectoryExists($exportDirectory);
    File::put($databaseDirectory . DIRECTORY_SEPARATOR . 'manual.sql', '-- backup');
    File::put($fullDirectory . DIRECTORY_SEPARATOR . 'manual.zip', 'zip');
    File::put($exportDirectory . DIRECTORY_SEPARATOR . 'movimientos.xlsx', 'excel');

    createSecuritySnapshot($user, 'movement', '2026-06-22 12:01:00');
    createSecuritySnapshot($user, 'planned_payment', '2026-06-22 11:59:00', '2026-06-22 12:00:00');
    createSecuritySnapshot($user, 'category', '2026-06-22 11:59:00');

    SystemFailure::create([
        'user_id' => $user->id,
        'module' => 'backup',
        'action' => 'database',
        'message' => 'mysqldump no esta disponible',
        'status' => 'open',
        'context' => ['backup_type' => 'database'],
        'occurred_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Seguridad')
        ->assertSee('manual.sql')
        ->assertSee('manual.zip')
        ->assertSee('movimientos.xlsx')
        ->assertSee('Disponible')
        ->assertSee('Restaurado')
        ->assertSee('Expirado')
        ->assertSee('mysqldump no esta disponible');
});

it('redirects guests away from the security screen', function () {
    $this->get(route('finance.security.index'))->assertRedirect(route('login'));
});

it('records database backup failures', function () {
    $user = User::factory()->create();
    app()->instance(FinanceBackupService::class, failingFinanceBackupServiceForSecurity('No se pudo crear el backup de BD: mysqldump no esta disponible.'));

    $this->actingAs($user)
        ->post(route('finance.security.backups.database'))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('finance_system_failures', [
        'user_id' => $user->id,
        'module' => 'backup',
        'action' => 'database',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('finance.security.index'))
        ->assertSee('mysqldump no esta disponible');
});

it('records full backup failures', function () {
    $user = User::factory()->create();
    app()->instance(FinanceBackupService::class, failingFinanceBackupServiceForSecurity('No se pudo crear el backup completo: zip fallo.'));

    $this->actingAs($user)
        ->post(route('finance.security.backups.full'))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('finance_system_failures', [
        'user_id' => $user->id,
        'module' => 'backup',
        'action' => 'full',
        'status' => 'open',
    ]);
});

it('records invalid backup downloads as failures', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('finance.security.backups.download', ['type' => 'database', 'filename' => 'missing.sql']))
        ->assertNotFound();

    $this->assertDatabaseHas('finance_system_failures', [
        'user_id' => $user->id,
        'module' => 'backup',
        'action' => 'download',
        'status' => 'open',
    ]);
});

it('records undo failures without storing the full token', function () {
    $user = User::factory()->create();
    $token = 'super-secret-token-that-should-not-be-stored';

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $token))
        ->assertRedirect()
        ->assertSessionHas('error');

    $failure = SystemFailure::firstOrFail();

    expect($failure->module)->toBe('deshacer');
    expect($failure->action)->toBe('restore');
    expect(json_encode($failure->context))->not->toContain($token);
});

it('filters failures by module status and date', function () {
    $user = User::factory()->create();
    SystemFailure::create([
        'user_id' => $user->id,
        'module' => 'backup',
        'action' => 'database',
        'message' => 'Target failure',
        'status' => 'open',
        'occurred_at' => '2026-06-22 10:00:00',
    ]);
    SystemFailure::create([
        'user_id' => $user->id,
        'module' => 'deshacer',
        'action' => 'restore',
        'message' => 'Other failure',
        'status' => 'resolved',
        'occurred_at' => '2026-06-20 10:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('finance.security.index', [
            'module' => 'backup',
            'status' => 'open',
            'date_from' => '2026-06-22',
            'date_to' => '2026-06-22',
        ]))
        ->assertOk()
        ->assertSee('Target failure')
        ->assertDontSee('Other failure');
});

it('marks a failure as resolved', function () {
    $user = User::factory()->create();
    $failure = SystemFailure::create([
        'user_id' => $user->id,
        'module' => 'backup',
        'action' => 'database',
        'message' => 'Pendiente',
        'status' => 'open',
        'occurred_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('finance.security.failures.resolve', $failure))
        ->assertRedirect()
        ->assertSessionHas('success');

    $failure->refresh();

    expect($failure->status)->toBe('resolved');
    expect($failure->resolved_at)->not->toBeNull();
    expect($failure->resolved_by_user_id)->toBe($user->id);
});
