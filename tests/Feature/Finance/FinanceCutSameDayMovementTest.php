<?php

use App\Models\Finance\Account;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCutSuggestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function sameDayAccount(User $user, string $name = 'NU'): Account
{
    return Account::create([
        'user_id' => $user->id, 'name' => $name, 'type' => 'card',
        'opening_balance' => 0, 'is_active' => true,
    ]);
}

function sameDayCut(User $user, Account $account, float $balance, string $date): DailyCut
{
    $cut = DailyCut::create([
        'user_id' => $user->id, 'cut_date' => $date,
        'cards_amount' => 0, 'real_total' => 0, 'status' => 'ok',
    ]);
    $cut->balances()->create(['account_id' => $account->id, 'balance' => $balance]);

    return $cut;
}

function sameDayExpected(User $user, string $asOf): array
{
    $accounts = Account::where('user_id', $user->id)->where('is_active', true)->get();

    return app(FinanceCutSuggestionService::class)->expectedBalances($user, $accounts, Carbon::parse($asOf));
}

it('counts a movement registered after a same-day cut', function () {
    $user = User::factory()->create();
    $nu = sameDayAccount($user);

    Carbon::setTestNow('2026-07-03 10:00:00');
    sameDayCut($user, $nu, 8451.62, '2026-07-03');

    // Pago hecho DESPUÉS del corte, el mismo día.
    Carbon::setTestNow('2026-07-03 15:00:00');
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-03', 'movement_type' => 'expense',
        'amount' => 1830.38, 'description' => 'Crédito NU', 'account_id' => $nu->id, 'source' => 'credit_installment',
    ]);

    expect(sameDayExpected($user, '2026-07-04')[$nu->id]['expected'])->toBe(6621.24);
});

it('does not double count the automatic yield of a cut', function () {
    $user = User::factory()->create();
    $nu = sameDayAccount($user);

    Carbon::setTestNow('2026-07-03 10:00:00');
    sameDayCut($user, $nu, 8451.62, '2026-07-03');

    // El rendimiento se genera a la hora del corte, mismo día, source auto:daily-cut.
    Carbon::setTestNow('2026-07-03 10:00:01');
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-03', 'movement_type' => 'yield',
        'amount' => 50, 'description' => 'Rendimiento NU', 'account_id' => $nu->id, 'source' => 'auto:daily-cut',
    ]);

    // No se vuelve a sumar: ya está dentro del saldo del corte (8451.62, no 8501.62).
    expect(sameDayExpected($user, '2026-07-04')[$nu->id]['expected'])->toBe(8451.62);
});

it('ignores same-day movements registered before the cut', function () {
    $user = User::factory()->create();
    $nu = sameDayAccount($user);

    // Gasto del día ANTES del corte (ya quedó dentro del saldo declarado).
    Carbon::setTestNow('2026-07-03 08:00:00');
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-03', 'movement_type' => 'expense',
        'amount' => 100, 'description' => 'Gasto temprano', 'account_id' => $nu->id, 'source' => 'manual',
    ]);

    Carbon::setTestNow('2026-07-03 10:00:00');
    sameDayCut($user, $nu, 8451.62, '2026-07-03');

    // No se cuenta de nuevo (created_at anterior al corte).
    expect(sameDayExpected($user, '2026-07-04')[$nu->id]['expected'])->toBe(8451.62);
});

it('makes the next cut reconcile after paying the same day', function () {
    $user = User::factory()->create();
    $nu = sameDayAccount($user);

    Carbon::setTestNow('2026-07-03 10:00:00');
    sameDayCut($user, $nu, 8451.62, '2026-07-03');

    Carbon::setTestNow('2026-07-03 15:00:00');
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-03', 'movement_type' => 'expense',
        'amount' => 1830.38, 'description' => 'Crédito NU', 'account_id' => $nu->id, 'source' => 'credit_installment',
    ]);

    Carbon::setTestNow('2026-07-04 10:00:00');
    $cut04 = sameDayCut($user, $nu, 6621.24, '2026-07-04');
    $cut04->load('balances');

    $recon = app(FinanceCutSuggestionService::class)->reconciliationFor($cut04);

    expect($recon[$nu->id]['expected'])->toBe(6621.24)
        ->and($recon[$nu->id]['difference'])->toBe(0.0);
});
