<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCreditForFreePaymentTest(User $user): CreditPurchase
{
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'MPW')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Crédito / tarjeta')->firstOrFail();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-22',
        'name' => 'MPW prueba',
        'total_amount' => 3220,
        'months' => 2,
        'first_due_month' => '2026-07-01',
        'due_day' => 27,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    foreach ([1, 2] as $number) {
        CreditInstallment::create([
            'user_id' => $user->id,
            'credit_purchase_id' => $credit->id,
            'period_month' => "2026-0" . (6 + $number) . "-01",
            'due_date' => "2026-0" . (6 + $number) . "-27",
            'installment_number' => $number,
            'amount' => 1610,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);
    }

    return $credit;
}

it('registers a free credit payment without marking installments as paid', function () {
    $user = User::factory()->create();
    $credit = createCreditForFreePaymentTest($user);

    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->post(route('finance.credits.free-payments.store', $credit), [
            'paid_on' => '2026-06-22',
            'amount' => 220,
            'notes' => 'Pago suelto',
        ])
        ->assertRedirect(route('finance.credits.index'))
        ->assertSessionHas('success', 'Abono libre registrado como egreso real.');

    $this->assertDatabaseHas('finance_credit_free_payments', [
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'amount_applied' => 220,
    ]);

    $this->assertDatabaseHas('finance_movements', [
        'user_id' => $user->id,
        'movement_type' => 'expense',
        'amount' => 220,
        'description' => 'Abono libre crédito: MPW prueba',
        'source' => 'credit_free_payment',
    ]);

    expect(CreditInstallment::where('credit_purchase_id', $credit->id)->where('status', 'paid')->count())->toBe(0);
    expect(CreditPurchase::findOrFail($credit->id)->status)->toBe('partially_paid');

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Abonos libres')
        ->assertSee('Saldo real');
});

it('deletes and restores a free credit payment with its generated movement', function () {
    $user = makeFinanceOwner(User::factory()->create());
    $credit = createCreditForFreePaymentTest($user);

    $this->actingAs($user)
        ->post(route('finance.credits.free-payments.store', $credit), [
            'paid_on' => '2026-06-22',
            'amount' => 220,
        ]);

    $payment = CreditFreePayment::where('user_id', $user->id)->firstOrFail();
    $movementId = $payment->movement_id;

    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->delete(route('finance.credits.free-payments.destroy', $payment))
        ->assertRedirect(route('finance.credits.index'))
        ->assertSessionHas('undo_delete');

    expect(CreditFreePayment::whereKey($payment->id)->exists())->toBeFalse();
    expect(Movement::whereKey($movementId)->exists())->toBeFalse();

    $snapshot = DeleteSnapshot::where('user_id', $user->id)
        ->where('entity_type', 'credit_free_payment')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $snapshot->token))
        ->assertRedirect()
        ->assertSessionHas('success', 'Abono libre restaurado.');

    expect(CreditFreePayment::whereKey($payment->id)->exists())->toBeTrue();
    expect(Movement::whereKey($movementId)->exists())->toBeTrue();
    expect(CreditPurchase::findOrFail($credit->id)->status)->toBe('partially_paid');
});
