<?php

use App\Models\Finance\Account;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('requires an authenticated session', function () {
    $this->get('/finanzas/planificador')->assertRedirect(route('login'));
});

it('renders the projection page for the authenticated user', function () {
    $user = User::factory()->create();
    Account::create([
        'user_id' => $user->id, 'name' => 'Efectivo', 'type' => 'cash',
        'opening_balance' => 1200, 'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/finanzas/planificador')
        ->assertOk()
        ->assertSee('Planificador de flujo')
        ->assertSee('Proyección diaria')
        ->assertSee('$1,200.00');
});

it('falls back to the 15 day horizon when the query value is invalid', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/finanzas/planificador?horizonte=12')
        ->assertOk()
        ->assertSee('Planificador de flujo');
});

it('saves the planner settings for the current user only', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/finanzas/planificador')
        ->post('/finanzas/planificador/configuracion', [
            'minimum_buffer' => '750.50',
            'count_overdue_income' => '1',
        ])
        ->assertRedirect('/finanzas/planificador');

    $setting = PlannerSetting::where('user_id', $user->id)->firstOrFail();

    expect((float) $setting->minimum_buffer)->toBe(750.5)
        ->and($setting->count_overdue_income)->toBeTrue()
        ->and(PlannerSetting::count())->toBe(1);

    // Guardar de nuevo actualiza la misma fila, no crea otra.
    $this->actingAs($user)
        ->post('/finanzas/planificador/configuracion', ['minimum_buffer' => '100'])
        ->assertRedirect();

    expect(PlannerSetting::count())->toBe(1)
        ->and((float) PlannerSetting::where('user_id', $user->id)->value('minimum_buffer'))->toBe(100.0);
});
