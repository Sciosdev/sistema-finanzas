<?php

use App\Models\Finance\Account;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceCutSuggestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-26 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function cutSuggestionUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('returns previous balance and suggested balance per account', function () {
    $user = cutSuggestionUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $cut = DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-06-20',
        'cards_amount' => 0,
        'real_total' => 0,
        'status' => 'ok',
    ]);
    $cut->balances()->create(['account_id' => $cash->id, 'balance' => 1000]);
    $cut->balances()->create(['account_id' => $nu->id, 'balance' => 500]);

    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-22', 'movement_type' => 'income',
        'amount' => 200, 'description' => 'Ingreso NU', 'account_id' => $nu->id, 'source' => 'manual',
    ]);
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-23', 'movement_type' => 'expense',
        'amount' => 100, 'description' => 'Gasto efectivo', 'account_id' => $cash->id, 'source' => 'manual',
    ]);

    $accounts = Account::where('user_id', $user->id)->where('is_active', true)->get();
    $result = app(FinanceCutSuggestionService::class)->suggest($user, $accounts, Carbon::parse('2026-06-26'));

    expect($result['previous'][$cash->id])->toBe(1000.0)
        ->and($result['previous'][$nu->id])->toBe(500.0)
        ->and($result['suggested'][$cash->id])->toBe(900.0)
        ->and($result['suggested'][$nu->id])->toBe(700.0)
        ->and($result['previous_cut_date']->toDateString())->toBe('2026-06-20');
});

it('returns empty suggestions when there is no previous cut', function () {
    $user = cutSuggestionUser();
    $accounts = Account::where('user_id', $user->id)->where('is_active', true)->get();

    $result = app(FinanceCutSuggestionService::class)->suggest($user, $accounts, Carbon::parse('2026-06-26'));

    expect($result['suggested'])->toBe([])
        ->and($result['previous'])->toBe([])
        ->and($result['previous_cut_date'])->toBeNull();
});

it('prefills the daily cut widget on the dashboard with suggested and previous balances', function () {
    $user = cutSuggestionUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    $cut = DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-06-20',
        'cards_amount' => 0,
        'real_total' => 0,
        'status' => 'ok',
    ]);
    $cut->balances()->create(['account_id' => $cash->id, 'balance' => 1700]);

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertSee('value="1700"', false)   // prellenado en el corte del resumen
        ->assertSee('Anterior: $1,700.00');
});
