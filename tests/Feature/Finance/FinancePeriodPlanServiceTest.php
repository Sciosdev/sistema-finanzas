<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinancePeriodPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-03 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function periodPlanAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 0,
        'is_active' => true,
    ], $attributes));
}

function periodPlanIncome(User $user, array $attributes = []): ExpectedIncome
{
    return ExpectedIncome::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Ingreso',
        'amount' => 1000,
        'received_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function periodPlanFlow(User $user, array $attributes = []): PlannedPayment
{
    return PlannedPayment::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Flujo',
        'amount' => 100,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function periodPlanCreditDebt(User $user, string $name, float $amount, string $dueDate, array $creditAttributes = []): CreditPurchase
{
    $credit = CreditPurchase::create(array_merge([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => $name,
        'total_amount' => $amount,
        'months' => 1,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
    ], $creditAttributes));

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => $dueDate,
        'installment_number' => 1,
        'amount' => $amount,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    return $credit;
}

/**
 * Fixture "productivo": réplica del ejemplo real del usuario a 3 de julio.
 * Saldo 12,920.92; ingresos 2,000 (jul-4), 8,200 (jul-15) y 2,000 (ago-4);
 * deuda de crédito del mes MPW/NU/Onix/DIDI; flujos en efectivo y domiciliados.
 */
function productivoFixture(User $user): Account
{
    $cash = periodPlanAccount($user, ['name' => 'Efectivo', 'opening_balance' => 12920.92]);
    $nuCard = periodPlanAccount($user, ['name' => 'NU', 'type' => 'card', 'payment_day' => 27]);

    // Ingresos del mes.
    periodPlanIncome($user, ['name' => 'Cuarto 5', 'amount' => 2000, 'due_date' => '2026-07-04']);
    periodPlanIncome($user, ['name' => 'Cuarto 1', 'amount' => 1200, 'due_date' => '2026-07-15']);
    periodPlanIncome($user, ['name' => 'Consultoria', 'amount' => 2000, 'due_date' => '2026-07-15']);
    periodPlanIncome($user, ['name' => 'Scios', 'amount' => 5000, 'due_date' => '2026-07-15']);
    // Ingreso del mes siguiente (extiende el horizonte de planeación).
    periodPlanIncome($user, ['name' => 'Cuarto 5 ago', 'amount' => 2000, 'period_month' => '2026-08-01', 'due_date' => '2026-08-04']);

    // Deuda de crédito del mes en curso.
    periodPlanCreditDebt($user, 'MPW', 2988.27, '2026-07-23');
    periodPlanCreditDebt($user, 'NU credito', 4349.48, '2026-07-27');
    periodPlanCreditDebt($user, 'Onix', 5000, '2026-07-30');
    periodPlanCreditDebt($user, 'DIDI', 151.65, '2026-07-27');

    // Flujo planeado: en efectivo.
    periodPlanFlow($user, ['name' => 'Camara', 'amount' => 69, 'due_date' => '2026-07-09']);
    periodPlanFlow($user, ['name' => 'Amazon Music', 'amount' => 149, 'due_date' => '2026-07-13']);
    periodPlanFlow($user, ['name' => 'Agua', 'amount' => 400.67, 'due_date' => '2026-07-13']);
    periodPlanFlow($user, ['name' => 'Meli+', 'amount' => 299, 'due_date' => '2026-07-13']);
    // Flujo planeado: domiciliado a tarjeta (NU) → no toca efectivo, se paga el 27.
    periodPlanFlow($user, ['name' => 'Mega Cable', 'amount' => 550, 'due_date' => '2026-07-10', 'is_credit' => true, 'account_id' => $nuCard->id]);
    periodPlanFlow($user, ['name' => 'Camaras', 'amount' => 175, 'due_date' => '2026-07-10', 'is_credit' => true, 'account_id' => $nuCard->id]);

    return $cash;
}

it('sets the planning horizon to reach the first income of next month', function () {
    $user = User::factory()->create();
    productivoFixture($user);

    $meta = app(FinancePeriodPlanService::class)->build($user)['meta'];

    expect($meta['today'])->toBe('2026-07-03')
        ->and($meta['current_month_end'])->toBe('2026-07-31')
        ->and($meta['next_month_first_income_date'])->toBe('2026-08-04')
        ->and($meta['planning_end'])->toBe('2026-08-04')
        ->and($meta['starting_balance'])->toBe(12920.92);
});

it('splits the plan into income cut sub-periods labeled by quincena', function () {
    $user = User::factory()->create();
    productivoFixture($user);

    $segments = app(FinancePeriodPlanService::class)->build($user)['segments'];

    expect($segments)->toHaveCount(6)
        ->and($segments[0]['start_date'])->toBe('2026-07-03')
        ->and($segments[0]['end_date'])->toBe('2026-07-03')
        ->and($segments[1]['start_date'])->toBe('2026-07-04')
        ->and($segments[1]['end_date'])->toBe('2026-07-14')
        ->and($segments[1]['quincena_label'])->toBe('1ª quincena de julio 2026')
        ->and($segments[2]['start_date'])->toBe('2026-07-15')
        ->and($segments[3]['start_date'])->toBe('2026-07-16')
        ->and($segments[3]['end_date'])->toBe('2026-07-31')
        ->and($segments[3]['quincena_label'])->toBe('2ª quincena de julio 2026')
        ->and($segments[5]['start_date'])->toBe('2026-08-04');
});

it('chains the cash runway across income cut sub-periods', function () {
    $user = User::factory()->create();
    productivoFixture($user);

    $segments = app(FinancePeriodPlanService::class)->build($user)['segments'];

    // Tramo jul-4 → jul-14: entra 2,000, se apartan 917.67 de flujos en efectivo.
    expect($segments[1]['opening_balance'])->toBe(12920.92)
        ->and($segments[1]['income_total'])->toBe(2000.0)
        ->and($segments[1]['cash_flows_total'])->toBe(917.67)
        ->and($segments[1]['card_charges_total'])->toBe(0.0)
        ->and($segments[1]['closing_balance'])->toBe(14003.25)
        // Tramo jul-15: entra la quincena de 8,200.
        ->and($segments[2]['income_total'])->toBe(8200.0)
        ->and($segments[2]['closing_balance'])->toBe(22203.25);
});

it('treats domiciled credit flows as card debt paid on the card day, not as cash before', function () {
    $user = User::factory()->create();
    productivoFixture($user);

    $segments = app(FinancePeriodPlanService::class)->build($user)['segments'];

    // Los 725 de flujos domiciliados NO salieron en la 1ª quincena...
    expect($segments[1]['card_charges_total'])->toBe(0.0)
        // ...sino que se pagan con la tarjeta el 27 (2ª quincena) junto a la deuda del mes.
        ->and($segments[3]['card_charges_total'])->toBe(725.0)
        ->and($segments[3]['card_charge_items'][0]['card_account_name'])->toBe('NU')
        ->and($segments[3]['credit_due_total'])->toBe(12489.40)
        ->and($segments[3]['closing_balance'])->toBe(8988.85);
});

it('recognizes current month credit debt grouped by account as reference', function () {
    $user = User::factory()->create();
    productivoFixture($user);

    $plan = app(FinancePeriodPlanService::class)->build($user);
    $byName = collect($plan['credit_accounts'])->keyBy('account_name');

    expect($plan['current_month_credit_due_total'])->toBe(12489.40)
        ->and($byName['NU credito']['month_due_total'])->toBe(4349.48)
        ->and($byName['MPW']['month_due_total'])->toBe(2988.27)
        ->and($byName['Onix']['month_due_total'])->toBe(5000.0)
        ->and($byName['DIDI']['month_due_total'])->toBe(151.65)
        ->and($byName['NU credito']['next_due_date'])->toBe('2026-07-27');
});

it('does not create movements or change states while building the period plan', function () {
    $user = User::factory()->create();
    productivoFixture($user);
    $movementCount = Movement::count();
    $installmentCount = CreditInstallment::count();
    $installment = CreditInstallment::first();

    app(FinancePeriodPlanService::class)->build($user);

    expect(Movement::count())->toBe($movementCount)
        ->and(CreditInstallment::count())->toBe($installmentCount)
        ->and($installment->fresh()->status)->toBe('pending');
});

it('does not use data from another user', function () {
    $user = User::factory()->create();
    productivoFixture($user);

    $other = User::factory()->create();
    periodPlanAccount($other, ['opening_balance' => 99999]);
    periodPlanIncome($other, ['name' => 'Ingreso ajeno', 'amount' => 88888, 'due_date' => '2026-07-15']);

    $plan = app(FinancePeriodPlanService::class)->build($user);

    expect($plan['meta']['starting_balance'])->toBe(12920.92)
        ->and(json_encode($plan))->not->toContain('Ingreso ajeno');
});
