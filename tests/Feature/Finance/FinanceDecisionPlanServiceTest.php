<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditOption;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\Finance\SpendingLimit;
use App\Models\User;
use App\Services\Finance\FinanceCreditOptionSimulationService;
use App\Services\Finance\FinanceDecisionPlanService;
use App\Services\Finance\FinancePaymentRecommendationService;
use App\Services\Finance\FinanceProjectionService;
use App\Services\Finance\FinanceSpendingLimitService;
use App\Services\Finance\FinanceSurvivalBudgetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-02 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function decisionPlanAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 10000,
        'is_active' => true,
    ], $attributes));
}

function decisionPlanCategory(User $user, array $attributes = []): Category
{
    return Category::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Comida '.uniqid(),
        'type' => 'expense',
        'group' => 'Flexible',
        'is_active' => true,
    ], $attributes));
}

function decisionPlanIncome(User $user, array $attributes = []): ExpectedIncome
{
    return ExpectedIncome::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-15',
        'name' => 'Pago quincenal',
        'amount' => 8000,
        'received_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function decisionPlanPayment(User $user, array $attributes = []): PlannedPayment
{
    return PlannedPayment::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Pago programado',
        'amount' => 500,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function decisionPlanCredit(User $user, array $attributes = []): CreditPurchase
{
    return CreditPurchase::create(array_merge([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'NU',
        'total_amount' => 1800,
        'months' => 3,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
    ], $attributes));
}

function decisionPlanInstallment(User $user, CreditPurchase $credit, array $attributes = []): CreditInstallment
{
    return CreditInstallment::create(array_merge([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-23',
        'installment_number' => 1,
        'amount' => 900,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function decisionPlanMovement(User $user, Category $category, array $attributes = []): Movement
{
    return Movement::create(array_merge([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Gasto flexible',
        'category_id' => $category->id,
        'source' => 'manual',
    ], $attributes));
}

function decisionPlanCreditOption(User $user, array $attributes = []): CreditOption
{
    return CreditOption::create(array_merge([
        'user_id' => $user->id,
        'name' => 'NU opcion',
        'provider' => 'NU',
        'available_amount' => 5000,
        'min_amount' => 0,
        'cost_type' => 'total_percent',
        'cost_percent' => 3,
        'fixed_fee' => 0,
        'term_months' => 1,
        'payment_day' => 15,
        'is_active' => true,
    ], $attributes));
}

function decisionPlanReport(User $user, int $horizonDays = 30): array
{
    return app(FinanceDecisionPlanService::class)->build($user, $horizonDays);
}

it('calculates the recommended buffer with basic history', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 50000]);
    decisionPlanIncome($user);
    $food = decisionPlanCategory($user, ['name' => 'Comida']);
    decisionPlanMovement($user, $food, ['amount' => 12000]);

    $buffer = decisionPlanReport($user)['buffer'];

    expect($buffer['historical_basic_daily_spend'])->toBe(200.0)
        ->and($buffer['recommended_min_buffer'])->toBe(600.0)
        ->and($buffer['recommended_ideal_buffer'])->toBe(1400.0)
        ->and($buffer['buffer_used'])->toBe(600.0);
});

it('uses the base buffer when there is no basic history', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $buffer = decisionPlanReport($user)['buffer'];

    expect($buffer['historical_basic_daily_spend'])->toBe(150.0)
        ->and($buffer['recommended_min_buffer'])->toBe(500.0)
        ->and($buffer['recommended_ideal_buffer'])->toBe(1050.0);
});

it('does not depend on the manual minimum buffer for buffer_used', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 3000]);

    $buffer = decisionPlanReport($user)['buffer'];

    expect($buffer['manual_buffer_reference'])->toBe(3000.0)
        ->and($buffer['buffer_used'])->toBe(500.0);
});

it('shows the manual buffer only as a reference', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2500]);

    $buffer = decisionPlanReport($user)['buffer'];

    expect($buffer['manual_buffer_reference'])->toBe(2500.0)
        ->and($buffer['recommended_min_buffer'])->not->toBe(2500.0);
});

it('detects the next income inside the horizon', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15', 'name' => 'Nomina']);

    $window = decisionPlanReport($user, 15)['current_window'];

    expect($window['next_income_date'])->toBe('2026-07-15')
        ->and($window['next_income_name'])->toBe('Nomina')
        ->and($window['next_income_amount'])->toBe(8000.0);
});

it('uses a fifteen day window when there is no income inside the horizon', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-20']);

    $result = decisionPlanReport($user, 15);

    expect($result['current_window']['end_date'])->toBe('2026-07-16')
        ->and($result['current_window']['days_count'])->toBe(15)
        ->and($result['warnings'])->toContain('no_next_income_within_horizon');
});

it('creates the current window until the day before the next income', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);

    $window = decisionPlanReport($user)['current_window'];

    expect($window['start_date'])->toBe('2026-07-02')
        ->and($window['end_date'])->toBe('2026-07-14')
        ->and($window['days_count'])->toBe(13);
});

it('calculates payments before the next income', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, ['due_date' => '2026-07-10', 'amount' => 2000]);
    decisionPlanPayment($user, ['due_date' => '2026-07-20', 'amount' => 999]);

    $money = decisionPlanReport($user)['money_plan'];

    expect($money['before_income_payments_reserve'])->toBe(2000.0)
        ->and($money['future_payments_reserve'])->toBe(999.0);
});

it('handles credits after the next income as reserve and wait actions', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'NU']);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-23', 'amount' => 900]);

    $result = decisionPlanReport($user);

    expect($result['money_plan']['credit_reserve'])->toBe(900.0)
        ->and(json_encode($result['actions']['wait']))->toContain('NU');
});

it('calculates living money using the recommended buffer', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 10000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, ['due_date' => '2026-07-10', 'amount' => 2000]);

    $money = decisionPlanReport($user)['money_plan'];

    expect($money['buffer_reserve'])->toBe(500.0)
        ->and($money['living_money'])->toBe(7500.0)
        ->and($money['daily_living_allowance'])->toBe(576.92);
});

it('generates need_money when money is missing', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 400]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);

    $result = decisionPlanReport($user);

    expect($result['money_plan']['shortfall'])->toBeGreaterThan(0)
        ->and($result['actions']['need_money'])->not->toBeEmpty();
});

it('generates savings_possible when there is real surplus', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 10000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);

    $result = decisionPlanReport($user);

    expect($result['money_plan']['savings_possible'])->toBeGreaterThan(0)
        ->and($result['actions']['save'])->not->toBeEmpty();
});

it('recommends paying overdue payments today', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    decisionPlanPayment($user, ['name' => 'Luz vencida', 'due_date' => '2026-07-01', 'amount' => 300]);

    $payToday = decisionPlanReport($user)['actions']['pay_today'];

    expect($payToday)->not->toBeEmpty()
        ->and($payToday[0]['name'])->toBe('Luz vencida');
});

it('recommends waiting for payments due after the next income', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, ['name' => 'NU futuro', 'due_date' => '2026-07-20', 'amount' => 700]);

    $wait = decisionPlanReport($user)['actions']['wait'];

    expect(json_encode($wait))->toContain('NU futuro');
});

it('recommends reserving payments inside the horizon', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, ['name' => 'Internet', 'due_date' => '2026-07-20', 'amount' => 700]);

    $reserve = decisionPlanReport($user)['actions']['reserve'];

    expect(json_encode($reserve))->toContain('Internet');
});

it('does not recommend paying today for a forced automatic payment before its charge window', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, [
        'name' => 'Google One',
        'due_date' => '2026-07-10',
        'amount' => 100,
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $payToday = decisionPlanReport($user)['actions']['pay_today'];

    expect(json_encode($payToday))->not->toContain('Google One');
});

it('recommends reserve for a forced automatic payment before its charge window', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, [
        'name' => 'Google One',
        'due_date' => '2026-07-10',
        'amount' => 100,
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $reserve = decisionPlanReport($user)['actions']['reserve'];

    expect(json_encode($reserve))->toContain('Google One')
        ->and($reserve[0]['reason'])->toContain('No lo pagues todavía')
        ->and($reserve[0]['automatic_charge_state'])->toBe('before');
});

it('marks a forced automatic payment as in progress when today is inside its charge window', function () {
    Carbon::setTestNow('2026-07-10 10:00:00');

    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, [
        'name' => 'Google One',
        'due_date' => '2026-07-10',
        'amount' => 100,
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $payToday = decisionPlanReport($user)['actions']['pay_today'];

    expect($payToday)->toHaveCount(1)
        ->and($payToday[0]['name'])->toBe('Google One')
        ->and($payToday[0]['reason'])->toContain('Este cobro automático puede caer')
        ->and($payToday[0]['automatic_charge_state'])->toBe('in_window');
});

it('treats a forced automatic payment as overdue when its charge window already passed', function () {
    Carbon::setTestNow('2026-07-12 10:00:00');

    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, [
        'name' => 'Google One',
        'due_date' => '2026-07-10',
        'amount' => 100,
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $payToday = decisionPlanReport($user)['actions']['pay_today'];

    expect($payToday)->toHaveCount(1)
        ->and($payToday[0]['name'])->toBe('Google One')
        ->and($payToday[0]['reason'])->toContain('La ventana de cobro ya pasó')
        ->and($payToday[0]['is_overdue'])->toBeTrue()
        ->and($payToday[0]['automatic_charge_state'])->toBe('after');
});

it('uses the category budget produced by the survival budget service as basis', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    $food = decisionPlanCategory($user, ['name' => 'Comida']);
    decisionPlanMovement($user, $food, ['amount' => 300]);

    $rows = collect(decisionPlanReport($user)['category_budget'])->keyBy('category_name');

    expect($rows)->toHaveKey('Comida')
        ->and($rows['Comida']['historical_spent'])->toBe(300.0);
});

it('does not use data from another user', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 1000]);
    decisionPlanIncome($user);

    $other = User::factory()->create();
    decisionPlanAccount($other, ['opening_balance' => 90000]);
    decisionPlanIncome($other, ['name' => 'Ingreso ajeno', 'amount' => 90000]);
    decisionPlanPayment($other, ['name' => 'Pago ajeno', 'amount' => 9999]);

    $result = decisionPlanReport($user);

    expect($result['money_plan']['starting_balance'])->toBe(1000.0)
        ->and(json_encode($result))->not->toContain('Pago ajeno')
        ->and(json_encode($result))->not->toContain('Ingreso ajeno');
});

it('does not create movements', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    $category = decisionPlanCategory($user);
    decisionPlanMovement($user, $category);
    $movementCount = Movement::count();

    decisionPlanReport($user);

    expect(Movement::count())->toBe($movementCount);
});

it('does not change planned payments', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    $payment = decisionPlanPayment($user, ['status' => 'pending', 'paid_amount' => 25]);

    decisionPlanReport($user);

    expect($payment->fresh()->status)->toBe('pending')
        ->and((float) $payment->fresh()->paid_amount)->toBe(25.0);
});

it('does not change credits or installments', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    $credit = decisionPlanCredit($user, ['status' => 'active']);
    $installment = decisionPlanInstallment($user, $credit, ['status' => 'pending', 'paid_amount' => 10]);
    $creditCount = CreditPurchase::count();
    $installmentCount = CreditInstallment::count();

    decisionPlanReport($user);

    expect(CreditPurchase::count())->toBe($creditCount)
        ->and(CreditInstallment::count())->toBe($installmentCount)
        ->and($credit->fresh()->status)->toBe('active')
        ->and($installment->fresh()->status)->toBe('pending')
        ->and((float) $installment->fresh()->paid_amount)->toBe(10.0);
});

it('shows the recommended plan section on the projection page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Plan recomendado', false);
});

it('shows the recommended buffer section on the projection page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Colchón recomendado', false);
});

it('shows human timeline messages at the top of the recommended plan', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 10000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, ['due_date' => '2026-07-10', 'amount' => 500]);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Primero guarda', false)
        ->assertSee('El sistema recomienda conservar', false)
        ->assertSee('Puedes vivir con', false);
});

it('shows reserve messaging for forced automatic payments on the recommended plan page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 10000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, [
        'name' => 'Google One',
        'due_date' => '2026-07-10',
        'amount' => 100,
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('finance.projection.index'))
        ->assertOk()
        ->assertSee('Cobro automático', false)
        ->assertSee('Ventana de cobro: 2026-07-09 a 2026-07-11', false)
        ->assertSee('Reserva este dinero; no es pago manual anticipado.', false)
        ->assertSee('No lo pagues todavía', false);
});

it('does not show technical warning keys on the recommended plan', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 1000]);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertDontSee('no_next_income_within_horizon', false)
        ->assertSee('No hay ingreso esperado dentro del horizonte. El plan usa una ventana corta para no sobreestimar tu dinero.', false);
});

it('clarifies reserves that are already shown in pay today', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 10000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    decisionPlanPayment($user, ['due_date' => '2026-07-10', 'amount' => 500]);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Ya incluido en Paga hoy', false);
});

it('renames the old manual buffer card on the projection page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Colchón manual / configuración anterior', false);
});

it('does not break finance survival budget', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    decisionPlanReport($user);

    expect(app(FinanceSurvivalBudgetService::class)->build($user, 30)['window']['next_income_date'])->toBe('2026-07-15');
});

it('does not break finance projection', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);

    decisionPlanReport($user);

    expect(app(FinanceProjectionService::class)->project($user, 7)['days'])->toHaveCount(7);
});

it('does not break finance payment recommendations', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 400]);

    decisionPlanReport($user);

    expect(app(FinancePaymentRecommendationService::class)->recommend($user, 7)['available']['safe_today'])->toBe(400.0);
});

it('does not break finance spending limits', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 400]);
    $category = decisionPlanCategory($user, ['name' => 'Gasolina']);
    SpendingLimit::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period_type' => 'weekly',
        'limit_amount' => 500,
        'warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    decisionPlanReport($user);

    expect(app(FinanceSpendingLimitService::class)->analyze($user, 7)['summary']['total_limits'])->toBe(1);
});

it('does not break finance credit options', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 400]);
    decisionPlanCreditOption($user);

    decisionPlanReport($user);

    expect(app(FinanceCreditOptionSimulationService::class)->simulate($user, 500, 30)['options'])->toHaveCount(1);
});
