<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
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
        ->assertSee('Misma mensualidad; se cuenta una vez');

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
