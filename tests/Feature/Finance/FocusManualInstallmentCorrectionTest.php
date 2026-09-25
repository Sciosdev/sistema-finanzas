<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\CreditEffectiveScheduleService;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function focusManualInstallmentCredit(): array
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $account->update(['statement_day' => 15, 'payment_day' => 27]);
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'purchase_date' => '2028-06-11',
        'name' => 'Compra de prueba calendario sintético',
        'total_amount' => 720.72,
        'months' => 6,
        'first_due_month' => '2028-06-01',
        'due_day' => 27,
        'is_manual_schedule' => false,
        'status' => 'partially_paid',
    ]);

    foreach (['2028-06', '2028-07', '2028-08', '2028-09', '2028-10', '2028-11'] as $index => $period) {
        $paid = $index >= 1 && $index <= 3;
        $movement = $paid ? Movement::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'happened_on' => $period.'-03',
            'movement_type' => 'expense',
            'description' => 'Pago histórico '.($index + 1),
            'amount' => 120.12,
            'source' => 'credit_installment',
        ]) : null;
        $credit->installments()->create([
            'user_id' => $user->id,
            'period_month' => $period.'-01',
            'due_date' => ($index === 0 ? '2028-07' : $period).'-27',
            'installment_number' => $index + 1,
            'amount' => 120.12,
            'paid_amount' => $paid ? 120.12 : 0,
            'paid_on' => $paid ? $period.'-03' : null,
            'movement_id' => $movement?->id,
            'status' => $paid ? 'paid' : 'pending',
            'notes' => 'Fila original '.($index + 1),
        ]);
    }

    return [$user, $account, $credit];
}

function focusManualInstallmentPayload(CreditInstallment $installment, array $changes = []): array
{
    return array_replace([
        'period_month' => $installment->period_month->format('Y-m'),
        'due_date' => $installment->due_date?->toDateString(),
        'amount' => $installment->amount,
        'status' => $installment->status,
        'paid_on' => $installment->paid_on?->toDateString(),
        'notes' => $installment->notes,
    ], $changes);
}

it('moves a stale installment to December and preserves history through recalculation and general saves', function () {
    [$user, $account, $credit] = focusManualInstallmentCredit();
    $before = $credit->installments()->orderBy('installment_number')->get();
    $paidHistory = $before->where('status', 'paid')->mapWithKeys(fn ($row) => [$row->id => $row->only([
        'period_month', 'due_date', 'amount', 'paid_amount', 'paid_on', 'movement_id', 'status', 'notes',
    ])])->all();
    $movementsBefore = Movement::orderBy('id')->get()->toArray();
    $stale = $before->first();

    $this->actingAs($user)->put(route('finance.credits.installments.update', $stale),
        focusManualInstallmentPayload($stale, [
            'period_month' => '2028-12', 'due_date' => '2028-12-28', 'amount' => 120.11,
        ]))->assertRedirect()->assertSessionHasNoErrors();
    foreach (['2028-10' => '2028-10-26', '2028-11' => '2028-11-25'] as $period => $dueDate) {
        $row = $credit->installments()->whereDate('period_month', $period.'-01')->firstOrFail();
        $this->put(route('finance.credits.installments.update', $row),
            focusManualInstallmentPayload($row, ['due_date' => $dueDate, 'amount' => 120.11]))
            ->assertRedirect()->assertSessionHasNoErrors();
    }

    $credit->refresh();
    $corrected = $credit->installments()->orderBy('installment_number')->get();
    expect($credit->is_manual_schedule)->toBeTrue()
        ->and($credit->due_day)->toBeNull()
        ->and($credit->first_due_month->format('Y-m'))->toBe('2028-07')
        ->and((float) $credit->total_amount)->toBe(720.69)
        ->and($corrected->pluck('installment_number')->all())->toBe([1, 2, 3, 4, 5, 6])
        ->and($corrected->pluck('period_month')->map->format('Y-m')->all())
        ->toBe(['2028-07', '2028-08', '2028-09', '2028-10', '2028-11', '2028-12'])
        ->and($corrected->last()->id)->toBe($stale->id)
        ->and($corrected->pluck('id')->sort()->values()->all())->toBe($before->pluck('id')->sort()->values()->all());
    foreach ($paidHistory as $id => $attributes) {
        expect(CreditInstallment::findOrFail($id)->only(array_keys($attributes)))->toEqual($attributes);
    }
    expect(Movement::orderBy('id')->get()->toArray())->toBe($movementsBefore);

    $correctedSnapshot = $corrected->toArray();
    $this->post(route('finance.credits.recalculate-dates'))->assertRedirect();
    $this->put(route('finance.credits.update', $credit), [
        'purchase_date' => '2028-06-12', 'name' => 'Compra de prueba confirmado', 'account_id' => $account->id,
        'total_amount' => 600, 'months' => 2, 'first_due_month' => '2028-10', 'due_day' => 27,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($credit->fresh()->name)->toBe('Compra de prueba confirmado')
        ->and($credit->installments()->orderBy('installment_number')->get()->toArray())->toBe($correctedSnapshot)
        ->and(Movement::orderBy('id')->get()->toArray())->toBe($movementsBefore);
});

it('protects an amount-only or date-only correction as a manual schedule', function (array $changes) {
    [$user, , $credit] = focusManualInstallmentCredit();
    $row = $credit->installments()->whereDate('period_month', '2028-10-01')->firstOrFail();
    $this->actingAs($user)->put(route('finance.credits.installments.update', $row),
        focusManualInstallmentPayload($row, $changes))->assertRedirect()->assertSessionHasNoErrors();

    expect($credit->fresh()->is_manual_schedule)->toBeTrue();
    $snapshot = $row->fresh()->toArray();
    $this->post(route('finance.credits.recalculate-dates'))->assertRedirect();
    expect($row->fresh()->toArray())->toBe($snapshot);
})->with([
    'amount' => [['amount' => 120.11]],
    'due date' => [['due_date' => '2028-10-26']],
    'period' => [['period_month' => '2028-12']],
]);

it('does not convert a notes-only edit or erase an existing partial payment', function () {
    [$user, $account, $credit] = focusManualInstallmentCredit();
    $row = $credit->installments()->whereDate('period_month', '2028-10-01')->firstOrFail();
    $movement = Movement::create([
        'user_id' => $user->id, 'account_id' => $account->id, 'happened_on' => '2028-09-25',
        'movement_type' => 'expense', 'amount' => 20, 'description' => 'Pago parcial histórico',
        'source' => 'credit_installment',
    ]);
    $row->update(['paid_amount' => 20, 'paid_on' => '2028-09-25', 'movement_id' => $movement->id]);
    $movementBefore = $movement->fresh()->toArray();
    $this->actingAs($user)->put(route('finance.credits.installments.update', $row),
        focusManualInstallmentPayload($row, ['notes' => 'Comprobado']))->assertRedirect()->assertSessionHasNoErrors();

    expect($credit->fresh()->is_manual_schedule)->toBeFalse()
        ->and((float) $row->fresh()->paid_amount)->toBe(20.0)
        ->and($row->fresh()->movement_id)->toBe($movement->id)
        ->and($movement->fresh()->toArray())->toBe($movementBefore);
    $this->put(route('finance.credits.installments.update', $row),
        focusManualInstallmentPayload($row->fresh(), ['due_date' => '2028-10-26']))
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($credit->fresh()->is_manual_schedule)->toBeTrue()
        ->and((float) $row->fresh()->paid_amount)->toBe(20.0)
        ->and($movement->fresh()->toArray())->toBe($movementBefore);
});

it('reopens only the residual when a paid installment amount increases without inventing a payment', function () {
    [$user, , $credit] = focusManualInstallmentCredit();
    $row = $credit->installments()->whereDate('period_month', '2028-09-01')->firstOrFail();
    $movementBefore = $row->movement->toArray();
    $this->actingAs($user)->put(route('finance.credits.installments.update', $row),
        focusManualInstallmentPayload($row, ['amount' => 200]))->assertRedirect()->assertSessionHasNoErrors();
    $row->refresh();
    expect($row->status)->toBe('pending')
        ->and((float) $row->paid_amount)->toBe(120.12)
        ->and($row->movement->toArray())->toBe($movementBefore);
    app(CreditEffectiveScheduleService::class)->flush();
    expect(app(CreditEffectiveScheduleService::class)->effectivePending($row))->toBe(79.88);
});

it('rolls back a calendar correction that would move a free payment to another installment', function () {
    [$user, $account, $credit] = focusManualInstallmentCredit();
    $row = $credit->installments()->whereDate('period_month', '2028-10-01')->firstOrFail();
    $movement = Movement::create([
        'user_id' => $user->id, 'account_id' => $account->id, 'happened_on' => '2028-09-25',
        'movement_type' => 'expense', 'amount' => 20, 'description' => 'Abono octubre', 'source' => 'credit_free_payment',
    ]);
    CreditFreePayment::create([
        'user_id' => $user->id, 'credit_purchase_id' => $credit->id, 'movement_id' => $movement->id,
        'amount_applied' => 20, 'paid_on' => '2028-09-25', 'payment_type' => 'free_payment',
    ]);
    $before = $credit->installments()->orderBy('id')->get()->toArray();
    $this->actingAs($user)->put(route('finance.credits.installments.update', $row),
        focusManualInstallmentPayload($row, ['period_month' => '2028-12', 'due_date' => '2028-12-28']))
        ->assertRedirect()->assertSessionHasErrors('period_month');
    expect($credit->fresh()->is_manual_schedule)->toBeFalse()
        ->and($credit->installments()->orderBy('id')->get()->toArray())->toBe($before)
        ->and($movement->fresh())->not->toBeNull();
});

it('preserves the existing guard for a linked planned payment', function () {
    [$user, $account, $credit] = focusManualInstallmentCredit();
    $row = $credit->installments()->whereDate('period_month', '2028-10-01')->firstOrFail();
    PlannedPayment::create([
        'user_id' => $user->id, 'account_id' => $account->id, 'name' => 'Compra de prueba octubre',
        'period_month' => '2028-10-01', 'due_date' => '2028-10-27', 'amount' => 120.12,
        'paid_amount' => 0, 'status' => 'pending', 'credit_installment_id' => $row->id,
    ]);
    $before = $row->toArray();
    $this->actingAs($user)->put(route('finance.credits.installments.update', $row),
        focusManualInstallmentPayload($row, ['amount' => 120.11]))->assertRedirect()->assertSessionHas('error');
    expect($credit->fresh()->is_manual_schedule)->toBeFalse()->and($row->fresh()->toArray())->toBe($before);
});
