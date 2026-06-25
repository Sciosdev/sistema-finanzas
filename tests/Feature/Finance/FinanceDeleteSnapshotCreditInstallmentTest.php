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

function createFinanceUserForCreditInstallmentDeleteSnapshots(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return makeFinanceOwner($user);
}

function createCreditWithInstallmentsForIndividualSnapshot(User $user): CreditPurchase
{
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-22',
        'name' => 'Amazon mensualidad',
        'total_amount' => 600,
        'months' => 3,
        'first_due_month' => '2026-07-01',
        'due_day' => 27,
        'status' => 'active',
        'notes' => 'Credito para mensualidad individual',
    ]);

    foreach ([1 => 100, 2 => 200, 3 => 300] as $number => $amount) {
        CreditInstallment::create([
            'credit_purchase_id' => $credit->id,
            'user_id' => $user->id,
            'period_month' => Carbon::create(2026, 6 + $number, 1)->toDateString(),
            'due_date' => Carbon::create(2026, 6 + $number, 27)->toDateString(),
            'installment_number' => $number,
            'amount' => $amount,
            'paid_amount' => $number === 1 ? $amount : 0,
            'paid_on' => $number === 1 ? '2026-07-27' : null,
            'status' => $number === 1 ? 'paid' : 'pending',
            'notes' => 'Mensualidad ' . $number,
        ]);
    }

    return $credit;
}

it('deletes an individual credit installment and restores it before two minutes', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertRedirect()
        ->assertSessionHas('success', 'Mensualidad eliminada.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');
    $snapshot = DeleteSnapshot::where('token', $undo['token'])->firstOrFail();

    expect(CreditInstallment::whereKey($installment->id)->exists())->toBeFalse();
    expect($snapshot->entity_type)->toBe('credit_installment');
    expect($snapshot->relations_payload['credit']['id'])->toBe($credit->id);

    $credit->refresh();
    expect((float) $credit->total_amount)->toBe(400.0);
    expect($credit->months)->toBe(2);

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('success', 'Mensualidad restaurada.');

    $restored = CreditInstallment::findOrFail($installment->id);
    $credit->refresh();

    expect((float) $restored->amount)->toBe(200.0);
    expect($restored->installment_number)->toBe(2);
    expect($restored->period_month->toDateString())->toBe('2026-08-01');
    expect($restored->notes)->toBe('Mensualidad 2');
    expect((float) $credit->total_amount)->toBe(600.0);
    expect($credit->months)->toBe(3);
    expect($credit->first_due_month->toDateString())->toBe('2026-07-01');
    expect($credit->status)->toBe('partially_paid');

    Carbon::setTestNow();
});

it('renumbers installments when deleting and restoring an individual installment', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect($credit->installments()->orderBy('period_month')->pluck('installment_number')->all())->toBe([1, 2]);

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Mensualidad restaurada.');

    expect($credit->installments()->orderBy('period_month')->pluck('installment_number')->all())->toBe([1, 2, 3]);

    Carbon::setTestNow();
});

it('does not restore an individual credit installment after the two minute window expires', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Carbon::setTestNow('2026-06-22 10:03:00');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'El tiempo para deshacer ya expiró.');

    expect(CreditInstallment::whereKey($installment->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('prevents restoring the same individual credit installment token twice', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Mensualidad restaurada.');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'Ese borrado ya fue restaurado.');

    expect(CreditInstallment::whereKey($installment->id)->count())->toBe(1);

    Carbon::setTestNow();
});

it('fails safely when restoring an individual credit installment that already exists', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');
    $credit->refresh();
    $totalBeforeRestore = (float) $credit->total_amount;

    DB::table('finance_credit_installments')->insert([
        'id' => $installment->id,
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-10-01',
        'due_date' => '2026-10-27',
        'installment_number' => 99,
        'amount' => 999,
        'paid_amount' => 0,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'No se pudo restaurar porque la mensualidad ya existe.');

    $credit->refresh();
    expect((float) $credit->total_amount)->toBe($totalBeforeRestore);
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});

it('fails safely when restoring an individual installment without its credit', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    CreditPurchase::whereKey($credit->id)->delete();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'No se pudo restaurar porque el crédito ya no existe.');

    expect(CreditInstallment::whereKey($installment->id)->exists())->toBeFalse();
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});

it('restores an individual installment without movement id when its movement no longer exists', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-08-27',
        'movement_type' => 'expense',
        'amount' => 200,
        'description' => 'Pago mensualidad',
        'source' => 'credit_installment',
    ]);

    $installment->update(['movement_id' => $movement->id]);

    $this->actingAs($user)
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Movement::whereKey($movement->id)->delete();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Mensualidad restaurada.');

    expect(CreditInstallment::findOrFail($installment->id)->movement_id)->toBeNull();

    Carbon::setTestNow();
});

it('shows the undo button after deleting an individual credit installment', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForCreditInstallmentDeleteSnapshots();
    $credit = createCreditWithInstallmentsForIndividualSnapshot($user);
    $installment = $credit->installments()->where('installment_number', 2)->firstOrFail();

    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->followingRedirects()
        ->delete(route('finance.credits.installments.destroy', $installment))
        ->assertOk()
        ->assertSee('Mensualidad eliminada.')
        ->assertSee('Deshacer')
        ->assertSee('Disponible por 2 minutos.');

    Carbon::setTestNow();
});
