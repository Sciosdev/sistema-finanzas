<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use App\Services\Finance\FinanceCutSuggestionService;
use App\Services\Finance\FinanceProjectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function projectionAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta ' . uniqid(),
        'type' => 'card',
        'opening_balance' => 0,
        'is_active' => true,
    ], $attributes));
}

function projectionCredit(User $user, array $attributes = []): CreditPurchase
{
    return CreditPurchase::create(array_merge([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Compra a meses',
        'total_amount' => 1200,
        'months' => 4,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
    ], $attributes));
}

it('matches the cut reconciliation baseline and keeps accounts with credit limit', function () {
    $user = User::factory()->create();
    $cash = projectionAccount($user, ['name' => 'Efectivo', 'type' => 'cash']);
    $card = projectionAccount($user, ['name' => 'NU', 'credit_limit' => 50000]);

    $cut = DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-07-10',
        'cards_amount' => 500,
        'real_total' => 1500,
        'status' => 'ok',
    ]);
    $cut->balances()->create(['account_id' => $cash->id, 'balance' => 1000]);
    $cut->balances()->create(['account_id' => $card->id, 'balance' => 500]);

    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-12', 'movement_type' => 'income',
        'amount' => 200, 'description' => 'Ingreso NU', 'account_id' => $card->id, 'source' => 'manual',
    ]);
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-13', 'movement_type' => 'expense',
        'amount' => 100, 'description' => 'Gasto efectivo', 'account_id' => $cash->id, 'source' => 'manual',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    // La cuenta con credit_limit NO se excluye del universo de cuentas.
    expect($result['meta']['starting_balance'])->toBe(1600.0)
        ->and($result['meta']['starting_by_account'])->toHaveKey($card->id)
        ->and($result['meta']['starting_by_account'][$card->id]['balance'])->toBe(700.0)
        ->and($result['meta']['baseline_cut_date'])->toBe('2026-07-10')
        ->and($result['warnings'])->not->toContain('no_baseline_cut');

    // Misma base que la conciliación de cortes, al centavo.
    $accounts = Account::where('user_id', $user->id)->where('is_active', true)->get();
    $expected = app(FinanceCutSuggestionService::class)->expectedBalances($user, $accounts, today());
    $expectedTotal = round(collect($expected)->sum('expected'), 2);

    expect($result['meta']['starting_balance'])->toBe($expectedTotal);
});

it('falls back to opening balances and warns when there is no previous cut', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['name' => 'Efectivo', 'opening_balance' => 300]);
    $card = projectionAccount($user, ['name' => 'NU', 'opening_balance' => 200]);

    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-07-14', 'movement_type' => 'income',
        'amount' => 100, 'description' => 'Ingreso', 'account_id' => $card->id, 'source' => 'manual',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['meta']['starting_balance'])->toBe(600.0)
        ->and($result['meta']['baseline_cut_date'])->toBeNull()
        ->and($result['warnings'])->toContain('no_baseline_cut');
});

it('moves an overdue planned payment to day one', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-10',
        'name' => 'Luz', 'amount' => 400, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);
    $dayOne = $result['days'][0];

    expect($dayOne['date'])->toBe('2026-07-15')
        ->and($dayOne['payments'])->toHaveCount(1)
        ->and($dayOne['payments'][0]['is_overdue'])->toBeTrue()
        ->and($dayOne['payment_total'])->toBe(400.0)
        ->and($dayOne['closing_safe'])->toBe(600.0)
        ->and($result['summary']['overdue_payments_total'])->toBe(400.0)
        ->and($result['summary']['overdue_payments_count'])->toBe(1);
});

it('moves an overdue credit installment to day one', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);
    $credit = projectionCredit($user, ['name' => 'Refri']);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id, 'user_id' => $user->id,
        'period_month' => '2026-07-01', 'due_date' => '2026-07-10',
        'installment_number' => 1, 'amount' => 250, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);
    $dayOne = $result['days'][0];

    expect($dayOne['installments'])->toHaveCount(1)
        ->and($dayOne['installments'][0]['is_overdue'])->toBeTrue()
        ->and($dayOne['installments'][0]['credit_name'])->toBe('Refri')
        ->and($dayOne['installment_total'])->toBe(250.0)
        ->and($dayOne['closing_safe'])->toBe(750.0);
});

it('excludes overdue expected income by default and reports it separately', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);

    ExpectedIncome::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-10',
        'name' => 'Renta Jorge', 'amount' => 500, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['days'][0]['incomes'])->toBeEmpty()
        ->and($result['days'][0]['closing_projected'])->toBe(1000.0)
        ->and($result['summary']['overdue_income_total'])->toBe(500.0)
        ->and($result['summary']['overdue_income_items'])->toHaveCount(1)
        ->and($result['summary']['overdue_income_items'][0]['name'])->toBe('Renta Jorge');
});

it('counts overdue income on day one only in the projected track when enabled', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 0, 'count_overdue_income' => true]);

    ExpectedIncome::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-10',
        'name' => 'Renta Jorge', 'amount' => 500, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);
    $dayOne = $result['days'][0];

    expect($dayOne['incomes'])->toHaveCount(1)
        ->and($dayOne['incomes'][0]['is_overdue'])->toBeTrue()
        ->and($dayOne['closing_projected'])->toBe(1500.0)
        ->and($dayOne['closing_safe'])->toBe(1000.0)
        ->and($result['summary']['overdue_income_total'])->toBe(0.0);
});

it('uses only the residual amount for partial payments and incomes', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 2000]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-18',
        'name' => 'Renta depa', 'amount' => 1000, 'paid_amount' => 400, 'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-20',
        'name' => 'Cliente', 'amount' => 800, 'received_amount' => 300, 'status' => 'partial',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);
    $days = collect($result['days']);

    expect($days->firstWhere('date', '2026-07-18')['payment_total'])->toBe(600.0)
        ->and($days->firstWhere('date', '2026-07-20')['income_total'])->toBe(500.0)
        ->and($result['summary']['total_payments'])->toBe(600.0)
        ->and($result['summary']['total_incomes'])->toBe(500.0);
});

it('does not double count a planned payment paid with credit against its installments', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 2000]);
    $credit = projectionCredit($user, ['name' => 'Pantalla MSI', 'total_amount' => 900, 'months' => 3]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-05',
        'name' => 'Pantalla', 'amount' => 900, 'paid_amount' => 900, 'status' => 'paid',
        'is_credit' => true, 'credit_purchase_id' => $credit->id,
    ]);
    CreditInstallment::create([
        'credit_purchase_id' => $credit->id, 'user_id' => $user->id,
        'period_month' => '2026-07-01', 'due_date' => '2026-07-20',
        'installment_number' => 1, 'amount' => 300, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['summary']['total_payments'])->toBe(0.0)
        ->and($result['summary']['total_installments'])->toBe(300.0)
        ->and(collect($result['days'])->firstWhere('date', '2026-07-20')['installments'])->toHaveCount(1);
});

it('includes next month installments in a 30 day horizon and warns about empty planned flow', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 2000]);
    $credit = projectionCredit($user, ['name' => 'Laptop', 'first_due_month' => '2026-08-01']);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id, 'user_id' => $user->id,
        'period_month' => '2026-08-01', 'due_date' => '2026-08-05',
        'installment_number' => 1, 'amount' => 350, 'status' => 'pending',
    ]);

    $service = app(FinanceProjectionService::class);
    $result = $service->project($user, 30);

    expect(collect($result['days'])->firstWhere('date', '2026-08-05')['installment_total'])->toBe(350.0)
        ->and($result['warnings'])->toContain('next_month_flow_empty');

    // Con flujo planeado en agosto, la advertencia desaparece.
    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-08-01', 'due_date' => '2026-08-20',
        'name' => 'Internet agosto', 'amount' => 500, 'status' => 'pending',
    ]);

    expect($service->project($user, 30)['warnings'])->not->toContain('next_month_flow_empty');
});

it('marks a day as ok when the safe balance equals the buffer exactly', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-15',
        'name' => 'Pago justo', 'amount' => 500, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['days'][0]['closing_safe'])->toBe(500.0)
        ->and($result['days'][0]['risk'])->toBe('ok')
        ->and($result['summary']['first_risky_date'])->toBeNull()
        ->and($result['summary']['max_risk'])->toBe('ok');
});

it('marks medium risk when only expected income keeps the buffer alive', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-15',
        'name' => 'Pago fuerte', 'amount' => 600, 'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-15',
        'name' => 'Cliente', 'amount' => 400, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    // Seguro 400 (< colchón) pero proyectado 800 (≥ colchón).
    expect($result['days'][0]['closing_safe'])->toBe(400.0)
        ->and($result['days'][0]['closing_projected'])->toBe(800.0)
        ->and($result['days'][0]['risk'])->toBe('medium');
});

it('marks high risk when the projected balance hits zero below the buffer', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-15',
        'name' => 'Pago total', 'amount' => 1000, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['days'][0]['closing_projected'])->toBe(0.0)
        ->and($result['days'][0]['risk'])->toBe('high');
});

it('marks critical risk when the projected balance goes negative', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-15',
        'name' => 'Pago imposible', 'amount' => 1500, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['days'][0]['closing_projected'])->toBe(-500.0)
        ->and($result['days'][0]['risk'])->toBe('critical')
        ->and($result['summary']['first_risky_date'])->toBe('2026-07-15')
        ->and($result['summary']['max_risk'])->toBe('critical');
});

it('chains each closing balance into the next day opening balance', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-17',
        'name' => 'Pago', 'amount' => 200, 'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-19',
        'name' => 'Cobro', 'amount' => 300, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);
    $days = $result['days'];

    expect($days)->toHaveCount(7)
        ->and($days[0]['opening_safe'])->toBe(1000.0)
        ->and($days[0]['opening_projected'])->toBe(1000.0);

    foreach (range(0, 5) as $index) {
        expect($days[$index]['closing_safe'])->toBe($days[$index + 1]['opening_safe'])
            ->and($days[$index]['closing_projected'])->toBe($days[$index + 1]['opening_projected']);
    }

    expect($result['summary']['end_balance_safe'])->toBe(800.0)
        ->and($result['summary']['end_balance_projected'])->toBe(1100.0);
});

it('never mixes data from another user', function () {
    $user = User::factory()->create();
    projectionAccount($user, ['opening_balance' => 1000]);

    $other = User::factory()->create();
    projectionAccount($other, ['opening_balance' => 5000]);
    PlannedPayment::create([
        'user_id' => $other->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-16',
        'name' => 'Pago ajeno', 'amount' => 999, 'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $other->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-16',
        'name' => 'Ingreso ajeno', 'amount' => 888, 'status' => 'pending',
    ]);

    $result = app(FinanceProjectionService::class)->project($user, 7);

    expect($result['meta']['starting_balance'])->toBe(1000.0)
        ->and($result['summary']['total_payments'])->toBe(0.0)
        ->and($result['summary']['total_incomes'])->toBe(0.0)
        ->and($result['meta']['starting_by_account'])->toHaveCount(1);
});

it('rejects horizons outside 7, 15 and 30 days', function () {
    $user = User::factory()->create();

    app(FinanceProjectionService::class)->project($user, 10);
})->throws(InvalidArgumentException::class);
