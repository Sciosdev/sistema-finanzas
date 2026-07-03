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

function survivalBudgetAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 1000,
        'is_active' => true,
    ], $attributes));
}

function survivalBudgetCategory(User $user, array $attributes = []): Category
{
    return Category::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Comida '.uniqid(),
        'type' => 'expense',
        'group' => 'Flexible',
        'is_active' => true,
    ], $attributes));
}

function survivalBudgetIncome(User $user, array $attributes = []): ExpectedIncome
{
    return ExpectedIncome::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-16',
        'name' => 'Pago quincenal',
        'amount' => 8000,
        'received_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function survivalBudgetPayment(User $user, array $attributes = []): PlannedPayment
{
    return PlannedPayment::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-05',
        'name' => 'Pago obligatorio',
        'amount' => 300,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function survivalBudgetCredit(User $user, array $attributes = []): CreditPurchase
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

function survivalBudgetInstallment(User $user, CreditPurchase $credit, array $attributes = []): CreditInstallment
{
    return CreditInstallment::create(array_merge([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'installment_number' => 1,
        'amount' => 200,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

function survivalBudgetMovement(User $user, Category $category, array $attributes = []): Movement
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

function survivalBudgetCreditOption(User $user, array $attributes = []): CreditOption
{
    return CreditOption::create(array_merge([
        'user_id' => $user->id,
        'name' => 'NU',
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

function survivalBudgetReport(User $user, int $horizonDays = 30): array
{
    return app(FinanceSurvivalBudgetService::class)->build($user, $horizonDays);
}

it('detects the next expected income and defines the window until the previous day', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user);
    survivalBudgetIncome($user, ['due_date' => '2026-07-16', 'name' => 'Pago nomina', 'amount' => 9000]);

    $result = survivalBudgetReport($user);

    expect($result['window']['start_date'])->toBe('2026-07-02')
        ->and($result['window']['end_date'])->toBe('2026-07-15')
        ->and($result['window']['days_count'])->toBe(14)
        ->and($result['window']['next_income_date'])->toBe('2026-07-16')
        ->and($result['window']['next_income_name'])->toBe('Pago nomina')
        ->and($result['window']['next_income_amount'])->toBe(9000.0);
});

it('uses a fifteen day window when there is no expected income', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user);

    $result = survivalBudgetReport($user);

    expect($result['window']['end_date'])->toBe('2026-07-16')
        ->and($result['window']['days_count'])->toBe(15)
        ->and($result['alerts']['missing_next_income'])->toBeTrue()
        ->and($result['window']['window_reason'])->toContain('15 dias');
});

it('calculates obligations total with payments and installments inside the window', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 10000]);
    survivalBudgetIncome($user, ['due_date' => '2026-07-16']);
    survivalBudgetPayment($user, ['due_date' => '2026-07-05', 'amount' => 100]);
    survivalBudgetPayment($user, ['due_date' => '2026-07-20', 'amount' => 999]);
    $credit = survivalBudgetCredit($user);
    survivalBudgetInstallment($user, $credit, ['due_date' => '2026-07-10', 'amount' => 200]);

    $money = survivalBudgetReport($user)['money'];

    expect($money['obligations_total'])->toBe(300.0)
        ->and($money['upcoming_obligations_total'])->toBe(300.0)
        ->and($money['overdue_obligations_total'])->toBe(0.0);
});

it('calculates the survival pool from balance minus obligations and buffer', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 11311.38]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 3000]);
    survivalBudgetIncome($user, ['due_date' => '2026-07-16']);
    survivalBudgetPayment($user, ['amount' => 2850]);

    $money = survivalBudgetReport($user)['money'];

    expect($money['starting_balance'])->toBe(11311.38)
        ->and($money['obligations_total'])->toBe(2850.0)
        ->and($money['buffer'])->toBe(3000.0)
        ->and($money['raw_survival_pool'])->toBe(5461.38)
        ->and($money['survival_pool'])->toBe(5461.38);
});

it('sets category recommendations to zero when the survival pool is negative', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 3000]);
    survivalBudgetIncome($user);
    survivalBudgetPayment($user, ['amount' => 500]);

    $result = survivalBudgetReport($user);

    expect($result['money']['raw_survival_pool'])->toBe(-2500.0)
        ->and($result['money']['survival_pool'])->toBe(0.0)
        ->and($result['money']['shortfall_for_survival'])->toBe(2500.0)
        ->and(collect($result['categories'])->pluck('recommended_today')->unique()->all())->toBe([0.0]);
});

it('calculates the maximum suggested daily spend', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1400]);
    survivalBudgetIncome($user, ['due_date' => '2026-07-16']);

    expect(survivalBudgetReport($user)['money']['daily_total_allowance'])->toBe(100.0);
});

it('uses expense history to distribute the budget by category', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    $gas = survivalBudgetCategory($user, ['name' => 'Gasolina']);
    survivalBudgetMovement($user, $food, ['amount' => 300]);
    survivalBudgetMovement($user, $gas, ['amount' => 100]);

    $rows = collect(survivalBudgetReport($user)['categories'])->keyBy('category_name');

    expect($rows['Comida']['historical_percent'])->toBe(75.0)
        ->and($rows['Comida']['budget_total'])->toBe(750.0)
        ->and($rows['Gasolina']['historical_percent'])->toBe(25.0)
        ->and($rows['Gasolina']['budget_total'])->toBe(250.0);
});

it('uses the default distribution when history is insufficient', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);

    $result = survivalBudgetReport($user);

    expect($result['summary']['has_historical_basis'])->toBeFalse()
        ->and($result['alerts']['insufficient_history'])->toBeTrue()
        ->and($result['categories'][0]['category_name'])->toBe('Comida / tienda / despensa')
        ->and($result['categories'][0]['weight_percent'])->toBe(45.0)
        ->and($result['categories'][0]['budget_total'])->toBe(450.0);
});

it('excludes movements from another user from the historical distribution', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    survivalBudgetMovement($user, $food, ['amount' => 100]);

    $other = User::factory()->create();
    survivalBudgetMovement($other, $food, ['amount' => 900]);

    $row = collect(survivalBudgetReport($user)['categories'])->firstWhere('category_name', 'Comida');

    expect($row['historical_spent'])->toBe(100.0)
        ->and($row['budget_total'])->toBe(1000.0);
});

it('excludes non expense movements from the historical distribution', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    survivalBudgetMovement($user, $food, ['movement_type' => 'expense', 'amount' => 100]);
    survivalBudgetMovement($user, $food, ['movement_type' => 'income', 'amount' => 500]);
    survivalBudgetMovement($user, $food, ['movement_type' => 'yield', 'amount' => 700]);

    $row = collect(survivalBudgetReport($user)['categories'])->firstWhere('category_name', 'Comida');

    expect($row['historical_spent'])->toBe(100.0);
});

it('excludes San Juan and rent movements from the historical distribution', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    survivalBudgetMovement($user, $food, ['amount' => 100]);
    survivalBudgetMovement($user, $food, ['amount' => 200, 'is_san_juan' => true]);
    survivalBudgetMovement($user, $food, ['amount' => 300, 'is_rent' => true]);

    $row = collect(survivalBudgetReport($user)['categories'])->firstWhere('category_name', 'Comida');

    expect($row['historical_spent'])->toBe(100.0);
});

it('calculates already spent in the survival window', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user, ['due_date' => '2026-07-10']);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    survivalBudgetMovement($user, $food, ['happened_on' => '2026-06-20', 'amount' => 100]);
    survivalBudgetMovement($user, $food, ['happened_on' => '2026-07-02', 'amount' => 200]);

    $row = collect(survivalBudgetReport($user)['categories'])->firstWhere('category_name', 'Comida');

    expect($row['already_spent_in_window'])->toBe(200.0);
});

it('calculates remaining amount for each category', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user, ['due_date' => '2026-07-10']);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    survivalBudgetMovement($user, $food, ['happened_on' => '2026-06-20', 'amount' => 100]);
    survivalBudgetMovement($user, $food, ['happened_on' => '2026-07-02', 'amount' => 200]);

    $row = collect(survivalBudgetReport($user)['categories'])->firstWhere('category_name', 'Comida');

    expect($row['budget_total'])->toBe(1000.0)
        ->and($row['remaining_for_category'])->toBe(800.0);
});

it('calculates recommended today from the remaining category amount', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user, ['due_date' => '2026-07-10']);
    $food = survivalBudgetCategory($user, ['name' => 'Comida']);
    survivalBudgetMovement($user, $food, ['happened_on' => '2026-06-20', 'amount' => 100]);
    survivalBudgetMovement($user, $food, ['happened_on' => '2026-07-02', 'amount' => 200]);

    $row = collect(survivalBudgetReport($user)['categories'])->firstWhere('category_name', 'Comida');

    expect($row['days_remaining'])->toBe(8)
        ->and($row['recommended_today'])->toBe(100.0);
});

it('generates clear human messages', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);

    $messages = implode(' ', survivalBudgetReport($user)['messages']);

    expect($messages)->toContain('De hoy al')
        ->and($messages)->toContain('te quedan $1,000.00 para vivir')
        ->and($messages)->toContain('gasto maximo sugerido');
});

it('shows the suggested survival budget section on the planner page', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 1000]);
    survivalBudgetIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Presupuesto sugerido', false);
});

it('does not create movements while building the budget', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user);
    survivalBudgetIncome($user);
    $category = survivalBudgetCategory($user);
    survivalBudgetMovement($user, $category, ['amount' => 100]);
    $movementCount = Movement::count();

    survivalBudgetReport($user);

    expect(Movement::count())->toBe($movementCount);
});

it('does not modify planned payments', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user);
    survivalBudgetIncome($user);
    $payment = survivalBudgetPayment($user, ['status' => 'pending', 'paid_amount' => 25]);

    survivalBudgetReport($user);

    expect($payment->fresh()->status)->toBe('pending')
        ->and((float) $payment->fresh()->paid_amount)->toBe(25.0);
});

it('does not modify credit purchases or installments', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user);
    survivalBudgetIncome($user);
    $credit = survivalBudgetCredit($user, ['status' => 'active']);
    $installment = survivalBudgetInstallment($user, $credit, ['status' => 'pending', 'paid_amount' => 10]);
    $creditCount = CreditPurchase::count();
    $installmentCount = CreditInstallment::count();

    survivalBudgetReport($user);

    expect(CreditPurchase::count())->toBe($creditCount)
        ->and(CreditInstallment::count())->toBe($installmentCount)
        ->and($credit->fresh()->status)->toBe('active')
        ->and($installment->fresh()->status)->toBe('pending')
        ->and((float) $installment->fresh()->paid_amount)->toBe(10.0);
});

it('does not break projection recommendations spending limits or credit options', function () {
    $user = User::factory()->create();
    survivalBudgetAccount($user, ['opening_balance' => 400]);
    $category = survivalBudgetCategory($user, ['name' => 'Gasolina']);
    SpendingLimit::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period_type' => 'weekly',
        'limit_amount' => 500,
        'warning_threshold_percent' => 80,
        'is_active' => true,
    ]);
    survivalBudgetCreditOption($user);

    expect(app(FinanceProjectionService::class)->project($user, 7)['days'])->toHaveCount(7)
        ->and(app(FinancePaymentRecommendationService::class)->recommend($user, 7)['available']['safe_today'])->toBe(400.0)
        ->and(app(FinanceSpendingLimitService::class)->analyze($user, 7)['summary']['total_limits'])->toBe(1)
        ->and(app(FinanceCreditOptionSimulationService::class)->simulate($user, 500, 30)['options'])->toHaveCount(1);
});
