<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPaymentCorrection;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\CreditEffectiveScheduleService;
use App\Services\Finance\CreditPaymentCorrectionService;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function creditCorrectionFixture(): array
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $cash = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();
    $ids = [];
    foreach ([40.13, 60.27, 90.41, 10.19] as $index => $amount) {
        $credit = CreditPurchase::create([
            'user_id' => $user->id, 'account_id' => $card->id,
            'purchase_date' => $index < 2 ? '2028-09-14' : '2028-09-15',
            'name' => 'Compra sintética '.($index + 1), 'total_amount' => $amount,
            'months' => 1, 'first_due_month' => '2028-09-01', 'due_day' => 27,
            'status' => 'paid', 'is_manual_schedule' => false,
        ]);
        $movement = Movement::create([
            'user_id' => $user->id, 'account_id' => $cash->id,
            'happened_on' => '2028-09-15', 'movement_type' => 'expense',
            'description' => 'Crédito: '.$credit->name.' 1/1', 'amount' => $amount,
            'source' => 'credit_installment', 'notes' => 'Histórico '.($index + 1),
        ]);
        $row = $credit->installments()->create([
            'user_id' => $user->id, 'period_month' => '2028-09-01', 'due_date' => '2028-09-27',
            'installment_number' => 1, 'amount' => $amount, 'paid_amount' => $amount,
            'paid_on' => '2028-09-15', 'status' => 'paid', 'movement_id' => $movement->id,
        ]);
        $ids[] = $row->id;
    }
    $data = [
        'installment_ids' => $ids, 'expected_paid_total' => 201.00, 'amount' => 35.37,
        'concept' => 'Impuesto documentado de prueba', 'charge_period_month' => '2028-09',
        'charge_due_date' => '2028-09-25', 'target_period_month' => '2028-10',
        'target_due_date' => '2028-10-26', 'paid_on' => '2028-09-15',
        'reason' => 'El documento sintético identifica un impuesto pagado por separado.',
        'idempotency_key' => (string) Str::uuid(),
    ];

    return [$user, $card, $cash, $data];
}

function creditCorrectionState(): array
{
    return [
        'credits' => CreditPurchase::orderBy('id')->get()->map->getAttributes()->all(),
        'installments' => CreditInstallment::orderBy('id')->get()->map->getAttributes()->all(),
        'movements' => Movement::orderBy('id')->get()->map->getAttributes()->all(),
    ];
}

it('separates a documented tax from existing paid purchases while preserving cash exactly', function () {
    [$user, $card, $cash, $data] = creditCorrectionFixture();
    $before = creditCorrectionState();
    $correction = app(CreditPaymentCorrectionService::class)->separateCharge($user, $data);
    $rows = CreditInstallment::whereIn('id', $data['installment_ids'])->orderBy('id')->get();
    $charge = CreditPurchase::findOrFail($correction->charge_credit_purchase_id);

    expect($correction->before_state)->toBe($before)
        ->and($correction->after_state)->toBe(creditCorrectionState())
        ->and((int) round(Movement::where('movement_type', 'expense')->sum('amount') * 100))->toBe(20100)
        ->and((int) round(CreditInstallment::sum('paid_amount') * 100))->toBe(20100)
        ->and($rows->map(fn ($row) => (float) $row->amount)->all())->toBe([40.13, 60.27, 90.41, 10.19])
        ->and($rows->map(fn ($row) => (float) $row->paid_amount)->all())->toBe([40.13, 60.27, 65.23, 0.0])
        ->and($rows->pluck('status')->all())->toBe(['paid', 'paid', 'pending', 'pending'])
        ->and($rows->pluck('period_month')->map->format('Y-m')->unique()->all())->toBe(['2028-10'])
        ->and($rows->last()->movement_id)->toBeNull()
        ->and($charge->account_id)->toBe($card->id)
        ->and($charge->category_id)->not->toBeNull()
        ->and((float) $charge->total_amount)->toBe(35.37)
        ->and($charge->installments->first()->status)->toBe('paid')
        ->and($charge->installments->first()->period_month->format('Y-m'))->toBe('2028-09')
        ->and($charge->installments->first()->movement->account_id)->toBe($cash->id)
        ->and(CreditFreePayment::count())->toBe(0);
    $schedule = app(CreditEffectiveScheduleService::class);
    $schedule->flush();
    expect(round($rows->sum(fn ($row) => $schedule->effectivePending($row)), 2))->toBe(35.37);
});

it('returns the same audit for a repeated request and rejects reuse with different data', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $service = app(CreditPaymentCorrectionService::class);
    $first = $service->separateCharge($user, $data);
    $after = creditCorrectionState();
    $second = $service->separateCharge($user, $data);
    expect($second->id)->toBe($first->id)->and(CreditPaymentCorrection::count())->toBe(1)
        ->and(creditCorrectionState())->toBe($after);
    expect(fn () => $service->separateCharge($user, array_replace($data, ['amount' => 50])))
        ->toThrow(RuntimeException::class);
    expect(creditCorrectionState())->toBe($after);
});

it('restores the original records including a removed zero payment when undoing an untouched correction', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $before = creditCorrectionState();
    $service = app(CreditPaymentCorrectionService::class);
    $correction = $service->separateCharge($user, $data);
    $service->undo($user, $correction);

    expect(creditCorrectionState())->toBe($before)
        ->and($correction->fresh()->reverted_at)->not->toBeNull();
    expect(fn () => $service->separateCharge($user, $data))->toThrow(RuntimeException::class);
});

it('refuses to undo after a corrected record changes', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $service = app(CreditPaymentCorrectionService::class);
    $correction = $service->separateCharge($user, $data);
    CreditInstallment::findOrFail($data['installment_ids'][2])->update(['notes' => 'Revisado después']);
    $after = creditCorrectionState();
    expect(fn () => $service->undo($user, $correction))->toThrow(RuntimeException::class);
    expect(creditCorrectionState())->toBe($after)->and($correction->fresh()->reverted_at)->toBeNull();
});

it('rejects an unsafe correction atomically', function (string $fault) {
    [$user, $card, $cash, $data] = creditCorrectionFixture();
    $row = CreditInstallment::findOrFail($data['installment_ids'][0]);
    if ($fault === 'stale total') {
        $data['expected_paid_total'] = 240;
    } elseif ($fault === 'cash mismatch') {
        $row->movement->update(['amount' => 40]);
    } elseif ($fault === 'partially paid') {
        $row->update(['paid_amount' => 40, 'status' => 'pending']);
    } elseif ($fault === 'date mismatch') {
        $row->movement->update(['happened_on' => '2028-09-16']);
    } elseif ($fault === 'payment account mismatch') {
        $row->movement->update(['account_id' => $card->id]);
    } elseif ($fault === 'card mismatch') {
        $row->creditPurchase->update(['account_id' => $cash->id]);
    } elseif ($fault === 'shared movement') {
        CreditInstallment::findOrFail($data['installment_ids'][1])->update(['movement_id' => $row->movement_id]);
    } elseif ($fault === 'linked planned payment') {
        PlannedPayment::create([
            'user_id' => $user->id, 'name' => 'Pago vinculado', 'period_month' => '2028-09-01',
            'amount' => 40.13, 'status' => 'paid', 'movement_id' => $row->movement_id,
            'credit_installment_id' => $row->id,
        ]);
    } elseif ($fault === 'existing advance') {
        CreditFreePayment::create([
            'user_id' => $user->id, 'credit_purchase_id' => $row->credit_purchase_id,
            'amount_applied' => 1, 'paid_on' => '2028-09-15', 'payment_type' => 'free_payment',
        ]);
    } elseif ($fault === 'foreign owner') {
        $user = User::factory()->create();
    } elseif ($fault === 'entire payment') {
        $data['amount'] = $data['expected_paid_total'];
    } elseif ($fault === 'duplicate ids') {
        $data['installment_ids'][] = $row->id;
    }
    $before = creditCorrectionState();
    expect(fn () => app(CreditPaymentCorrectionService::class)->separateCharge($user, $data))
        ->toThrow(RuntimeException::class);
    expect(creditCorrectionState())->toBe($before)->and(CreditPaymentCorrection::count())->toBe(0);
})->with([
    'stale total', 'cash mismatch', 'partially paid', 'date mismatch', 'payment account mismatch',
    'card mismatch', 'shared movement', 'linked planned payment', 'existing advance', 'foreign owner',
    'entire payment', 'duplicate ids',
]);

it('validates the correction form and creates an auditable correction through its route', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $this->actingAs($user)->post(route('finance.credits.payment-corrections.store'), array_replace($data, ['reason' => '']))
        ->assertRedirect()->assertSessionHasErrors('reason');
    expect(CreditPaymentCorrection::count())->toBe(0);
    $this->post(route('finance.credits.payment-corrections.store'), $data)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
    expect(CreditPaymentCorrection::count())->toBe(1)->and((int) round(Movement::sum('amount') * 100))->toBe(20100);
});

it('shows an undo action to the owner and restores the original records through its route', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $before = creditCorrectionState();
    $correction = app(CreditPaymentCorrectionService::class)->separateCharge($user, $data);
    $this->actingAs($user)->get(route('finance.credits.index'))->assertOk()
        ->assertSee($correction->concept)->assertSee($correction->reason)
        ->assertSee('35.37')->assertSee('Revertir')
        ->assertSee(route('finance.credits.payment-corrections.destroy', $correction), false);
    $this->delete(route('finance.credits.payment-corrections.destroy', $correction))
        ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
    expect(creditCorrectionState())->toBe($before)
        ->and($correction->fresh()->reverted_at)->not->toBeNull();
    $this->get(route('finance.credits.index'))->assertOk()->assertSee('Revertida')
        ->assertDontSee(route('finance.credits.payment-corrections.destroy', $correction), false);
});

it('forbids an undo request by a different user without changing any record', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $correction = app(CreditPaymentCorrectionService::class)->separateCharge($user, $data);
    $after = creditCorrectionState();
    $this->actingAs(User::factory()->create())
        ->delete(route('finance.credits.payment-corrections.destroy', $correction))->assertForbidden();
    expect(creditCorrectionState())->toBe($after)->and($correction->fresh()->reverted_at)->toBeNull();
});

it('returns a validation error when an HTTP undo would overwrite a later change', function () {
    [$user, , , $data] = creditCorrectionFixture();
    $correction = app(CreditPaymentCorrectionService::class)->separateCharge($user, $data);
    CreditInstallment::findOrFail($data['installment_ids'][2])->update(['notes' => 'Cambio posterior sintético']);
    $after = creditCorrectionState();
    $this->actingAs($user)->delete(route('finance.credits.payment-corrections.destroy', $correction))
        ->assertRedirect()->assertSessionHasErrors('correction');
    expect(creditCorrectionState())->toBe($after)->and($correction->fresh()->reverted_at)->toBeNull();
});
