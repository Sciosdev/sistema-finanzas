<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
});

function maintenanceOwner(): User
{
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    return $owner;
}

it('shows the maintenance section to the finance owner', function () {
    $owner = maintenanceOwner();

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Mantenimiento')
        ->assertSee('Estado de migraciones')
        ->assertSee('Haz Backup BD antes de migrar')
        ->assertSee('Confirmo que ya hice backup antes de ejecutar migraciones');
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

    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('Nothing to migrate.');

    $this->actingAs($owner)
        ->post(route('finance.maintenance.run-migrations'), ['confirm_backup' => '1'])
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');
});

it('ignores any arbitrary command sent in the request and still runs only migrate --force', function () {
    $owner = maintenanceOwner();

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
