<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
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

function plannedRevertUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function makeRevertPlannedPayment(User $user, array $overrides = []): PlannedPayment
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

it('reverts a payment made with a new credit and deletes the generated credit', function () {
    $user = plannedRevertUser();
    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $payment = makeRevertPlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-new', $payment), [
            'account_id' => $card->id,
            'months' => 3,
        ])
        ->assertSessionHas('success');

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Youtube Premium')->firstOrFail();
    expect($credit->installments()->count())->toBe(3);

    $this->actingAs($user)
        ->post(route('finance.planned.revert', $payment))
        ->assertSessionHas('success');

    $payment->refresh();

    expect($payment->status)->toBe('pending')
        ->and((float) $payment->paid_amount)->toBe(0.0)
        ->and($payment->paid_on)->toBeNull()
        ->and($payment->credit_purchase_id)->toBeNull()
        ->and((bool) $payment->is_credit)->toBeFalse();

    expect(CreditPurchase::where('user_id', $user->id)->count())->toBe(0)
        ->and(CreditInstallment::where('user_id', $user->id)->count())->toBe(0);
});

it('keeps a generated credit that already has payments and only unlinks it', function () {
    $user = plannedRevertUser();
    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $payment = makeRevertPlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-new', $payment), [
            'account_id' => $card->id,
            'months' => 2,
        ]);

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Youtube Premium')->firstOrFail();
    $credit->installments()->orderBy('installment_number')->first()->update(['paid_amount' => 50]);

    $this->actingAs($user)
        ->post(route('finance.planned.revert', $payment))
        ->assertSessionHas('success');

    $payment->refresh();

    expect($payment->status)->toBe('pending')
        ->and($payment->credit_purchase_id)->toBeNull();

    expect(CreditPurchase::where('user_id', $user->id)->count())->toBe(1)
        ->and($credit->installments()->count())->toBe(2);
});

it('reverts a payment linked to an existing credit without deleting the credit', function () {
    $user = plannedRevertUser();
    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $payment = makeRevertPlannedPayment($user);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-10',
        'name' => 'Credito previo',
        'total_amount' => 159,
        'months' => 1,
        'first_due_month' => '2026-07-01',
        'due_day' => 10,
        'account_id' => $card->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-paid', $payment), [
            'account_id' => $card->id,
            'credit_purchase_id' => $credit->id,
        ]);

    expect($payment->fresh()->status)->toBe('paid');

    $this->actingAs($user)
        ->post(route('finance.planned.revert', $payment))
        ->assertSessionHas('success');

    $payment->refresh();

    expect($payment->status)->toBe('pending')
        ->and($payment->credit_purchase_id)->toBeNull()
        ->and((bool) $payment->is_credit)->toBeFalse();

    expect(CreditPurchase::where('user_id', $user->id)->count())->toBe(1);
});

it('reverts a paid payment and deletes the auto-generated expense movement', function () {
    $user = plannedRevertUser();
    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $payment = makeRevertPlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.paid', $payment), [
            'paid_on' => '2026-06-14',
            'account_id' => $card->id,
        ]);

    expect(Movement::where('user_id', $user->id)->where('source', 'planned_payment')->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('finance.planned.revert', $payment))
        ->assertSessionHas('success');

    $payment->refresh();

    expect($payment->status)->toBe('pending')
        ->and($payment->movement_id)->toBeNull()
        ->and(Movement::where('user_id', $user->id)->where('source', 'planned_payment')->count())->toBe(0);
});

it('reverts a payment linked to a real movement without deleting the movement', function () {
    $user = plannedRevertUser();
    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 159,
        'description' => 'Youtube ya pagado',
        'source' => 'manual',
    ]);
    $payment = makeRevertPlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.link-movement', $payment), [
            'movement_id' => $movement->id,
        ]);

    expect($payment->fresh()->status)->toBe('paid');

    $this->actingAs($user)
        ->post(route('finance.planned.revert', $payment))
        ->assertSessionHas('success');

    $payment->refresh();

    expect($payment->status)->toBe('pending')
        ->and($payment->movement_id)->toBeNull();

    expect(Movement::where('user_id', $user->id)->count())->toBe(1);
});

it('does nothing when the payment is still pending', function () {
    $user = plannedRevertUser();
    $payment = makeRevertPlannedPayment($user);

    $this->actingAs($user)
        ->post(route('finance.planned.revert', $payment))
        ->assertSessionHas('error');

    expect($payment->fresh()->status)->toBe('pending');
});

it('does not let a user revert another users planned payment', function () {
    $owner = plannedRevertUser();
    $other = plannedRevertUser();
    $payment = makeRevertPlannedPayment($other, ['status' => 'paid', 'paid_amount' => 159]);

    $this->actingAs($owner)
        ->post(route('finance.planned.revert', $payment))
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe('paid');
});
