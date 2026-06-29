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

function expectedBalanceUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('computes the expected cash balance from movements when there is no cut yet', function () {
    $user = expectedBalanceUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-01', 'movement_type' => 'income', 'amount' => 500, 'description' => 'Entró', 'account_id' => $cash->id, 'source' => 'manual']);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-05', 'movement_type' => 'expense', 'amount' => 200, 'description' => 'Andrea Tienda', 'account_id' => $cash->id, 'source' => 'manual']);

    $balances = app(FinanceCutSuggestionService::class)
        ->expectedBalances($user, collect([$cash]), Carbon::parse('2026-06-28'));

    expect($balances[$cash->id]['expected'])->toBe(300.0)
        ->and($balances[$cash->id]['delta'])->toBe(300.0)
        ->and($balances[$cash->id]['from_cut'])->toBeFalse();
});

it('anchors the expected balance to the last cut and only counts later movements', function () {
    $user = expectedBalanceUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    $cut = DailyCut::create(['user_id' => $user->id, 'cut_date' => '2026-06-10', 'status' => 'review']);
    $cut->balances()->create(['account_id' => $cash->id, 'balance' => 1000]);

    // Antes del corte: NO debe contar. Después del corte: sí resta.
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-09', 'movement_type' => 'expense', 'amount' => 999, 'description' => 'Previo', 'account_id' => $cash->id, 'source' => 'manual']);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-12', 'movement_type' => 'expense', 'amount' => 100, 'description' => 'Después', 'account_id' => $cash->id, 'source' => 'manual']);

    $balances = app(FinanceCutSuggestionService::class)
        ->expectedBalances($user, collect([$cash]), Carbon::parse('2026-06-28'));

    expect($balances[$cash->id]['baseline'])->toBe(1000.0)
        ->and($balances[$cash->id]['delta'])->toBe(-100.0)
        ->and($balances[$cash->id]['expected'])->toBe(900.0)
        ->and($balances[$cash->id]['from_cut'])->toBeTrue();
});

it('can show a negative expected balance to flag a missing income or loss', function () {
    $user = expectedBalanceUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-05', 'movement_type' => 'expense', 'amount' => 150, 'description' => 'Gasto sin respaldo', 'account_id' => $cash->id, 'source' => 'manual']);

    $balances = app(FinanceCutSuggestionService::class)
        ->expectedBalances($user, collect([$cash]), Carbon::parse('2026-06-28'));

    expect($balances[$cash->id]['expected'])->toBe(-150.0);
});

it('shows the expected balance per account on the accounts page', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = expectedBalanceUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-02', 'movement_type' => 'income', 'amount' => 800, 'description' => 'Entró', 'account_id' => $cash->id, 'source' => 'manual']);
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-06', 'movement_type' => 'expense', 'amount' => 300, 'description' => 'Salió', 'account_id' => $cash->id, 'source' => 'manual']);

    $this->actingAs($user)
        ->get(route('finance.accounts.index'))
        ->assertOk()
        ->assertSee('Saldo esperado por cuenta')
        ->assertSee('$500.00');
});
