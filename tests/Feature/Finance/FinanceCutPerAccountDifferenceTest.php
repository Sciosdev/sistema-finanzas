<?php

use App\Models\Finance\Account;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceCutSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function cutDiffUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function makeCut(User $user, string $date, array $balances): DailyCut
{
    $cut = DailyCut::create(['user_id' => $user->id, 'cut_date' => $date, 'status' => 'review']);

    foreach ($balances as $accountId => $balance) {
        $cut->balances()->create(['account_id' => $accountId, 'balance' => $balance]);
    }

    return $cut->load('balances.account');
}

it('reconciles each account against the previous cut and the later movements', function () {
    $user = cutDiffUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    makeCut($user, '2026-06-10', [$cash->id => 1000]);

    // Despues del corte anterior: +500 ingreso, -200 egreso => esperado 1300.
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-12', 'movement_type' => 'income', 'amount' => 500, 'description' => 'Entro', 'account_id' => $cash->id, 'source' => 'manual']);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-15', 'movement_type' => 'expense', 'amount' => 200, 'description' => 'Salio', 'account_id' => $cash->id, 'source' => 'manual']);

    // Declaro 1280 (faltan 20).
    $cut = makeCut($user, '2026-06-20', [$cash->id => 1280]);

    $rec = app(FinanceCutSuggestionService::class)->reconciliationFor($cut);

    expect($rec[$cash->id]['expected'])->toBe(1300.0)
        ->and($rec[$cash->id]['real'])->toBe(1280.0)
        ->and($rec[$cash->id]['difference'])->toBe(-20.0)
        ->and($rec[$cash->id]['has_baseline'])->toBeTrue();
});

it('flags a surplus when the declared balance is above expected', function () {
    $user = cutDiffUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    makeCut($user, '2026-06-10', [$cash->id => 500]);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-12', 'movement_type' => 'expense', 'amount' => 100, 'description' => 'Salio', 'account_id' => $cash->id, 'source' => 'manual']);

    // Esperado 400, declaro 450 => sobran 50.
    $cut = makeCut($user, '2026-06-20', [$cash->id => 450]);

    $rec = app(FinanceCutSuggestionService::class)->reconciliationFor($cut);

    expect($rec[$cash->id]['expected'])->toBe(400.0)
        ->and($rec[$cash->id]['difference'])->toBe(50.0);
});

it('marks the first cut as a baseline without a comparison', function () {
    $user = cutDiffUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 300, 'description' => 'Entro', 'account_id' => $cash->id, 'source' => 'manual']);

    $cut = makeCut($user, '2026-06-10', [$cash->id => 999]);

    $rec = app(FinanceCutSuggestionService::class)->reconciliationFor($cut);

    // Sin corte anterior: baseline = opening_balance (0) + movimientos hasta la fecha.
    expect($rec[$cash->id]['has_baseline'])->toBeFalse()
        ->and($rec[$cash->id]['expected'])->toBe(300.0);
});

it('shows the per-account breakdown in the cut history', function () {
    Carbon::setTestNow('2026-06-25 10:00:00');
    $user = cutDiffUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    makeCut($user, '2026-06-10', [$cash->id => 1000]);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-12', 'movement_type' => 'expense', 'amount' => 200, 'description' => 'Salio', 'account_id' => $cash->id, 'source' => 'manual']);
    makeCut($user, '2026-06-20', [$cash->id => 780]); // esperado 800 => faltan 20

    $this->actingAs($user)
        ->get(route('finance.cuts.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Diferencia por cuenta')
        ->assertSee('descuadre')
        ->assertSee('Falta $20.00');
});

it('exposes the live per-account expected hooks in the cut form', function () {
    Carbon::setTestNow('2026-06-25 10:00:00');
    $user = cutDiffUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    makeCut($user, '2026-06-10', [$cash->id => 1000]);

    $this->actingAs($user)
        ->get(route('finance.cuts.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('data-expected', false)
        ->assertSee('data-cut-summary', false);
});
