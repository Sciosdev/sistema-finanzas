<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditOption;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannerSetting;
use App\Models\Finance\SpendingLimit;
use App\Models\User;
use App\Services\Finance\FinanceCreditOptionSimulationService;
use App\Services\Finance\FinancePaymentRecommendationService;
use App\Services\Finance\FinanceProjectionService;
use App\Services\Finance\FinanceSpendingLimitService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function creditOptionAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 1000,
        'payment_day' => null,
        'is_active' => true,
    ], $attributes));
}

function creditOptionCategory(User $user, array $attributes = []): Category
{
    return Category::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Gasolina '.uniqid(),
        'type' => 'expense',
        'is_active' => true,
    ], $attributes));
}

function creditOptionFor(User $user, array $attributes = []): CreditOption
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

function creditOptionSimulation(User $user, float $amount = 1000, int $horizon = 30, string $strategy = 'balanced'): array
{
    return app(FinanceCreditOptionSimulationService::class)->simulate($user, $amount, $horizon, $strategy);
}

it('calculates total percent cost correctly', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['name' => 'NU', 'cost_type' => 'total_percent', 'cost_percent' => 3]);

    $option = creditOptionSimulation($user)['options'][0];

    expect($option['repayment_total'])->toBe(1030.0)
        ->and($option['total_cost'])->toBe(30.0)
        ->and($option['cost_percent_effective'])->toBe(3.0);
});

it('calculates fixed fee cost correctly', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['name' => 'BBVA', 'cost_type' => 'fixed_fee', 'cost_percent' => 0, 'fixed_fee' => 80]);

    $option = creditOptionSimulation($user)['options'][0];

    expect($option['repayment_total'])->toBe(1080.0)
        ->and($option['total_cost'])->toBe(80.0);
});

it('calculates percent plus fee cost correctly', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['name' => 'DIDI', 'cost_type' => 'percent_plus_fee', 'cost_percent' => 10, 'fixed_fee' => 50]);

    $option = creditOptionSimulation($user)['options'][0];

    expect($option['repayment_total'])->toBe(1150.0)
        ->and($option['total_cost'])->toBe(150.0)
        ->and($option['cost_percent_effective'])->toBe(15.0);
});

it('splits repayment total into installments and adjusts the last one', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['cost_percent' => 10, 'term_months' => 3]);

    $installments = creditOptionSimulation($user)['options'][0]['installments'];

    expect(array_column($installments, 'amount'))->toBe([366.67, 366.67, 366.66])
        ->and(round(array_sum(array_column($installments, 'amount')), 2))->toBe(1100.0);
});

it('calculates the first payment day using option payment day', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['payment_day' => 20]);

    expect(creditOptionSimulation($user)['options'][0]['first_payment_date'])->toBe('2026-07-20');
});

it('uses account payment day when option payment day is null', function () {
    $user = User::factory()->create();
    $account = creditOptionAccount($user, ['payment_day' => 22]);
    creditOptionFor($user, ['account_id' => $account->id, 'payment_day' => null]);

    expect(creditOptionSimulation($user)['options'][0]['first_payment_date'])->toBe('2026-07-22');
});

it('uses day 15 when no payment day exists', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['payment_day' => null]);

    expect(creditOptionSimulation($user)['options'][0]['first_payment_date'])->toBe('2026-07-15');
});

it('marks an option unavailable when amount exceeds available amount', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['available_amount' => 500]);

    $option = creditOptionSimulation($user)['options'][0];

    expect($option['available'])->toBeFalse()
        ->and($option['status'])->toBe('unavailable')
        ->and($option['unavailable_reason'])->toBe('el monto solicitado supera el disponible');
});

it('marks an option unavailable when amount is below minimum amount', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['min_amount' => 1500]);

    $option = creditOptionSimulation($user)['options'][0];

    expect($option['available'])->toBeFalse()
        ->and($option['unavailable_reason'])->toBe('el monto solicitado es menor al mínimo permitido');
});

it('simulates receiving amount on day one and subtracting future installments', function () {
    $user = User::factory()->create();
    creditOptionAccount($user, ['opening_balance' => 0]);
    creditOptionFor($user, [
        'cost_percent' => 0,
        'term_months' => 2,
        'payment_day' => 20,
    ]);

    $simulation = creditOptionSimulation($user, 1000, 7)['options'][0]['simulation'];

    expect($simulation['end_safe'])->toBe(500.0)
        ->and($simulation['end_projected'])->toBe(500.0)
        ->and($simulation['min_projected'])->toBe(500.0);
});

it('calculates the cheapest option', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    $nu = creditOptionFor($user, ['name' => 'NU', 'cost_percent' => 3]);
    creditOptionFor($user, ['name' => 'BBVA', 'cost_percent' => 8]);

    expect(creditOptionSimulation($user)['ranking']['cheapest_option_id'])->toBe($nu->id);
});

it('calculates the lowest monthly option', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['name' => 'NU', 'cost_percent' => 3, 'term_months' => 1]);
    $didi = creditOptionFor($user, ['name' => 'DIDI', 'cost_percent' => 20, 'term_months' => 3]);

    expect(creditOptionSimulation($user)['ranking']['lowest_monthly_option_id'])->toBe($didi->id);
});

it('calculates the safest flow option', function () {
    $user = User::factory()->create();
    creditOptionAccount($user, ['opening_balance' => 0]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);
    creditOptionFor($user, ['name' => 'Pago fuerte', 'cost_percent' => 0, 'term_months' => 1, 'payment_day' => 15]);
    $soft = creditOptionFor($user, ['name' => 'Pago suave', 'cost_percent' => 0, 'term_months' => 3, 'payment_day' => 15]);

    expect(creditOptionSimulation($user)['ranking']['safest_flow_option_id'])->toBe($soft->id);
});

it('calculates the recommended option with balanced strategy', function () {
    $user = User::factory()->create();
    creditOptionAccount($user, ['opening_balance' => 0]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);
    creditOptionFor($user, ['name' => 'Barata pesada', 'cost_percent' => 0, 'term_months' => 1, 'payment_day' => 15]);
    $balanced = creditOptionFor($user, ['name' => 'Balanceada', 'cost_percent' => 20, 'term_months' => 3, 'payment_day' => 15]);

    expect(creditOptionSimulation($user, 1000, 30, 'balanced')['ranking']['recommended_option_id'])->toBe($balanced->id);
});

it('does not show options from another user', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user, ['name' => 'NU']);

    $other = User::factory()->create();
    creditOptionAccount($other);
    creditOptionFor($other, ['name' => 'Opción ajena']);

    $payload = json_encode(creditOptionSimulation($user));

    expect(str_contains($payload, 'Opción ajena'))->toBeFalse()
        ->and(str_contains($payload, 'NU'))->toBeTrue();
});

it('does not create movements while simulating', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user);

    creditOptionSimulation($user);

    expect(Movement::count())->toBe(0);
});

it('does not create real credit purchases or installments while simulating', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user);

    creditOptionSimulation($user);

    expect(CreditPurchase::count())->toBe(0)
        ->and(CreditInstallment::count())->toBe(0);
});

it('does not break finance projection with credit options configured', function () {
    $user = User::factory()->create();
    creditOptionAccount($user);
    creditOptionFor($user);

    expect(app(FinanceProjectionService::class)->project($user, 7)['days'])->toHaveCount(7);
});

it('does not break finance payment recommendations with credit options configured', function () {
    $user = User::factory()->create();
    creditOptionAccount($user, ['opening_balance' => 400]);
    creditOptionFor($user);

    expect(app(FinancePaymentRecommendationService::class)->recommend($user, 7)['available']['safe_today'])->toBe(400.0);
});

it('does not break finance spending limits with credit options configured', function () {
    $user = User::factory()->create();
    creditOptionAccount($user, ['opening_balance' => 400]);
    creditOptionFor($user);
    $category = creditOptionCategory($user);
    SpendingLimit::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period_type' => 'weekly',
        'limit_amount' => 500,
        'warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    expect(app(FinanceSpendingLimitService::class)->analyze($user, 7)['summary']['total_limits'])->toBe(1);
});
