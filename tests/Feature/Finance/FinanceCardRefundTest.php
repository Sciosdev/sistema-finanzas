<?php

use App\Models\Finance\Account;
use App\Models\Finance\CardRefund;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\CardRefundService;
use App\Services\Finance\CreditEffectiveScheduleService;
use App\Services\Finance\CreditFreePaymentService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-09-25 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

function refundAccount(User $user, string $name = 'NU'): Account
{
    return Account::create([
        'user_id' => $user->id, 'name' => $name, 'type' => 'card',
        'opening_balance' => 0, 'credit_limit' => 20000, 'is_active' => true,
    ]);
}

/** @param array<string, float> $amounts */
function refundCredit(User $user, Account $account, array $amounts): CreditPurchase
{
    $credit = CreditPurchase::create([
        'user_id' => $user->id, 'account_id' => $account->id,
        'purchase_date' => '2026-09-16', 'name' => 'Compra de prueba',
        'total_amount' => array_sum($amounts), 'months' => count($amounts),
        'first_due_month' => array_key_first($amounts).'-01', 'due_day' => 26, 'status' => 'active',
    ]);
    $number = 0;
    foreach ($amounts as $month => $amount) {
        CreditInstallment::create([
            'user_id' => $user->id, 'credit_purchase_id' => $credit->id,
            'period_month' => $month.'-01', 'due_date' => $month.'-26',
            'installment_number' => ++$number, 'amount' => $amount,
            'paid_amount' => 0, 'status' => 'pending',
        ]);
    }

    return $credit->fresh(['installments', 'freePayments']);
}

function recordOctoberRefund(User $user, Account $account, float $amount = 50, string $key = 'amazon-2026-09-16'): CardRefund
{
    return app(CardRefundService::class)->create(
        $user, $account, Carbon::parse('2026-09-16'), Carbon::parse('2026-10-01'),
        $amount, 'Estorno de compra Amazon.Com Inc', 'Devolución real mostrada en Nu; compra original no identificada.', $key,
    );
}

function refundDues(CreditPurchase $credit): array
{
    $schedule = app(CreditEffectiveScheduleService::class);
    $schedule->flush();
    $fresh = $credit->fresh(['installments', 'freePayments']);
    $pending = $schedule->effectivePendingFor($fresh);

    return $fresh->installments->sortBy('installment_number')
        ->map(fn ($installment) => $pending[$installment->id])->values()->all();
}

it('records each actual refund without cash and reduces only the selected card and month', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $first = refundCredit($user, $nu, ['2026-09' => 99, '2026-10' => 10, '2026-11' => 90]);
    $second = refundCredit($user, $nu, ['2026-10' => 3850.76]);
    $other = refundCredit($user, refundAccount($user, 'Otra'), ['2026-10' => 333]);
    $originalRows = CreditInstallment::orderBy('id')->get(['id', 'amount', 'paid_amount', 'due_date'])->toArray();

    $amazon = recordOctoberRefund($user, $nu);
    $pase = app(CardRefundService::class)->create(
        $user, $nu, Carbon::parse('2026-09-19'), Carbon::parse('2026-10-01'), 26,
        'PASE DEV ACLARACION CR', 'Crédito real mostrado en Nu.', 'pase-2026-09-19',
    );

    expect(CardRefund::count())->toBe(2)
        ->and((float) $amazon->amount)->toBe(50.0)
        ->and((float) $pase->amount)->toBe(26.0)
        ->and($amazon->reference_credit_purchase_id)->toBeNull()
        ->and(round((float) CreditFreePayment::where('payment_type', 'refund')->sum('amount_applied'), 2))->toBe(76.0)
        ->and(CreditFreePayment::whereNotNull('movement_id')->count())->toBe(0)
        ->and(Movement::count())->toBe(0)
        ->and(CreditInstallment::orderBy('id')->get(['id', 'amount', 'paid_amount', 'due_date'])->toArray())->toBe($originalRows)
        ->and(refundDues($first))->toBe([99.0, 0.0, 90.0])
        ->and(refundDues($second))->toBe([3784.76])
        ->and(refundDues($other))->toBe([333.0]);

    $totals = app(CreditFreePaymentService::class)->totals($second);
    expect($totals['total_paid'])->toBe(0.0)
        ->and($totals['free_paid'])->toBe(0.0)
        ->and($totals['refunded'])->toBe(66.0)
        ->and($totals['settled_total'])->toBe(66.0)
        ->and($totals['balance_due'])->toBe(3784.76);

    $obligations = app(FinanceSummaryService::class)->monthObligations(
        $user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'),
    );
    expect(round($obligations->where('source', 'credit')->sum('amount_due'), 2))->toBe(4117.76);
});

it('keeps a refund anchored after paying only the remaining cash', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-09' => 100, '2026-10' => 500, '2026-11' => 500]);
    $refund = recordOctoberRefund($user, $nu);
    $october = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();

    expect(refundDues($credit))->toBe([100.0, 450.0, 500.0])
        ->and($refund->allocations->first()->target_installment_id)->toBe($october->id);
    $this->actingAs($user)->post(route('finance.credits.installments.paid', $october), [
        'paid_on' => '2026-10-26',
    ])->assertRedirect();

    Carbon::setTestNow('2026-11-03');
    expect(refundDues($credit))->toBe([100.0, 0.0, 500.0])
        ->and((float) $october->fresh()->paid_amount)->toBe(450.0)
        ->and(Movement::count())->toBe(1)
        ->and((float) Movement::firstOrFail()->amount)->toBe(450.0);
    $totals = app(CreditFreePaymentService::class)->totals($credit);
    expect($totals['total_paid'])->toBe(450.0)->and($totals['refunded'])->toBe(50.0)
        ->and($totals['balance_due'])->toBe(600.0);
});

it('combines a real advance a refund and a later payment without double counting', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100, '2026-11' => 100]);
    app(CreditFreePaymentService::class)->createFreePayment($credit, Carbon::parse('2026-09-25'), 20);
    recordOctoberRefund($user, $nu, 15);
    $october = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();

    expect(refundDues($credit))->toBe([65.0, 100.0]);
    $this->actingAs($user)->post(route('finance.credits.installments.paid', $october), [
        'paid_on' => '2026-10-26',
    ])->assertRedirect();
    expect(refundDues($credit))->toBe([0.0, 100.0])
        ->and(Movement::count())->toBe(2)
        ->and(round((float) Movement::sum('amount'), 2))->toBe(85.0);
});

it('returns the same refund on an identical retry and rejects changed data for its reference', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100]);
    $first = recordOctoberRefund($user, $nu);
    $retry = recordOctoberRefund($user, $nu);

    expect($retry->id)->toBe($first->id)->and(CardRefund::count())->toBe(1)
        ->and(refundDues($credit))->toBe([50.0]);
    expect(fn () => recordOctoberRefund($user, $nu, 26))->toThrow(RuntimeException::class);
    expect(CardRefund::count())->toBe(1)->and(Movement::count())->toBe(0);
});

it('rejects refunds beyond available room including a recently paid amount', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100]);
    app(CreditFreePaymentService::class)->createFreePayment($credit, Carbon::parse('2026-09-25'), 70);

    expect(fn () => recordOctoberRefund($user, $nu, 50))->toThrow(RuntimeException::class);
    expect(CardRefund::count())->toBe(0)->and(CreditFreePayment::where('payment_type', 'refund')->count())->toBe(0)
        ->and(Movement::count())->toBe(1)->and(refundDues($credit))->toBe([30.0]);
});

it('rejects nonpositive refunds and foreign card ownership', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    refundCredit($user, $nu, ['2026-10' => 100]);
    expect(fn () => recordOctoberRefund($user, $nu, -1))->toThrow(RuntimeException::class);
    expect(fn () => recordOctoberRefund($user, $nu, 0))->toThrow(RuntimeException::class);
    expect(fn () => recordOctoberRefund(User::factory()->create(), $nu))->toThrow(RuntimeException::class);
    expect(CardRefund::count())->toBe(0)->and(Movement::count())->toBe(0);
});

it('reverses an unused refund but blocks deleting a single allocation', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100]);
    $refund = recordOctoberRefund($user, $nu);

    expect(fn () => app(CreditFreePaymentService::class)->deleteFreePayment($refund->allocations->first()))
        ->toThrow(RuntimeException::class);
    app(CardRefundService::class)->delete($refund);

    expect(refundDues($credit))->toBe([100.0])->and($credit->fresh()->status)->toBe('active')
        ->and(CardRefund::count())->toBe(0)->and(CreditFreePayment::count())->toBe(0)
        ->and(Movement::count())->toBe(0);
});

it('blocks deleting a refund consumed by a real payment or changing its target calendar', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100]);
    $refund = recordOctoberRefund($user, $nu);
    $october = $credit->installments()->firstOrFail();
    expect(fn () => app(CardRefundService::class)->assertInstallmentCanBeChanged($october))
        ->toThrow(RuntimeException::class);

    $this->actingAs($user)->post(route('finance.credits.installments.paid', $october), [
        'paid_on' => '2026-10-26',
    ])->assertRedirect();
    expect(fn () => app(CardRefundService::class)->delete($refund))->toThrow(RuntimeException::class);
    expect(CardRefund::count())->toBe(1)->and(refundDues($credit))->toBe([0.0])
        ->and((float) Movement::firstOrFail()->amount)->toBe(50.0);
});

it('reverses all allocations when one refund covers multiple installments of the same month', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 20, '2026-11' => 80]);
    $credit->installments()->where('installment_number', 2)->update([
        'period_month' => '2026-10-01', 'due_date' => '2026-10-27',
    ]);
    $refund = recordOctoberRefund($user, $nu, 50);

    expect($refund->allocations)->toHaveCount(2)->and(refundDues($credit))->toBe([0.0, 50.0]);
    app(CardRefundService::class)->delete($refund);
    expect(refundDues($credit))->toBe([20.0, 80.0])->and(CardRefund::count())->toBe(0);
});

it('rejects an original purchase from another card instead of inferring it from an amount', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    refundCredit($user, $nu, ['2026-10' => 100]);
    $other = refundCredit($user, refundAccount($user, 'Otra'), ['2026-10' => 50]);

    expect(fn () => app(CardRefundService::class)->create(
        $user, $nu, Carbon::parse('2026-09-16'), Carbon::parse('2026-10-01'), 50,
        'Amazon', 'La referencia debe pertenecer a esta tarjeta.', 'invalid-original-purchase', $other,
    ))->toThrow(RuntimeException::class);
    expect(CardRefund::count())->toBe(0)->and(Movement::count())->toBe(0);
});

it('stores a source refund through its form and enforces deletion ownership', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100]);
    $this->actingAs($user)->from(route('finance.credits.index'))
        ->post(route('finance.credits.card-refunds.store'), [
            'account_id' => $nu->id, 'received_on' => '2026-09-16',
            'period_month' => '2026-10', 'amount' => '50.00',
            'description' => 'Estorno de compra Amazon.Com Inc',
            'notes' => 'Captura de Nu; no identifica la compra original.',
            'idempotency_key' => '4a838747-956e-4aca-9b4d-18e15ba6ea95',
        ])->assertRedirect(route('finance.credits.index').'#card-refunds')->assertSessionHas('success');

    $refund = CardRefund::firstOrFail();
    expect(refundDues($credit))->toBe([50.0])->and(Movement::count())->toBe(0);
    $this->actingAs(User::factory()->create())
        ->delete(route('finance.credits.card-refunds.destroy', $refund))->assertForbidden();
    expect(CardRefund::count())->toBe(1);
    $this->actingAs($user)->from(route('finance.credits.index'))
        ->delete(route('finance.credits.card-refunds.destroy', $refund))
        ->assertRedirect(route('finance.credits.index').'#card-refunds')->assertSessionHas('success');
    expect(refundDues($credit))->toBe([100.0])->and(CardRefund::count())->toBe(0);
});

it('rejects deleting an earlier cash advance when a refund and payment use its installment', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100, '2026-11' => 100]);
    $cash = app(CreditFreePaymentService::class)->createFreePayment($credit, Carbon::parse('2026-09-25'), 20);
    recordOctoberRefund($user, $nu, 15);
    $october = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();
    $this->actingAs($user)->post(route('finance.credits.installments.paid', $october), [
        'paid_on' => '2026-10-26',
    ])->assertRedirect();

    expect(fn () => app(CreditFreePaymentService::class)->deleteFreePayment($cash))->toThrow(RuntimeException::class);
    $this->actingAs($user)->from(route('finance.credits.index'))
        ->delete(route('finance.credits.free-payments.destroy', $cash))
        ->assertRedirect(route('finance.credits.index'))->assertSessionHasErrors();

    expect(refundDues($credit))->toBe([0.0, 100.0])
        ->and(app(CreditFreePaymentService::class)->totals($credit)['balance_due'])->toBe(100.0)
        ->and(DeleteSnapshot::count())->toBe(0)
        ->and(round((float) Movement::sum('amount'), 2))->toBe(85.0);
});

it('rejects a linked planned installment instead of silently allocating its refund elsewhere', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $linked = refundCredit($user, $nu, ['2026-10' => 100]);
    $other = refundCredit($user, $nu, ['2026-10' => 100]);
    $installment = $linked->installments()->firstOrFail();
    PlannedPayment::create([
        'user_id' => $user->id, 'account_id' => $nu->id,
        'period_month' => '2026-10-01', 'due_date' => '2026-10-26',
        'name' => 'Pago planeado de la compra', 'amount' => 100, 'paid_amount' => 0,
        'status' => 'pending', 'credit_purchase_id' => $linked->id,
        'credit_installment_id' => $installment->id, 'is_credit' => true,
    ]);

    expect(fn () => recordOctoberRefund($user, $nu, 50))->toThrow(RuntimeException::class);
    expect(CardRefund::count())->toBe(0)->and(CreditFreePayment::count())->toBe(0)
        ->and(refundDues($linked))->toBe([100.0])->and(refundDues($other))->toBe([100.0])
        ->and((float) PlannedPayment::firstOrFail()->amount)->toBe(100.0)
        ->and(Movement::count())->toBe(0);
});

it('does not restore a deleted cash advance over a subsequently recorded refund', function () {
    $user = makeFinanceOwner(User::factory()->create());
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100]);
    $cash = app(CreditFreePaymentService::class)->createFreePayment($credit, Carbon::parse('2026-09-25'), 50);
    $this->actingAs($user)->delete(route('finance.credits.free-payments.destroy', $cash))->assertRedirect();
    $snapshot = DeleteSnapshot::where('entity_type', 'credit_free_payment')->firstOrFail();
    recordOctoberRefund($user, $nu, 70);

    $this->actingAs($user)->post(route('finance.security.undo-delete', $snapshot->token))
        ->assertRedirect()->assertSessionHas('error');
    expect(refundDues($credit))->toBe([30.0])->and(CardRefund::count())->toBe(1)
        ->and(CreditFreePayment::where('payment_type', 'free_payment')->count())->toBe(0)
        ->and(Movement::count())->toBe(0);
});

it('marks only the remaining cash as already registered and safely tolerates a retry', function () {
    $user = User::factory()->create();
    $nu = refundAccount($user);
    $credit = refundCredit($user, $nu, ['2026-10' => 100, '2026-11' => 100]);
    app(CreditFreePaymentService::class)->createFreePayment($credit, Carbon::parse('2026-09-25'), 20);
    recordOctoberRefund($user, $nu, 15);
    $october = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();

    foreach ([1, 2] as $attempt) {
        $this->actingAs($user)->post(route('finance.credits.installments.registered', $october), [
            'paid_on' => '2026-10-26',
        ])->assertRedirect()->assertSessionHas('success');
    }
    expect((float) $october->fresh()->paid_amount)->toBe(65.0)
        ->and(refundDues($credit))->toBe([0.0, 100.0])
        ->and(Movement::count())->toBe(1)
        ->and((float) Movement::firstOrFail()->amount)->toBe(20.0);
});
