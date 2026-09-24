<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceProjectionService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-09-23 12:00:00'));
afterEach(fn () => Carbon::setTestNow());

function reconciliationFixture(): array
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-10',
        'name' => 'Equipo familiar',
        'total_amount' => 9600,
        'months' => 2,
        'first_due_month' => '2026-09-01',
        'due_day' => 26,
        'account_id' => $account->id,
        'status' => 'active',
    ]);
    $installment = CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-09-01',
        'due_date' => '2026-09-26',
        'installment_number' => 1,
        'amount' => 4800,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);
    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-10-01',
        'due_date' => '2026-10-26',
        'installment_number' => 2,
        'amount' => 4800,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);
    $planned = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-09-01',
        'due_date' => '2026-09-20',
        'name' => 'Pago del equipo',
        'amount' => 4800,
        'paid_amount' => 0,
        'status' => 'pending',
        'account_id' => $account->id,
    ]);

    return [$user, $credit, $installment, $planned, $account];
}

it('reconciles an existing planned expense with a pending installment without another expense', function () {
    [$user, $credit, $installment, $planned] = reconciliationFixture();

    $this->actingAs($user)->post(route('finance.planned.paid', $planned), ['paid_on' => '2026-09-20']);
    $movement = Movement::where('source', 'planned_payment')->firstOrFail();

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee('Conciliar con una mensualidad')
        ->assertSee('Posible pago ya registrado en Flujo planeado');

    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    expect($planned->fresh()->credit_installment_id)->toBe($installment->id)
        ->and($installment->fresh()->status)->toBe('paid')
        ->and((float) $installment->fresh()->paid_amount)->toBe(4800.0)
        ->and($installment->fresh()->movement_id)->toBe($movement->id)
        ->and(Movement::where('user_id', $user->id)->count())->toBe(1)
        ->and($credit->fresh()->status)->toBe('partially_paid');

    $totals = app(FinanceSummaryService::class)->monthSummary($user, '2026-09')['obligation_totals'];
    expect($totals['total'])->toBe(4800.0)
        ->and($totals['paid'])->toBe(4800.0)
        ->and($totals['pending'])->toBe(0.0);

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertDontSee('planned-payment-actions-'.$planned->id)
        ->assertSee('Equipo familiar');

    $this->actingAs($user)
        ->post(route('finance.credits.installments.paid', $installment))
        ->assertSessionHas('error');
    expect(Movement::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('finance.planned.unlink-installment', $planned))
        ->assertSessionHas('success');

    expect($planned->fresh()->credit_installment_id)->toBeNull()
        ->and($installment->fresh()->status)->toBe('pending')
        ->and($installment->fresh()->movement_id)->toBeNull()
        ->and(Movement::whereKey($movement->id)->exists())->toBeTrue();
});

it('links future copies once and pays a linked installment from Credits with one expense', function () {
    [$user, $credit, $installment, $planned, $creditAccount] = reconciliationFixture();
    $cashAccount = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();
    $nextInstallment = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();
    $futurePlanned = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-10-01',
        'due_date' => '2026-10-20',
        'name' => $planned->name,
        'amount' => $planned->amount,
        'paid_amount' => 0,
        'status' => 'pending',
        'account_id' => $planned->account_id,
    ]);

    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    expect($planned->fresh()->status)->toBe('pending')
        ->and($futurePlanned->fresh()->credit_installment_id)->toBe($nextInstallment->id)
        ->and(Movement::where('user_id', $user->id)->count())->toBe(0);

    $september = app(FinanceSummaryService::class)->monthSummary($user, '2026-09')['obligation_totals'];
    $october = app(FinanceSummaryService::class)->monthSummary($user, '2026-10')['obligation_totals'];
    expect($september['pending'])->toBe(4800.0)
        ->and($october['pending'])->toBe(4800.0);

    $octoberObligation = app(FinanceSummaryService::class)
        ->monthObligations($user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'))
        ->firstWhere('source', 'credit');
    expect($octoberObligation['due_date']->toDateString())->toBe('2026-10-20');

    $projection = app(FinanceProjectionService::class)->projectUntil($user, Carbon::parse('2026-10-31'));
    expect($projection['summary']['total_installments'])->toBe(9600.0)
        ->and($projection['summary']['total_payments'])->toBe(0.0)
        ->and(collect($projection['days'])->firstWhere('date', '2026-10-20')['installments'])->toHaveCount(1);

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertDontSee('planned-payment-actions-'.$futurePlanned->id)
        ->assertSee('Equipo familiar');

    $this->actingAs($user)
        ->post(route('finance.credits.installments.paid', $installment), [
            'paid_on' => '2026-09-20',
            'payment_account_id' => $cashAccount->id,
        ])
        ->assertSessionHas('success');

    $movement = Movement::where('user_id', $user->id)->sole();
    expect($movement->account_id)->toBe($cashAccount->id)
        ->and($movement->source)->toBe('credit_installment')
        ->and($planned->fresh()->status)->toBe('paid')
        ->and($installment->fresh()->status)->toBe('paid')
        ->and($planned->fresh()->movement_id)->toBe($movement->id)
        ->and($installment->fresh()->movement_id)->toBe($movement->id)
        ->and($credit->fresh()->status)->toBe('partially_paid');

    $this->actingAs($user)
        ->post(route('finance.planned.copy'), ['source_month' => '2026-10', 'target_month' => '2026-11'])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-11']));
    expect(PlannedPayment::where('user_id', $user->id)->count())->toBe(2);
});

it('keeps bulk creditor payments in sync with a linked planned payment', function () {
    [$user, , $installment, $planned, $creditAccount] = reconciliationFixture();
    $cashAccount = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();
    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('finance.credits.creditors.pay-month'), [
            'account_id' => $creditAccount->id,
            'creditor_name' => $creditAccount->name,
            'paid_on' => '2026-09-20',
            'payment_account_id' => $cashAccount->id,
        ])
        ->assertSessionHas('success');

    $movement = Movement::where('user_id', $user->id)->sole();
    expect($movement->account_id)->toBe($cashAccount->id)
        ->and($planned->fresh()->status)->toBe('paid')
        ->and($planned->fresh()->movement_id)->toBe($movement->id)
        ->and($installment->fresh()->movement_id)->toBe($movement->id);
});

it('lets Credits unify future planned copies after an earlier month was reconciled', function () {
    [$user, $credit, $installment, $planned] = reconciliationFixture();
    $this->actingAs($user)->post(route('finance.planned.paid', $planned));
    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    $next = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();
    $future = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-10-01',
        'due_date' => '2026-10-20',
        'name' => $planned->name,
        'amount' => $planned->amount,
        'paid_amount' => 0,
        'status' => 'pending',
        'account_id' => $planned->account_id,
        'is_credit' => true,
    ]);

    $this->actingAs($user)
        ->post(route('finance.credits.sync-planned-series', $credit))
        ->assertSessionHas('success');
    expect($future->fresh()->credit_installment_id)->toBe($next->id)
        ->and($future->fresh()->status)->toBe('pending')
        ->and($future->fresh()->is_credit)->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.credits.sync-planned-series', $credit))
        ->assertSessionHas('success');
    expect(PlannedPayment::where('user_id', $user->id)->count())->toBe(2);
});

it('syncs a selected credit payment and its planned copy without a second movement', function () {
    [$user, , $installment, $planned] = reconciliationFixture();
    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('finance.credits.installments.pay-selected'), [
            'installment_ids' => [$installment->id],
            'paid_on' => '2026-09-20',
        ])
        ->assertSessionHas('success');

    expect(Movement::where('user_id', $user->id)->count())->toBe(1)
        ->and($planned->fresh()->status)->toBe('paid')
        ->and($installment->fresh()->movement_id)->toBe($planned->fresh()->movement_id);
});

it('unifies a new copied payment with the known credit series automatically', function () {
    [$user, $credit, $installment, $planned] = reconciliationFixture();
    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->post(route('finance.planned.store'), [
            'period_month' => '2026-10',
            'due_date' => '2026-10-20',
            'name' => $planned->name,
            'amount' => $planned->amount,
            'account_id' => $planned->account_id,
        ])
        ->assertSessionHas('success', 'La cuota ya aparece en Créditos; quedó unificada y se paga desde ahí.');

    $next = $credit->installments()->whereDate('period_month', '2026-10-01')->firstOrFail();
    $copy = PlannedPayment::where('user_id', $user->id)
        ->whereDate('period_month', '2026-10-01')->sole();
    expect($copy->credit_installment_id)->toBe($next->id)
        ->and(Movement::where('user_id', $user->id)->count())->toBe(0);
});

it('can link an imported credit-category payment before it is paid', function () {
    [$user, , $installment, $planned] = reconciliationFixture();
    $planned->update(['is_credit' => true]);

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee('Conciliar con una mensualidad');

    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    expect($planned->fresh()->credit_installment_id)->toBe($installment->id)
        ->and($planned->fresh()->is_credit)->toBeFalse()
        ->and($installment->fresh()->status)->toBe('pending')
        ->and(Movement::where('user_id', $user->id)->count())->toBe(0);
});

it('plans one credit obligation for every month of a 33-month schedule', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-10',
        'name' => 'Compra Onix',
        'total_amount' => 165000,
        'months' => 33,
        'first_due_month' => '2026-06-01',
        'due_day' => 26,
        'account_id' => $account->id,
        'status' => 'active',
    ]);

    foreach (range(1, 33) as $number) {
        $month = Carbon::parse('2026-06-01')->addMonths($number - 1);
        $installment = CreditInstallment::create([
            'user_id' => $user->id,
            'credit_purchase_id' => $credit->id,
            'period_month' => $month->toDateString(),
            'due_date' => $month->copy()->day(26)->toDateString(),
            'installment_number' => $number,
            'amount' => 5000,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        if ($number >= 5 && $number <= 7) {
            PlannedPayment::create([
                'user_id' => $user->id,
                'period_month' => $month->toDateString(),
                'due_date' => $month->copy()->day(20)->toDateString(),
                'name' => 'Coca - Onix',
                'amount' => 5000,
                'status' => 'pending',
                'credit_installment_id' => $installment->id,
            ]);
        }
    }

    $this->actingAs($user)
        ->post(route('finance.credits.payment-plan', $credit), ['planned_payment_day' => 20])
        ->assertSessionHas('success');

    $installments = $credit->installments()->with(['creditPurchase', 'plannedPayment'])->orderBy('installment_number')->get();
    expect($installments)->toHaveCount(33);
    foreach ($installments as $installment) {
        expect($installment->effectiveDueDate()?->day)->toBe(20);
    }

    $this->actingAs($user)
        ->post(route('finance.planned.copy'), ['source_month' => '2026-12', 'target_month' => '2027-01'])
        ->assertSessionHas('success');
    expect(PlannedPayment::where('user_id', $user->id)->count())->toBe(3);

    foreach (['2027-01', '2029-02'] as $month) {
        $obligations = app(FinanceSummaryService::class)
            ->monthObligations($user, Carbon::parse($month.'-01'), Carbon::parse($month.'-01')->endOfMonth());
        expect($obligations->where('source', 'credit')->count())->toBe(1)
            ->and($obligations->firstWhere('source', 'credit')['due_date']->format('Y-m-d'))->toBe($month.'-20');

        $this->actingAs($user)
            ->get(route('finance.planned.index', ['month' => $month]))
            ->assertOk()
            ->assertSee($month.'-20')
            ->assertSee('Compra Onix');
    }

    $projection = app(FinanceProjectionService::class)->projectUntil($user, Carbon::parse('2027-01-31'));
    expect(collect($projection['days'])->firstWhere('date', '2027-01-20')['installments'])->toHaveCount(1);

    $january = $installments->firstWhere('installment_number', 8);
    $this->actingAs($user)
        ->post(route('finance.credits.installments.paid', $january), [
            'paid_on' => '2027-01-20',
            'payment_account_id' => $account->id,
        ])
        ->assertSessionHas('success');
    expect(Movement::where('user_id', $user->id)->count())->toBe(1)
        ->and($january->fresh()->status)->toBe('paid');
});

it('reconciles a credit installment already paid with a pending planned payment', function () {
    [$user, , $installment, $planned] = reconciliationFixture();

    $this->actingAs($user)->post(route('finance.credits.installments.paid', $installment), ['paid_on' => '2026-09-20']);
    $movement = Movement::where('source', 'credit_installment')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('success');

    expect($planned->fresh()->status)->toBe('paid')
        ->and($planned->fresh()->movement_id)->toBe($movement->id)
        ->and($planned->fresh()->credit_installment_id)->toBe($installment->id)
        ->and(Movement::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('finance.planned.unlink-installment', $planned))
        ->assertSessionHas('success');

    expect($planned->fresh()->status)->toBe('pending')
        ->and($installment->fresh()->status)->toBe('paid')
        ->and(Movement::whereKey($movement->id)->exists())->toBeTrue();
});

it('rejects ambiguous or foreign installment reconciliation', function () {
    [$user, , $installment, $planned] = reconciliationFixture();
    $this->actingAs($user)->post(route('finance.planned.paid', $planned));
    $installment->update(['amount' => 4900]);

    $this->actingAs($user)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertSessionHas('error');

    expect($installment->fresh()->status)->toBe('pending')
        ->and($planned->fresh()->credit_installment_id)->toBeNull();

    $other = User::factory()->create();
    $this->actingAs($other)
        ->post(route('finance.planned.link-installment', $planned), ['credit_installment_id' => $installment->id])
        ->assertForbidden();
});
