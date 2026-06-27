<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function cycleUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('computes the due date paying next month when payment day is before cutoff', function () {
    $account = new Account(['statement_day' => 31, 'payment_day' => 15]);

    // Compra el 27, corte 31 -> entra este mes; pago 15 (<=31) -> mes siguiente.
    expect($account->firstDueDateFor(Carbon::parse('2026-06-27'))->toDateString())->toBe('2026-07-15');
});

it('computes the due date paying same month when payment day is after cutoff', function () {
    $account = new Account(['statement_day' => 5, 'payment_day' => 25]);

    expect($account->firstDueDateFor(Carbon::parse('2026-06-03'))->toDateString())->toBe('2026-06-25')
        ->and($account->firstDueDateFor(Carbon::parse('2026-06-10'))->toDateString())->toBe('2026-07-25');
});

it('returns null when the card has no cycle configured', function () {
    $account = new Account(['statement_day' => null, 'payment_day' => 15]);

    expect($account->firstDueDateFor(Carbon::parse('2026-06-27')))->toBeNull();
});

it('persists the credit cycle fields on an account', function () {
    $user = cycleUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $this->actingAs($user)
        ->put(route('finance.accounts.update', $nu), [
            'name' => 'NU',
            'type' => 'card',
            'credit_limit' => 20000,
            'statement_day' => 31,
            'payment_day' => 15,
        ])
        ->assertRedirect();

    $nu->refresh();

    expect((float) $nu->credit_limit)->toBe(20000.0)
        ->and($nu->statement_day)->toBe(31)
        ->and($nu->payment_day)->toBe(15);
});

it('auto sets the credit due date from the card cycle when creating a credit', function () {
    $user = cycleUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update(['statement_day' => 31, 'payment_day' => 15]);

    $this->actingAs($user)
        ->post(route('finance.credits.store'), [
            'purchase_date' => '2026-06-27',
            'name' => 'Compra NU',
            'amount_mode' => 'total',
            'total_amount' => 100,
            'months' => 1,
            // Mes "equivocado" a propósito: el ciclo de la tarjeta debe sobrescribirlo.
            'first_due_month' => '2026-09',
            'due_day' => 5,
            'account_id' => $nu->id,
        ])
        ->assertRedirect();

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Compra NU')->firstOrFail();
    $installment = $credit->installments()->orderBy('installment_number')->firstOrFail();

    expect($credit->first_due_month->format('Y-m'))->toBe('2026-07')
        ->and($installment->due_date->toDateString())->toBe('2026-07-15');
});

it('keeps the manual due date when the card has no cycle', function () {
    $user = cycleUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.credits.store'), [
            'purchase_date' => '2026-06-27',
            'name' => 'Compra manual',
            'amount_mode' => 'total',
            'total_amount' => 100,
            'months' => 1,
            'first_due_month' => '2026-08',
            'due_day' => 10,
            'account_id' => $nu->id,
        ])
        ->assertRedirect();

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Compra manual')->firstOrFail();

    expect($credit->first_due_month->format('Y-m'))->toBe('2026-08')
        ->and($credit->due_day)->toBe(10);
});
