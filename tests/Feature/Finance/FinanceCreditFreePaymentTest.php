<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\CreditEffectiveScheduleService;
use App\Services\Finance\CreditFreePaymentService;
use App\Services\Finance\FinanceAdvisorSnapshotService;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinancePeriodPlanService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-26 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Crea un crédito con las mensualidades indicadas.
 *
 * @param array<int, float> $amounts importe de cada mensualidad, en orden
 */
function makeCreditWithInstallments(
    User $user,
    array $amounts,
    string $firstPeriod = '2026-08',
    int $dueDay = 27,
    bool $manualSchedule = false,
    string $name = 'Crédito de prueba',
): CreditPurchase {
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'MPW')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Crédito / tarjeta')->firstOrFail();
    $first = Carbon::createFromFormat('!Y-m', $firstPeriod)->startOfMonth();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => $first->copy()->subMonth()->day(15)->toDateString(),
        'name' => $name,
        'total_amount' => round(array_sum($amounts), 2),
        'months' => count($amounts),
        'first_due_month' => $first->toDateString(),
        'due_day' => $dueDay,
        'is_manual_schedule' => $manualSchedule,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    foreach (array_values($amounts) as $index => $amount) {
        $period = $first->copy()->addMonths($index);

        CreditInstallment::create([
            'user_id' => $user->id,
            'credit_purchase_id' => $credit->id,
            'period_month' => $period->toDateString(),
            'due_date' => $period->copy()->day(min($dueDay, $period->daysInMonth))->toDateString(),
            'installment_number' => $index + 1,
            'amount' => round($amount, 2),
            'paid_amount' => 0,
            'status' => 'pending',
        ]);
    }

    return $credit->refresh();
}

/**
 * Pendientes efectivos del crédito en el mismo orden que las mensualidades.
 *
 * @return array<int, float>
 */
function effectiveDues(CreditPurchase $credit): array
{
    $schedule = app(CreditEffectiveScheduleService::class);
    $schedule->flush();

    $credit = CreditPurchase::with(['installments', 'freePayments'])->findOrFail($credit->id);
    $pending = $schedule->effectivePendingFor($credit);

    return $credit->installments
        ->sortBy('installment_number')
        ->map(fn (CreditInstallment $installment) => $pending[$installment->id] ?? null)
        ->values()
        ->all();
}

function addFreePayment(User $user, CreditPurchase $credit, float $amount, string $paidOn = '2026-07-26'): CreditFreePayment
{
    app(CreditEffectiveScheduleService::class)->flush();

    return app(CreditFreePaymentService::class)->createFreePayment(
        CreditPurchase::with(['installments', 'freePayments'])->findOrFail($credit->id),
        Carbon::parse($paidOn),
        $amount,
    );
}

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

it('discounts a free payment from the effective amount due of a single installment credit', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [705.30]);

    addFreePayment($user, $credit, 300);

    $schedule = app(CreditEffectiveScheduleService::class);
    $schedule->flush();
    $fresh = CreditPurchase::with(['installments', 'freePayments'])->findOrFail($credit->id);

    expect($schedule->balanceDue($fresh))->toBe(405.30)
        ->and(effectiveDues($credit))->toBe([405.30])
        // El calendario contratado no se toca.
        ->and((float) $fresh->installments->first()->amount)->toBe(705.30)
        ->and((float) $fresh->installments->first()->paid_amount)->toBe(0.0)
        ->and($fresh->installments->first()->status)->toBe('pending');
});

it('spreads several free payments across the pending installments in date order', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [500, 500, 500]);

    // La tarjeta no deja adelantar dos mensualidades de un golpe, así que se
    // llega a los $700 con dos abonos: el primero salda la más próxima y el
    // segundo empieza a comerse la siguiente.
    addFreePayment($user, $credit, 500);
    addFreePayment($user, $credit, 200);

    expect(effectiveDues($credit))->toBe([0.0, 300.0, 500.0]);
});

it('applies the real paid amount first and only then the free payment', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [500, 500]);

    $credit->installments()->where('installment_number', 1)->update(['paid_amount' => 200]);

    addFreePayment($user, $credit, 150);

    expect(effectiveDues($credit))->toBe([150.0, 500.0]);
});

it('never lets a free payment land on an installment from a month before the payment', function () {
    $user = User::factory()->create();
    // Mensualidad vieja pendiente (residuo del arranque del sistema) + las que vienen.
    $credit = makeCreditWithInstallments($user, [189.85, 189.85, 189.85], '2026-06');

    addFreePayment($user, $credit, 189.85, '2026-07-26');

    // Junio queda intacto: el abono se aplica a la primera de julio en adelante.
    expect(effectiveDues($credit))->toBe([189.85, 0.0, 189.85]);
});

it('keeps the virtual distribution in place after the covered installment is paid', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [500, 500]);

    addFreePayment($user, $credit, 500);
    expect(effectiveDues($credit))->toBe([0.0, 500.0]);

    $first = $credit->installments()->where('installment_number', 1)->firstOrFail();

    $this->actingAs($user)->post(route('finance.credits.installments.paid', $first), [
        'paid_on' => '2026-08-27',
    ])->assertRedirect();

    // Ni se cobró dinero de más ni el abono se recorrió a la segunda mensualidad.
    expect(Movement::where('source', 'credit_installment')->count())->toBe(0)
        ->and((float) $first->fresh()->paid_amount)->toBe(0.0)
        ->and(effectiveDues($credit))->toBe([0.0, 500.0]);
});

it('recalculates the effective schedule when a free payment is deleted and restored', function () {
    $user = makeFinanceOwner(User::factory()->create());
    $credit = makeCreditWithInstallments($user, [500, 500]);

    $payment = addFreePayment($user, $credit, 300);
    expect(effectiveDues($credit))->toBe([200.0, 500.0]);

    $this->actingAs($user)
        ->delete(route('finance.credits.free-payments.destroy', $payment))
        ->assertRedirect();

    expect(effectiveDues($credit))->toBe([500.0, 500.0]);

    $snapshot = DeleteSnapshot::where('user_id', $user->id)
        ->where('entity_type', 'credit_free_payment')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($user)->post(route('finance.security.undo-delete', $snapshot->token))->assertRedirect();

    expect(effectiveDues($credit))->toBe([200.0, 500.0]);
});

it('settles the credit when a free payment covers the last pending installment', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [705.30]);

    addFreePayment($user, $credit, 705.30);

    $schedule = app(CreditEffectiveScheduleService::class);
    $schedule->flush();
    $fresh = CreditPurchase::with(['installments', 'freePayments'])->findOrFail($credit->id);

    expect(effectiveDues($credit))->toBe([0.0])
        ->and($schedule->balanceDue($fresh))->toBe(0.0)
        ->and($fresh->status)->toBe('paid')
        ->and($schedule->nextPayableInstallment($fresh))->toBeNull()
        ->and($schedule->maxFreePayment($fresh))->toBe(0.0);

    $obligations = app(FinanceSummaryService::class)->monthObligations(
        $user,
        Carbon::parse('2026-08-01')->startOfMonth(),
        Carbon::parse('2026-08-01')->endOfMonth(),
    );

    expect($obligations->where('source', 'credit')->sum(fn (array $row) => (float) $row['amount_due']))->toBe(0.0);
});

it('rejects a free payment bigger than the next installment without creating anything', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [500, 500, 500]);

    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->post(route('finance.credits.free-payments.store', $credit), [
            'paid_on' => '2026-07-26',
            'amount' => 6000,
        ])
        ->assertRedirect(route('finance.credits.index'))
        ->assertSessionHas('error');

    expect(CreditFreePayment::where('credit_purchase_id', $credit->id)->count())->toBe(0)
        ->and(Movement::where('source', 'credit_free_payment')->count())->toBe(0)
        ->and(effectiveDues($credit))->toBe([500.0, 500.0, 500.0]);
});

it('rejects a free payment that exceeds the remaining room of the next installment', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [500, 500]);

    addFreePayment($user, $credit, 400);

    // Solo quedan $100 de la mensualidad próxima: $150 debe rebotar.
    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->post(route('finance.credits.free-payments.store', $credit), [
            'paid_on' => '2026-07-26',
            'amount' => 150,
        ])
        ->assertSessionHas('error');

    expect(CreditFreePayment::where('credit_purchase_id', $credit->id)->count())->toBe(1)
        ->and(Movement::where('source', 'credit_free_payment')->count())->toBe(1)
        ->and(effectiveDues($credit))->toBe([100.0, 500.0]);
});

it('creates exactly one expense in the chosen account and does not discount the money twice', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [705.30]);
    $other = Account::where('user_id', $user->id)->where('name', 'NU')->first()
        ?? Account::where('user_id', $user->id)->whereKeyNot($credit->account_id)->firstOrFail();

    $this->actingAs($user)->post(route('finance.credits.free-payments.store', $credit), [
        'paid_on' => '2026-07-26',
        'amount' => 300,
        'account_id' => $other->id,
    ])->assertSessionHas('success');

    $movements = Movement::where('user_id', $user->id)->get();

    expect($movements)->toHaveCount(1)
        ->and((float) $movements->first()->amount)->toBe(300.0)
        ->and($movements->first()->account_id)->toBe($other->id)
        ->and($movements->first()->movement_type)->toBe('expense');

    // Pagar la mensualidad después solo debe mover los $405.30 que faltan.
    $installment = $credit->installments()->firstOrFail();
    $this->actingAs($user)->post(route('finance.credits.installments.paid', $installment), [
        'paid_on' => '2026-08-27',
    ])->assertRedirect();

    $total = Movement::where('user_id', $user->id)->sum('amount');

    expect(round((float) $total, 2))->toBe(705.30)
        ->and(Movement::where('source', 'credit_installment')->count())->toBe(1)
        ->and(round((float) Movement::where('source', 'credit_installment')->first()->amount, 2))->toBe(405.30);
});

it('keeps cents exact when the free payment splits an odd instalment', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [235.10, 235.10, 235.10]);

    addFreePayment($user, $credit, 0.01);
    expect(effectiveDues($credit))->toBe([235.09, 235.10, 235.10]);

    addFreePayment($user, $credit, 235.09);
    expect(effectiveDues($credit))->toBe([0.0, 235.10, 235.10]);

    // Un importe con tres decimales se redondea a centavos una sola vez
    // (100.005 -> 100.01) y el pendiente sigue cuadrando al centavo.
    addFreePayment($user, $credit, 100.005);
    expect(effectiveDues($credit))->toBe([0.0, 135.09, 235.10]);
});

it('works the same on a manual schedule credit with irregular installments', function () {
    $user = User::factory()->create();
    $credit = makeCreditWithInstallments($user, [1000.55, 250.45], '2026-08', 15, true);

    addFreePayment($user, $credit, 1000.55);
    expect(effectiveDues($credit))->toBe([0.0, 250.45]);

    addFreePayment($user, $credit, 250.45);
    expect(effectiveDues($credit))->toBe([0.0, 0.0]);
    expect(CreditPurchase::findOrFail($credit->id)->status)->toBe('paid');
});

it('uses the effective amount in the global and per creditor credit summaries', function () {
    $user = User::factory()->create();
    // Una mensualidad este mes y otra el siguiente, como una tarjeta real.
    $credit = makeCreditWithInstallments($user, [1000, 705.30], '2026-07');

    addFreePayment($user, $credit, 300);

    $response = $this->actingAs($user)->get(route('finance.credits.index'))->assertOk();
    $summary = $response->viewData('summary');
    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'MPW');

    expect($summary['current_month'])->toBe(700.0)
        ->and($summary['next_month'])->toBe(705.30)
        ->and($creditor['current_due'])->toBe(700.0)
        ->and($creditor['next_due'])->toBe(705.30)
        // El pago por selección también ofrece el importe efectivo.
        ->and(round((float) $creditor['pending_installments'][0]['amount'], 2))->toBe(700.0);
});

it('shows the effective amount in obligations the planner and the advisor snapshot', function () {
    $user = makeFinanceOwner(User::factory()->create());
    $credit = makeCreditWithInstallments($user, [1000, 500], '2026-07');

    addFreePayment($user, $credit, 400);

    $obligations = app(FinanceSummaryService::class)->monthObligations(
        $user,
        Carbon::parse('2026-07-01')->startOfMonth(),
        Carbon::parse('2026-07-31')->endOfMonth(),
    );
    $obligation = $obligations->firstWhere('source', 'credit');

    expect($obligation['amount'])->toBe(1000.0)
        ->and($obligation['free_applied'])->toBe(400.0)
        ->and($obligation['amount_due'])->toBe(600.0)
        ->and($obligation['credit_balance_due'])->toBe(1100.0);

    $creditAccounts = collect(app(FinancePeriodPlanService::class)->build($user)['credit_accounts']);

    expect($creditAccounts)->toHaveCount(1)
        ->and((float) $creditAccounts->sum('month_due_total'))->toBe(600.0);

    $snapshot = app(FinanceAdvisorSnapshotService::class)->build($user);
    $advisorCredit = collect($snapshot['credits'] ?? [])->firstWhere('name', 'Crédito de prueba');

    expect((float) $advisorCredit['next_due_amount'])->toBe(600.0)
        ->and((float) $advisorCredit['balance_due'])->toBe(1100.0);
});
