<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditFreePayment;
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

    $result = decisionPlanReport($user);
    $buffer = $result['buffer'];

    expect($buffer['manual_buffer_reference'])->toBe(3000.0)
        ->and($buffer['buffer_used'])->toBe(500.0)
        ->and($result['savings_guidance']['buffer_used'])->toBe(500.0);
});

it('shows the manual buffer only as a reference', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2500]);

    $result = decisionPlanReport($user);
    $buffer = $result['buffer'];

    expect($buffer['manual_buffer_reference'])->toBe(2500.0)
        ->and($buffer['recommended_min_buffer'])->not->toBe(2500.0)
        ->and($result['savings_guidance']['recommended_normal_buffer'])->toBe($buffer['recommended_min_buffer']);
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

it('does not recommend savings before the recommended normal buffer is covered', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 2000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);

    $guidance = decisionPlanReport($user)['savings_guidance'];

    expect($guidance['current_buffer_gap'])->toBe(450.0)
        ->and($guidance['free_savings_available'])->toBe(0.0)
        ->and($guidance['should_save'])->toBeFalse()
        ->and($guidance['message'])->toContain('Primero completa tu colchón recomendado normal');
});

it('recommends strengthening the ideal buffer before free savings', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 2600]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);

    $guidance = decisionPlanReport($user)['savings_guidance'];

    expect($guidance['current_buffer_gap'])->toBe(0.0)
        ->and($guidance['ideal_buffer_gap'])->toBe(550.0)
        ->and($guidance['free_savings_available'])->toBe(0.0)
        ->and($guidance['should_save'])->toBeFalse()
        ->and($guidance['message'])->toContain('acercarte al colchón ideal');
});

it('shows free savings only after the ideal buffer is covered', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 4000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);

    $result = decisionPlanReport($user);
    $guidance = $result['savings_guidance'];

    expect($guidance['current_buffer_gap'])->toBe(0.0)
        ->and($guidance['free_savings_available'])->toBe(1000.0)
        ->and($guidance['free_savings_available'])->toBeLessThanOrEqual($result['money_plan']['savings_possible'])
        ->and($guidance['should_save'])->toBeTrue()
        ->and($guidance['message'])->toContain('Puedes ahorrar $1,000.00')
        ->and($result['actions']['save'][0]['name'])->toBe('Ahorro libre');
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

it('does not recommend credit payoff when cash is needed for plan buffer and living money', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 2000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'NU']);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 1000]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];

    expect($strategy['available_for_credit_payoff_now'])->toBe(0.0)
        ->and($strategy['horizon_credit_due'])->toBe(1000.0)
        ->and($strategy['recommended_actions'])->toBeEmpty()
        ->and($strategy['message'])->toContain('conserva efectivo para pagos');
});

it('calculates available cash for credit payoff after required reserves', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'NU']);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 1000]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];

    expect($strategy['available_for_credit_payoff_now'])->toBe(2550.0);
});

it('separates horizon balance from future balance per account group', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'BBVA']);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 100, 'paid_amount' => 25]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-08-20', 'period_month' => '2026-08-01', 'installment_number' => 2, 'amount' => 200]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-09-20', 'period_month' => '2026-09-01', 'installment_number' => 3, 'amount' => 300, 'paid_amount' => 300, 'status' => 'paid']);

    $group = collect(decisionPlanReport($user)['credit_payoff_strategy']['account_groups'])
        ->firstWhere('account_name', 'Sin cuenta');

    expect($group['total_pending_reference'])->toBe(275.0)
        ->and($group['horizon_balance'])->toBe(75.0)
        ->and($group['future_balance_reference'])->toBe(200.0)
        ->and($group['installments_pending_count'])->toBe(2);
});

it('does not include installments outside the horizon in horizon_balance', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $nu = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'Google Chat GPT', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-27', 'amount' => 395]);
    $future = decisionPlanCredit($user, ['name' => 'Amazon Teclado', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $future, ['due_date' => '2026-08-27', 'period_month' => '2026-08-01', 'amount' => 398.29]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];
    $group = collect($strategy['account_groups'])->firstWhere('account_name', 'NU');

    expect($strategy['horizon_credit_due'])->toBe(395.0)
        ->and($strategy['future_credit_balance_reference'])->toBe(398.29)
        ->and($group['horizon_balance'])->toBe(395.0)
        ->and($group['future_balance_reference'])->toBe(398.29)
        ->and(collect($group['items_in_horizon'])->pluck('credit_name')->all())->toBe(['Google Chat GPT'])
        ->and(collect($group['future_items_reference'])->pluck('credit_name')->all())->toBe(['Amazon Teclado']);
});

it('groups credit installments by account id keeping only horizon items for the recommendation', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $nu = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $amazon = decisionPlanCredit($user, ['name' => 'Amazon Gel', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $amazon, ['due_date' => '2026-07-20', 'amount' => 398.29]);
    $keyboard = decisionPlanCredit($user, ['name' => 'Amazon Teclado', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $keyboard, ['due_date' => '2026-07-21', 'amount' => 1139.10]);

    $group = collect(decisionPlanReport($user)['credit_payoff_strategy']['account_groups'])
        ->firstWhere('account_name', 'NU');

    expect($group['account_id'])->toBe($nu->id)
        ->and($group['horizon_balance'])->toBe(1537.39)
        ->and($group['total_pending_reference'])->toBe(1537.39)
        ->and($group['credits_count'])->toBe(2)
        ->and(collect($group['items_in_horizon'])->pluck('credit_name')->all())->toBe(['Amazon Gel', 'Amazon Teclado']);
});

it('groups credits without account as sin cuenta', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'Sin tarjeta']);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 200]);

    $group = collect(decisionPlanReport($user)['credit_payoff_strategy']['account_groups'])
        ->firstWhere('account_name', 'Sin cuenta');

    expect($group['account_id'])->toBeNull()
        ->and($group['account_name'])->toBe('Sin cuenta')
        ->and($group['items_in_horizon'][0]['credit_name'])->toBe('Sin tarjeta');
});

it('includes account pressure and explanation on horizon credit payoff actions', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $account = decisionPlanAccount($user, ['name' => 'DIDI', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'DIDI', 'account_id' => $account->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-01', 'amount' => 200, 'status' => 'overdue']);

    $action = collect(decisionPlanReport($user)['credit_payoff_strategy']['recommended_actions'])
        ->firstWhere('account_name', 'DIDI');

    expect($action['action'])->toBe('pay_current_horizon_account')
        ->and($action['account_name'])->toBe('DIDI')
        ->and($action['next_due_date'])->toBe('2026-07-01')
        ->and($action['amount'])->toBe(200.0)
        ->and($action['horizon_balance'])->toBe(200.0)
        ->and($action['overdue_amount'])->toBe(200.0)
        ->and($action['due_before_income'])->toBe(200.0)
        ->and($action['pressure_label'])->toBe('Vencido')
        ->and($action['explanation'])->toContain('DIDI')
        ->and($action['items_in_horizon'][0]['credit_name'])->toBe('DIDI');
});

it('includes account pressure and explanation on deferred future balance actions', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 2000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $account = decisionPlanAccount($user, ['name' => 'Onix', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'Compra Onix', 'account_id' => $account->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-09-20', 'period_month' => '2026-09-01', 'amount' => 1000]);

    $action = collect(decisionPlanReport($user)['credit_payoff_strategy']['defer_actions'])
        ->firstWhere('account_name', 'Onix');

    expect($action['account_name'])->toBe('Onix')
        ->and($action['action'])->toBe('defer_future_balance')
        ->and($action['future_balance_reference'])->toBe(1000.0)
        ->and($action['pressure_label'])->toBe('Deuda futura')
        ->and($action['explanation'])->toContain('referencia')
        ->and($action['future_items_reference'][0]['credit_name'])->toBe('Compra Onix');
});

it('includes account pressure and explanation on minimum payment actions', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $account = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'NU - Tablet', 'account_id' => $account->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-07', 'amount' => 300]);

    $action = collect(decisionPlanReport($user)['credit_payoff_strategy']['minimum_payment_actions'])
        ->firstWhere('account_name', 'NU');

    expect($action['account_name'])->toBe('NU')
        ->and($action['action'])->toBe('minimum_payment_account')
        ->and($action['pressure_label'])->toBe('Antes del próximo ingreso')
        ->and($action['explanation'])->toContain('mensualidad mínima')
        ->and($action['items_in_horizon'][0]['credit_name'])->toBe('NU - Tablet');
});

it('uses a period based credit message that does not list every purchase', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 7000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $nu = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);

    foreach (['Amazon Gel', 'Amazon Teclado', 'Envato Elements', 'Google Chat GPT'] as $index => $name) {
        $credit = decisionPlanCredit($user, ['name' => $name, 'account_id' => $nu->id]);
        decisionPlanInstallment($user, $credit, [
            'due_date' => '2026-07-20',
            'amount' => [398.29, 1139.10, 698.84, 395.00][$index],
            'installment_number' => $index + 1,
        ]);
    }

    $message = decisionPlanReport($user)['credit_payoff_strategy']['message'];

    expect($message)->toContain('Para este horizonte necesitas cubrir')
        ->and($message)->toContain('NU')
        ->and($message)->toContain('$2,631.23')
        ->and($message)->not->toContain('Amazon Gel')
        ->and($message)->not->toContain('Amazon Teclado')
        ->and($message)->not->toContain('liquida toda la cuenta');
});

it('does not treat future debt as an obligation to liquidate today', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 7000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $nu = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $now = decisionPlanCredit($user, ['name' => 'Google Chat GPT', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $now, ['due_date' => '2026-07-27', 'amount' => 395]);
    $future = decisionPlanCredit($user, ['name' => 'Amazon Gel', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $future, ['due_date' => '2026-08-27', 'period_month' => '2026-08-01', 'amount' => 4300]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];
    $horizonAction = collect($strategy['recommended_actions'])->firstWhere('action', 'pay_current_horizon_account');

    expect($strategy['horizon_credit_due'])->toBe(395.0)
        ->and($strategy['future_credit_balance_reference'])->toBe(4300.0)
        ->and($horizonAction['amount'])->toBe(395.0)
        ->and($strategy['message'])->not->toContain('liquida toda la cuenta')
        ->and(collect($strategy['recommended_actions'])->pluck('action')->all())->not->toContain('liquidate_account')
        ->and($strategy['message'])->toContain('referencia');
});

it('does not recommend covering the whole account when cash is short for the horizon', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 4500]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $account = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'NU grande', 'account_id' => $account->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 3000]);

    $actions = collect(decisionPlanReport($user)['credit_payoff_strategy']['recommended_actions']);
    $nu = $actions->firstWhere('account_name', 'NU');

    expect($actions->pluck('action')->all())->not->toContain('liquidate_account')
        ->and($nu['action'])->toBe('pay_current_horizon_account')
        ->and($nu['amount'])->toBe(2050.0)
        ->and($nu['covers_full_horizon'])->toBeFalse();
});

it('recommends an optional extra payment when money is left after the horizon', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $nu = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'NU compra', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-10', 'amount' => 200]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-08-20', 'period_month' => '2026-08-01', 'installment_number' => 2, 'amount' => 1000]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];
    $actions = collect($strategy['recommended_actions']);
    $extra = $actions->firstWhere('action', 'optional_extra_payment_account');

    expect($actions->firstWhere('action', 'pay_current_horizon_account')['amount'])->toBe(200.0)
        ->and($strategy['optional_extra_payment'])->toBe(1000.0)
        ->and($extra['account_name'])->toBe('NU')
        ->and($extra['amount'])->toBe(1000.0)
        ->and($extra['explanation'])->toContain('Opcional');
});

it('prioritizes covering the horizon of the account with the most near term pressure', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 6650]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $bbva = decisionPlanAccount($user, ['name' => 'BBVA', 'opening_balance' => 0]);
    $nu = decisionPlanAccount($user, ['name' => 'NU', 'opening_balance' => 0]);
    $didi = decisionPlanAccount($user, ['name' => 'DIDI', 'opening_balance' => 0]);
    $a = decisionPlanCredit($user, ['name' => 'BBVA compra', 'account_id' => $bbva->id]);
    decisionPlanInstallment($user, $a, ['due_date' => '2026-07-27', 'amount' => 1000]);
    $b = decisionPlanCredit($user, ['name' => 'NU compra', 'account_id' => $nu->id]);
    decisionPlanInstallment($user, $b, ['due_date' => '2026-07-07', 'amount' => 2000]);
    $c = decisionPlanCredit($user, ['name' => 'DIDI compra', 'account_id' => $didi->id]);
    decisionPlanInstallment($user, $c, ['due_date' => '2026-07-20', 'amount' => 200]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];
    $horizonActions = collect($strategy['recommended_actions'])->where('action', 'pay_current_horizon_account')->values();

    expect($strategy['available_for_credit_payoff_now'])->toBe(4200.0)
        ->and($horizonActions->pluck('account_name')->all())->toBe(['NU', 'BBVA', 'DIDI'])
        ->and($strategy['horizon_credit_due'])->toBe(3200.0)
        ->and($strategy['recommended_to_pay_now'])->toBe(3200.0);
});

it('does not use buffer or minimum living money for credit payoff', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 2450]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'DIDI']);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 200]);

    $strategy = decisionPlanReport($user)['credit_payoff_strategy'];

    expect($strategy['available_for_credit_payoff_now'])->toBe(0.0)
        ->and($strategy['recommended_actions'])->toBeEmpty();
});

it('ignores credits from another user in credit payoff strategy', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $own = decisionPlanCredit($user, ['name' => 'NU']);
    decisionPlanInstallment($user, $own, ['due_date' => '2026-07-20', 'amount' => 1000]);
    $other = User::factory()->create();
    decisionPlanAccount($other, ['opening_balance' => 5000]);
    $otherCredit = decisionPlanCredit($other, ['name' => 'Credito ajeno']);
    decisionPlanInstallment($other, $otherCredit, ['due_date' => '2026-07-20', 'amount' => 100]);

    $strategyJson = json_encode(decisionPlanReport($user)['credit_payoff_strategy']);

    expect($strategyJson)->toContain('NU')
        ->and($strategyJson)->not->toContain('Credito ajeno');
});

it('does not persist anything while building credit payoff strategy', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user, ['due_date' => '2026-07-15']);
    $credit = decisionPlanCredit($user, ['name' => 'NU']);
    $installment = decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-20', 'amount' => 1000, 'status' => 'pending']);
    $movementCount = Movement::count();
    $freePaymentCount = CreditFreePayment::count();
    $creditCount = CreditPurchase::count();
    $installmentCount = CreditInstallment::count();

    decisionPlanReport($user);

    expect(Movement::count())->toBe($movementCount)
        ->and(CreditFreePayment::count())->toBe($freePaymentCount)
        ->and(CreditPurchase::count())->toBe($creditCount)
        ->and(CreditInstallment::count())->toBe($installmentCount)
        ->and($installment->fresh()->status)->toBe('pending');
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

it('shows the credit payoff strategy section on the projection page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Estrategia de créditos del periodo', false)
        ->assertSee('Monto a cubrir en el horizonte', false)
        ->assertSee('Deuda futura como referencia', false)
        ->assertSee('Esto es solo una recomendación. No se creó ningún movimiento ni se marcó ningún crédito como pagado.', false);
});

it('shows account and explanation details on the credit payoff strategy page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 5000]);
    decisionPlanIncome($user);
    $account = decisionPlanAccount($user, ['name' => 'DIDI', 'opening_balance' => 0]);
    $credit = decisionPlanCredit($user, ['name' => 'DIDI', 'account_id' => $account->id]);
    decisionPlanInstallment($user, $credit, ['due_date' => '2026-07-01', 'amount' => 200, 'status' => 'overdue']);

    $this->actingAs($user)
        ->get(route('finance.projection.index'))
        ->assertOk()
        ->assertSee('Cuentas a cubrir en este periodo', false)
        ->assertSee('Cuenta: DIDI', false)
        ->assertSee('Próximo vencimiento: 2026-07-01', false)
        ->assertSee('Vencido', false)
        ->assertSee('Mensualidades incluidas en este periodo:', false)
        ->assertSee('DIDI $200.00', false)
        ->assertSee('Motivo: Cubre las mensualidades de DIDI', false);
});

it('shows the recommended buffer section on the projection page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Colchón recomendado normal', false)
        ->assertSee('Colchón ideal', false)
        ->assertSee('Meta más cómoda, no obligatoria.', false)
        ->assertSee('Colchón protegido en este plan', false);
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
        ->assertSee('Colchón manual anterior / referencia', false);
});

it('shows the money priority block on the projection page', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user);
    decisionPlanIncome($user);

    $response = $this->actingAs($user)->get(route('finance.projection.index'));

    $response->assertOk()
        ->assertSee('Prioridad del dinero:', false)
        ->assertSee('Cobros automáticos', false)
        ->assertSee('Ahorro libre', false);
});

it('shows free savings as a card label only when it applies', function () {
    $user = User::factory()->create();
    decisionPlanAccount($user, ['opening_balance' => 2600]);
    decisionPlanIncome($user);

    $this->actingAs($user)
        ->get(route('finance.projection.index'))
        ->assertOk()
        ->assertSee('<p class="text-muted small mb-1">Ahorro sugerido</p>', false)
        ->assertDontSee('<p class="text-muted small mb-1">Ahorro libre</p>', false);

    $userWithSurplus = User::factory()->create();
    decisionPlanAccount($userWithSurplus, ['opening_balance' => 4000]);
    decisionPlanIncome($userWithSurplus);

    $this->actingAs($userWithSurplus)
        ->get(route('finance.projection.index'))
        ->assertOk()
        ->assertSee('<p class="text-muted small mb-1">Ahorro libre</p>', false)
        ->assertSee('Puedes ahorrar $1,000.00 sin afectar pagos, vida diaria ni colchón.', false);
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
