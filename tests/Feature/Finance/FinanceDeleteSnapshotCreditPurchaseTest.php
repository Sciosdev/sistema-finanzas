<?php

use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createFinanceUserForCreditPurchaseDeleteSnapshots(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function createCreditPurchaseWithInstallmentsForSnapshot(User $user): CreditPurchase
{
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-22',
        'name' => 'Amazon',
        'total_amount' => 1139.10,
        'months' => 3,
        'first_due_month' => '2026-07-01',
        'due_day' => 27,
        'status' => 'active',
        'notes' => 'Credito para deshacer',
    ]);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-27',
        'installment_number' => 1,
        'amount' => 379.70,
        'paid_amount' => 379.70,
        'paid_on' => '2026-07-27',
        'status' => 'paid',
        'notes' => 'Primera mensualidad',
    ]);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-08-01',
        'due_date' => '2026-08-27',
        'installment_number' => 2,
        'amount' => 379.70,
        'paid_amount' => 0,
        'status' => 'pending',
        'notes' => 'Segunda mensualidad',
    ]);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-09-01',
        'due_date' => '2026-09-27',
        'installment_number' => 3,
        'amount' => 379.70,
        'paid_amount' => 0,
        'status' => 'pending',
        'notes' => 'Tercera mensualidad',
    ]);

    return $credit;
}

it('deletes a full credit purchase and restores it with all installments before two minutes', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);
    $installmentIds = $credit->installments()->orderBy('installment_number')->pluck('id')->all();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-07-27',
        'movement_type' => 'expense',
        'amount' => 379.70,
        'description' => 'Pago credito Amazon',
        'source' => 'credit_installment',
    ]);

    $credit->installments()->where('installment_number', 1)->update(['movement_id' => $movement->id]);

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertRedirect()
        ->assertSessionHas('success', 'Crédito eliminado.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(CreditPurchase::whereKey($credit->id)->exists())->toBeFalse();
    expect(CreditInstallment::whereIn('id', $installmentIds)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('success', 'Crédito restaurado.');

    $restored = CreditPurchase::with('installments')->findOrFail($credit->id);
    $installments = $restored->installments()->orderBy('installment_number')->get();

    expect($restored->name)->toBe('Amazon');
    expect((float) $restored->total_amount)->toBe(1139.10);
    expect($installments)->toHaveCount(3);
    expect($installments->pluck('id')->all())->toBe($installmentIds);
    expect((float) $installments[0]->amount)->toBe(379.70);
    expect((float) $installments[0]->paid_amount)->toBe(379.70);
    expect($installments[0]->paid_on->toDateString())->toBe('2026-07-27');
    expect($installments[0]->movement_id)->toBe($movement->id);
    expect($installments[1]->status)->toBe('pending');
    expect($installments[1]->notes)->toBe('Segunda mensualidad');

    Carbon::setTestNow();
});

it('restores a credit installment without movement id when its movement no longer exists', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-07-27',
        'movement_type' => 'expense',
        'amount' => 379.70,
        'description' => 'Movimiento que desaparece',
        'source' => 'credit_installment',
    ]);

    $firstInstallment = $credit->installments()->where('installment_number', 1)->firstOrFail();
    $firstInstallment->update(['movement_id' => $movement->id]);

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Movement::whereKey($movement->id)->delete();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Crédito restaurado.');

    expect(CreditInstallment::findOrFail($firstInstallment->id)->movement_id)->toBeNull();

    Carbon::setTestNow();
});

it('does not restore a full credit purchase after the two minute window expires', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);
    $installmentIds = $credit->installments()->pluck('id')->all();

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Carbon::setTestNow('2026-06-22 10:03:00');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'El tiempo para deshacer ya expiró.');

    expect(CreditPurchase::whereKey($credit->id)->exists())->toBeFalse();
    expect(CreditInstallment::whereIn('id', $installmentIds)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('prevents restoring the same full credit purchase token twice', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Crédito restaurado.');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'Ese borrado ya fue restaurado.');

    expect(CreditPurchase::whereKey($credit->id)->count())->toBe(1);

    Carbon::setTestNow();
});

it('fails safely when restoring a credit purchase with an existing credit id', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);
    $installmentIds = $credit->installments()->pluck('id')->all();

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    DB::table('finance_credit_purchases')->insert([
        'id' => $credit->id,
        'user_id' => $user->id,
        'purchase_date' => '2026-06-23',
        'name' => 'Credito duplicado',
        'total_amount' => 50,
        'months' => 1,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'No se pudo restaurar porque el crédito ya existe.');

    expect(CreditInstallment::whereIn('id', $installmentIds)->exists())->toBeFalse();
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});

it('rolls back completely when restoring a credit purchase with an existing installment id', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);
    $conflictingInstallmentId = $credit->installments()->orderBy('installment_number')->value('id');

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    DB::table('finance_credit_purchases')->insert([
        'id' => $credit->id + 1000,
        'user_id' => $user->id,
        'purchase_date' => '2026-06-23',
        'name' => 'Otro credito',
        'total_amount' => 100,
        'months' => 1,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('finance_credit_installments')->insert([
        'id' => $conflictingInstallmentId,
        'credit_purchase_id' => $credit->id + 1000,
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-27',
        'installment_number' => 1,
        'amount' => 100,
        'paid_amount' => 0,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'No se pudo restaurar porque una mensualidad ya existe.');

    expect(CreditPurchase::whereKey($credit->id)->exists())->toBeFalse();
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});

it('shows the undo button after deleting a full credit purchase', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditPurchaseDeleteSnapshots();
    $credit = createCreditPurchaseWithInstallmentsForSnapshot($user);

    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->followingRedirects()
        ->delete(route('finance.credits.destroy', $credit))
        ->assertOk()
        ->assertSee('Crédito eliminado.')
        ->assertSee('Deshacer')
        ->assertSee('Disponible por 2 minutos.');

    Carbon::setTestNow();
});
