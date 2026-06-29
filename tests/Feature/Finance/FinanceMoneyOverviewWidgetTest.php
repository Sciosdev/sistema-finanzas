<?php

use App\Models\Finance\Account;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function moneyWidgetUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('shows the money overview widget with the total on the movements screen', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = moneyWidgetUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 940, 'description' => 'Entro', 'account_id' => $cash->id, 'source' => 'manual']);

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee('Dinero disponible (estimado)')
        ->assertSee('$940.00')
        ->assertSee('Efectivo: <span', false);
});

it('shows the money overview widget on the cuts screen', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = moneyWidgetUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 940, 'description' => 'Efectivo', 'account_id' => $cash->id, 'source' => 'manual']);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-03', 'movement_type' => 'income', 'amount' => 74.27, 'description' => 'NU', 'account_id' => $nu->id, 'source' => 'manual']);

    $this->actingAs($user)
        ->get(route('finance.cuts.index'))
        ->assertOk()
        ->assertSee('Dinero disponible (estimado)')
        ->assertSee('$1,014.27'); // total efectivo + NU
});

it('exposes the live-preview hooks on the movements screen', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = moneyWidgetUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 940, 'description' => 'Entro', 'account_id' => $cash->id, 'source' => 'manual']);

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee('data-money-overview', false)
        ->assertSee('data-money-total', false)
        ->assertSee('data-money-pill="' . $cash->id . '"', false)
        ->assertSee('data-base=', false);
});

it('shows the widget and the original movement hooks on the edit screen', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = moneyWidgetUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 940, 'description' => 'Entro', 'account_id' => $cash->id, 'source' => 'manual']);
    $movement = Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-05', 'movement_type' => 'expense', 'amount' => 200, 'description' => 'Salio', 'account_id' => $cash->id, 'source' => 'manual']);

    $this->actingAs($user)
        ->get(route('finance.movements.edit', $movement))
        ->assertOk()
        ->assertSee('Dinero disponible (estimado)')
        ->assertSee('data-movement-form', false)
        ->assertSee('data-original-type="expense"', false)
        ->assertSee('data-original-amount="200.00"', false)
        ->assertSee('data-original-account="' . $cash->id . '"', false)
        // La base del widget ya incluye ambos movimientos: 940 - 200 = 740.
        ->assertSee('$740.00');
});

it('does not count one account into another in the money widget total', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = moneyWidgetUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 500, 'description' => 'Entro', 'account_id' => $cash->id, 'source' => 'manual']);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-05', 'movement_type' => 'expense', 'amount' => 200, 'description' => 'Salio', 'account_id' => $cash->id, 'source' => 'manual']);

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee('$300.00'); // 500 - 200 en efectivo
});
