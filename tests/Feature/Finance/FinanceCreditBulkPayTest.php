<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-10 09:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function bulkPayCredit(User $user, Account $account, string $name, int $months = 2): CreditPurchase
{
    return CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-20',
        'name' => $name,
        'total_amount' => 1000,
        'months' => $months,
        'first_due_month' => '2026-07-01',
        'due_day' => 25,
        'account_id' => $account->id,
        'status' => 'active',
    ]);
}

function bulkPayInstallment(User $user, CreditPurchase $credit, array $attributes): CreditInstallment
{
    return CreditInstallment::create(array_merge([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-25',
        'installment_number' => 1,
        'amount' => 500,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

it('bulk pays all current month installments of a creditor and creates movements', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $mpw = Account::where('user_id', $user->id)->where('name', 'MPW')->firstOrFail();

    $nuCredit = bulkPayCredit($user, $nu, 'NU compra');
    $nuCurrent = bulkPayInstallment($user, $nuCredit, ['installment_number' => 1, 'amount' => 500, 'period_month' => '2026-07-01', 'due_date' => '2026-07-25']);
    $nuNext = bulkPayInstallment($user, $nuCredit, ['installment_number' => 2, 'amount' => 500, 'period_month' => '2026-08-01', 'due_date' => '2026-08-25']);

    $mpwCredit = bulkPayCredit($user, $mpw, 'MPW compra', 1);
    $mpwCurrent = bulkPayInstallment($user, $mpwCredit, ['installment_number' => 1, 'amount' => 300, 'period_month' => '2026-07-01', 'due_date' => '2026-07-27']);

    $movementsBefore = Movement::count();

    $this->actingAs($user)
        ->post(route('finance.credits.creditors.pay-month'), ['account_id' => $nu->id, 'creditor_name' => 'NU'])
        ->assertRedirect();

    expect($nuCurrent->fresh()->status)->toBe('paid')
        ->and((float) $nuCurrent->fresh()->paid_amount)->toBe(500.0)
        ->and($nuCurrent->fresh()->movement_id)->not->toBeNull()
        // La mensualidad del mes siguiente NO se toca.
        ->and($nuNext->fresh()->status)->toBe('pending')
        // El otro acreedor (MPW) NO se toca.
        ->and($mpwCurrent->fresh()->status)->toBe('pending')
        // Se creó exactamente un movimiento (egreso) por la mensualidad de NU.
        ->and(Movement::count())->toBe($movementsBefore + 1);

    $movement = Movement::where('source', 'credit_installment')->latest('id')->first();
    expect((float) $movement->amount)->toBe(500.0)
        ->and($movement->account_id)->toBe($nu->id)
        ->and($movement->movement_type)->toBe('expense');
});

it('is idempotent: paying again does not create duplicate movements', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $credit = bulkPayCredit($user, $nu, 'NU compra', 1);
    bulkPayInstallment($user, $credit, ['amount' => 500]);

    $this->actingAs($user)->post(route('finance.credits.creditors.pay-month'), ['account_id' => $nu->id]);
    $afterFirst = Movement::count();

    $this->actingAs($user)
        ->post(route('finance.credits.creditors.pay-month'), ['account_id' => $nu->id])
        ->assertSessionHas('warning');

    expect(Movement::count())->toBe($afterFirst);
});

it('does not touch another users credit when paying a creditor month', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $ownCredit = bulkPayCredit($user, $nu, 'NU propia', 1);
    bulkPayInstallment($user, $ownCredit, ['amount' => 500]);

    $other = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($other);
    $otherNu = Account::where('user_id', $other->id)->where('name', 'NU')->firstOrFail();
    $otherCredit = bulkPayCredit($other, $otherNu, 'NU ajena', 1);
    $otherInstallment = bulkPayInstallment($other, $otherCredit, ['amount' => 999]);

    $this->actingAs($user)->post(route('finance.credits.creditors.pay-month'), ['account_id' => $nu->id]);

    expect($otherInstallment->fresh()->status)->toBe('pending')
        ->and((float) $otherInstallment->fresh()->paid_amount)->toBe(0.0);
});
