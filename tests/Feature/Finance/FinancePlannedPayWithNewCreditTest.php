<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function plannedCreditUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function makePlannedPayment(User $user, array $overrides = []): PlannedPayment
{
    $category = Category::where('user_id', $user->id)->where('name', 'Casa')->firstOrFail();

    return PlannedPayment::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-27',
        'name' => 'Youtube Premium',
        'amount' => 159,
        'status' => 'pending',
        'category_id' => $category->id,
    ], $overrides));
}

it('pays a planned payment by creating a new credit in one action', function () {
    $user = plannedCreditUser();
    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $payment = makePlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-new', $payment), [
            'account_id' => $card->id,
            'months' => 1,
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-06']))
        ->assertSessionHas('success');

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Youtube Premium')->firstOrFail();

    expect((float) $credit->total_amount)->toBe(159.0)
        ->and($credit->months)->toBe(1)
        ->and($credit->account_id)->toBe($card->id)
        ->and($credit->installments()->count())->toBe(1);

    $payment->refresh();

    expect($payment->status)->toBe('paid')
        ->and((bool) $payment->is_credit)->toBeTrue()
        ->and($payment->credit_purchase_id)->toBe($credit->id)
        ->and($payment->movement_id)->toBeNull();
});

it('does not create an expense movement when paying with a new credit', function () {
    $user = plannedCreditUser();
    $payment = makePlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-new', $payment), ['months' => 1]);

    expect(Movement::where('user_id', $user->id)->where('source', 'planned_payment')->count())->toBe(0);
});

it('splits the credit into the requested number of installments', function () {
    $user = plannedCreditUser();
    $payment = makePlannedPayment($user, ['amount' => 300]);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-new', $payment), ['months' => 3]);

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Youtube Premium')->firstOrFail();

    expect($credit->installments()->count())->toBe(3)
        ->and(round((float) $credit->installments()->sum('amount'), 2))->toBe(300.0);
});

it('does not let a user pay another users planned payment with a new credit', function () {
    $owner = plannedCreditUser();
    $other = plannedCreditUser();
    $payment = makePlannedPayment($other);

    $this->actingAs($owner)
        ->post(route('finance.planned.credit-new', $payment), ['months' => 1])
        ->assertForbidden();

    expect(CreditPurchase::where('user_id', $owner->id)->count())->toBe(0);
    expect($payment->fresh()->status)->toBe('pending');
});

it('refuses to pay with a new credit if a real movement is already linked', function () {
    $user = plannedCreditUser();
    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 159,
        'description' => 'Youtube ya pagado',
        'source' => 'manual',
    ]);
    $payment = makePlannedPayment($user, ['movement_id' => $movement->id]);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-new', $payment), ['months' => 1])
        ->assertSessionHas('error');

    expect(CreditPurchase::where('user_id', $user->id)->count())->toBe(0);
});
