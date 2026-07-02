<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use App\Services\Finance\FinancePaymentRecommendationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function recommendationAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 0,
        'is_active' => true,
    ], $attributes));
}

function recommendationCredit(User $user, array $attributes = []): CreditPurchase
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

it('calculates available safe today from day one closing safe minus buffer', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 300]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['available']['safe_today'])->toBe(700.0);
});

it('calculates available projected today from day one closing projected minus buffer', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 300]);

    ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Cliente',
        'amount' => 500,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['available']['projected_today'])->toBe(1200.0);
});

it('calculates cash needed to avoid a negative projected balance', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 100]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Pago fuerte',
        'amount' => 300,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['shortfall']['cash_needed_to_avoid_negative'])->toBe(200.0);
});

it('calculates cash needed to keep the minimum buffer', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 700]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-16',
        'name' => 'Pago mañana',
        'amount' => 500,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['shortfall']['cash_needed_for_buffer'])->toBe(200.0);
});

it('puts overdue day one planned payments in pay now', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Luz',
        'amount' => 400,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['recommendations']['pay_now'])
        ->toHaveCount(1)
        ->and($result['recommendations']['pay_now'][0]['type'])->toBe('payment')
        ->and($result['recommendations']['pay_now'][0]['id'])->toBe($payment->id)
        ->and($result['recommendations']['pay_now'][0]['is_overdue'])->toBeTrue();
});

it('puts overdue day one credit installments in pay now', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);
    $credit = recommendationCredit($user, ['name' => 'Refri']);

    $installment = CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'installment_number' => 1,
        'amount' => 250,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['recommendations']['pay_now'])
        ->toHaveCount(1)
        ->and($result['recommendations']['pay_now'][0]['type'])->toBe('installment')
        ->and($result['recommendations']['pay_now'][0]['id'])->toBe($installment->id)
        ->and($result['recommendations']['pay_now'][0]['is_overdue'])->toBeTrue();
});

it('puts payments from medium risk days in wait for income', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Pago condicionado',
        'amount' => 600,
        'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Cliente',
        'amount' => 400,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['recommendations']['wait_for_income'])
        ->toHaveCount(1)
        ->and($result['recommendations']['wait_for_income'][0]['name'])->toBe('Pago condicionado')
        ->and($result['recommendations']['wait_for_income'][0]['risk_after_payment'])->toBe('medium');
});

it('puts payments from high and critical risk days in risky payments', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Pago alto',
        'amount' => 1000,
        'status' => 'pending',
    ]);
    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-16',
        'name' => 'Pago crítico',
        'amount' => 100,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect(collect($result['recommendations']['risky_payments'])->pluck('risk_after_payment')->all())
        ->toBe(['high', 'critical']);
});

it('puts overdue expected incomes in overdue income to collect', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Renta',
        'amount' => 800,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($result['recommendations']['overdue_income_to_collect'])
        ->toHaveCount(1)
        ->and($result['recommendations']['overdue_income_to_collect'][0]['id'])->toBe($income->id)
        ->and($result['recommendations']['overdue_income_to_collect'][0]['amount'])->toBe(800.0);
});

it('does not include another user data', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1000]);

    $other = User::factory()->create();
    recommendationAccount($other, ['opening_balance' => 5000]);
    PlannedPayment::create([
        'user_id' => $other->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Pago ajeno',
        'amount' => 999,
        'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $other->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Ingreso ajeno',
        'amount' => 888,
        'status' => 'pending',
    ]);

    $result = app(FinancePaymentRecommendationService::class)->recommend($user, 7);
    $payload = json_encode($result);

    expect(str_contains($payload, 'Pago ajeno'))->toBeFalse()
        ->and(str_contains($payload, 'Ingreso ajeno'))->toBeFalse();
});

it('does not create movements or change statuses', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 2000]);
    $credit = recommendationCredit($user);

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Pago',
        'amount' => 200,
        'status' => 'pending',
    ]);
    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Ingreso',
        'amount' => 300,
        'status' => 'partial',
    ]);
    $installment = CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'installment_number' => 1,
        'amount' => 250,
        'status' => 'pending',
    ]);

    app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect(Movement::count())->toBe(0)
        ->and($payment->fresh()->status)->toBe('pending')
        ->and($income->fresh()->status)->toBe('partial')
        ->and($installment->fresh()->status)->toBe('pending');
});

it('shows the new recommendation cards on the planner page', function () {
    $user = User::factory()->create();
    recommendationAccount($user, ['opening_balance' => 1200]);

    $this->actingAs($user)
        ->get('/finanzas/planificador')
        ->assertOk()
        ->assertSee('Disponible seguro hoy')
        ->assertSee('Disponible proyectado hoy')
        ->assertSee('Faltante para no quedar negativo')
        ->assertSee('Faltante para mantener colchón')
        ->assertSee('Paga / atiende hoy')
        ->assertSee('Ingresos vencidos por cobrar');
});
