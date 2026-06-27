<?php

use App\Models\Finance\Account;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-26 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function cutSuggestUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function lastCutWith(User $user, array $balances, string $date = '2026-06-20'): DailyCut
{
    $cut = DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => $date,
        'cards_amount' => 0,
        'real_total' => 0,
        'status' => 'ok',
    ]);

    foreach ($balances as $accountId => $balance) {
        $cut->balances()->create(['account_id' => $accountId, 'balance' => $balance]);
    }

    return $cut;
}

it('prefills each account with last cut balance plus incomes minus expenses', function () {
    $user = cutSuggestUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    lastCutWith($user, [$cash->id => 1000, $nu->id => 500], '2026-06-20');

    // Movimientos posteriores al último corte.
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-22', 'movement_type' => 'income',
        'amount' => 200, 'description' => 'Ingreso NU', 'account_id' => $nu->id, 'source' => 'manual',
    ]);
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-23', 'movement_type' => 'expense',
        'amount' => 100, 'description' => 'Gasto efectivo', 'account_id' => $cash->id, 'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.cuts.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('value="900"', false)  // Efectivo: 1000 - 100
        ->assertSee('value="700"', false); // NU: 500 + 200
});

it('ignores movements dated on or before the last cut', function () {
    $user = cutSuggestUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    lastCutWith($user, [$cash->id => 1000], '2026-06-20');

    // Movimiento en la misma fecha del corte: ya está reflejado, no debe contar.
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-20', 'movement_type' => 'expense',
        'amount' => 300, 'description' => 'Antes del corte', 'account_id' => $cash->id, 'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.cuts.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('value="1000"', false);
});

it('keeps zero when there is no previous cut', function () {
    $user = cutSuggestUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-22', 'movement_type' => 'income',
        'amount' => 200, 'description' => 'Ingreso', 'account_id' => $cash->id, 'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.cuts.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('value="0"', false);
});

it('does not use another users cuts or movements', function () {
    $user = cutSuggestUser();
    $other = cutSuggestUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $otherCash = Account::where('user_id', $other->id)->where('name', 'Efectivo')->firstOrFail();

    lastCutWith($other, [$otherCash->id => 9999], '2026-06-20');

    $this->actingAs($user)
        ->get(route('finance.cuts.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertDontSee('value="9999"', false);
});
