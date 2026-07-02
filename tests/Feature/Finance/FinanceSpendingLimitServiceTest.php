<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\SpendingLimit;
use App\Models\User;
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

function spendingLimitAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 1000,
        'is_active' => true,
    ], $attributes));
}

function spendingLimitCategory(User $user, array $attributes = []): Category
{
    return Category::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Gasolina '.uniqid(),
        'type' => 'expense',
        'group' => 'Transporte',
        'is_active' => true,
    ], $attributes));
}

function spendingLimitFor(User $user, Category $category, array $attributes = []): SpendingLimit
{
    return SpendingLimit::create(array_merge([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period_type' => 'weekly',
        'limit_amount' => 500,
        'warning_threshold_percent' => 80,
        'is_active' => true,
    ], $attributes));
}

function spendingLimitMovement(User $user, Category $category, array $attributes = []): Movement
{
    return Movement::create(array_merge([
        'user_id' => $user->id,
        'happened_on' => '2026-07-15',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Gasto de prueba',
        'category_id' => $category->id,
        'source' => 'manual',
    ], $attributes));
}

function spendingLimitReport(User $user): array
{
    return app(FinanceSpendingLimitService::class)->analyze($user, 7);
}

it('calculates spent amount for the daily period', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['period_type' => 'daily', 'limit_amount' => 500]);

    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-15', 'amount' => 120]);
    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-14', 'amount' => 80]);

    $limit = spendingLimitReport($user)['limits'][0];

    expect($limit['spent_amount'])->toBe(120.0)
        ->and($limit['period_start'])->toBe('2026-07-15')
        ->and($limit['period_end'])->toBe('2026-07-15');
});

it('calculates spent amount for the weekly period', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['period_type' => 'weekly']);

    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-13', 'amount' => 100]);
    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-15', 'amount' => 200]);
    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-12', 'amount' => 50]);

    $limit = spendingLimitReport($user)['limits'][0];

    expect($limit['spent_amount'])->toBe(300.0)
        ->and($limit['period_start'])->toBe('2026-07-13')
        ->and($limit['period_end'])->toBe('2026-07-19');
});

it('calculates spent amount for the monthly period', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['period_type' => 'monthly']);

    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-01', 'amount' => 100]);
    spendingLimitMovement($user, $category, ['happened_on' => '2026-07-15', 'amount' => 200]);
    spendingLimitMovement($user, $category, ['happened_on' => '2026-06-30', 'amount' => 50]);

    $limit = spendingLimitReport($user)['limits'][0];

    expect($limit['spent_amount'])->toBe(300.0)
        ->and($limit['period_start'])->toBe('2026-07-01')
        ->and($limit['period_end'])->toBe('2026-07-31');
});

it('uses only movements from the authenticated user', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category);
    spendingLimitMovement($user, $category, ['amount' => 100]);

    $other = User::factory()->create();
    spendingLimitMovement($other, $category, ['amount' => 999]);

    expect(spendingLimitReport($user)['limits'][0]['spent_amount'])->toBe(100.0);
});

it('uses only expense movements', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category);

    spendingLimitMovement($user, $category, ['movement_type' => 'expense', 'amount' => 100]);
    spendingLimitMovement($user, $category, ['movement_type' => 'income', 'amount' => 200]);
    spendingLimitMovement($user, $category, ['movement_type' => 'yield', 'amount' => 300]);

    expect(spendingLimitReport($user)['limits'][0]['spent_amount'])->toBe(100.0);
});

it('does not count movements from another category', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user, ['name' => 'Gasolina']);
    $otherCategory = spendingLimitCategory($user, ['name' => 'Comida']);
    spendingLimitFor($user, $category);

    spendingLimitMovement($user, $category, ['amount' => 100]);
    spendingLimitMovement($user, $otherCategory, ['amount' => 300]);

    expect(spendingLimitReport($user)['limits'][0]['spent_amount'])->toBe(100.0);
});

it('calculates remaining amount correctly', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['limit_amount' => 700]);
    spendingLimitMovement($user, $category, ['amount' => 620]);

    expect(spendingLimitReport($user)['limits'][0]['remaining_amount'])->toBe(80.0);
});

it('calculates used percent correctly', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['limit_amount' => 700]);
    spendingLimitMovement($user, $category, ['amount' => 620]);

    expect(spendingLimitReport($user)['limits'][0]['used_percent'])->toBe(88.57);
});

it('recommended today never exceeds remaining amount divided by remaining days', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user, ['opening_balance' => 1000]);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['period_type' => 'weekly', 'limit_amount' => 500]);
    spendingLimitMovement($user, $category, ['amount' => 250]);

    $limit = spendingLimitReport($user)['limits'][0];

    expect($limit['remaining_days'])->toBe(5)
        ->and($limit['daily_allowance_by_limit'])->toBe(50.0)
        ->and($limit['recommended_today'])->toBe(50.0);
});

it('recommended today never exceeds available safe today', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user, ['opening_balance' => 40]);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['period_type' => 'weekly', 'limit_amount' => 500]);

    $report = spendingLimitReport($user);

    expect($report['available_safe_today'])->toBe(40.0)
        ->and($report['limits'][0]['daily_allowance_by_limit'])->toBe(100.0)
        ->and($report['limits'][0]['recommended_today'])->toBe(40.0);
});

it('marks exceeded when the limit is exceeded', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['limit_amount' => 100]);
    spendingLimitMovement($user, $category, ['amount' => 120]);

    expect(spendingLimitReport($user)['limits'][0]['status'])->toBe('exceeded');
});

it('marks warning when used percent reaches the threshold', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['limit_amount' => 100, 'warning_threshold_percent' => 80]);
    spendingLimitMovement($user, $category, ['amount' => 80]);

    expect(spendingLimitReport($user)['limits'][0]['status'])->toBe('warning');
});

it('marks blocked when available safe today is zero', function () {
    $user = User::factory()->create();
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category, ['limit_amount' => 100]);
    spendingLimitMovement($user, $category, ['amount' => 50]);

    $limit = spendingLimitReport($user)['limits'][0];

    expect($limit['recommended_today'])->toBe(0.0)
        ->and($limit['status'])->toBe('blocked');
});

it('does not create movements or change statuses', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    $limit = spendingLimitFor($user, $category);
    spendingLimitMovement($user, $category, ['amount' => 50]);
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Laptop',
        'total_amount' => 1200,
        'months' => 4,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
    ]);
    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-20',
        'name' => 'Internet',
        'amount' => 300,
        'status' => 'pending',
    ]);
    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-20',
        'name' => 'Cliente',
        'amount' => 500,
        'status' => 'partial',
    ]);
    $installment = CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-20',
        'installment_number' => 1,
        'amount' => 300,
        'status' => 'pending',
    ]);

    $movementCount = Movement::count();

    spendingLimitReport($user);

    expect(Movement::count())->toBe($movementCount)
        ->and($limit->fresh()->is_active)->toBeTrue()
        ->and($payment->fresh()->status)->toBe('pending')
        ->and($income->fresh()->status)->toBe('partial')
        ->and($installment->fresh()->status)->toBe('pending');
});

it('does not break finance projection with spending limits configured', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category);

    expect(app(FinanceProjectionService::class)->project($user, 7)['days'])->toHaveCount(7);
});

it('does not break finance payment recommendations with spending limits configured', function () {
    $user = User::factory()->create();
    spendingLimitAccount($user, ['opening_balance' => 400]);
    $category = spendingLimitCategory($user);
    spendingLimitFor($user, $category);

    $recommendations = app(FinancePaymentRecommendationService::class)->recommend($user, 7);

    expect($recommendations['available']['safe_today'])->toBe(400.0);
});
