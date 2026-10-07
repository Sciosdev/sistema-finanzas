<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\CreditFreePaymentService;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-10-07 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

function creditsPerformancePurchase(User $user, string $name = 'Compra de prueba', bool $paid = false): CreditPurchase
{
    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $credit = CreditPurchase::create([
        'user_id' => $user->id, 'account_id' => $account->id,
        'purchase_date' => '2026-10-01', 'name' => $name,
        'total_amount' => 400, 'months' => 1, 'first_due_month' => '2026-10-01',
        'due_day' => 25, 'status' => $paid ? 'paid' : 'active',
    ]);
    $credit->installments()->create([
        'user_id' => $user->id, 'period_month' => '2026-10-01',
        'due_date' => '2026-10-25', 'installment_number' => 1,
        'amount' => 400, 'paid_amount' => $paid ? 400 : 0,
        'paid_on' => $paid ? '2026-10-05' : null, 'status' => $paid ? 'paid' : 'pending',
    ]);

    return $credit;
}

it('keeps the list compact and avoids aggregate queries for each credit', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    for ($i = 0; $i < 40; $i++) {
        creditsPerformancePurchase($user, 'Compra '.$i, $i > 9);
    }

    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = $this->actingAs($user)->get(route('finance.credits.index'))->assertOk();
    $queries = collect(DB::getQueryLog());
    DB::disableQueryLog();
    $aggregateQueries = $queries->filter(fn ($query) => str_contains(strtolower($query['query']), 'sum(')
        && preg_match('/finance_credit_(installments|free_payments)/', $query['query']));

    expect($aggregateQueries)->toHaveCount(0)
        ->and(strlen($response->getContent()))->toBeLessThan(500000)
        ->and(substr_count($response->getContent(), 'class="card finance-credit-card"'))->toBe(40);
    $response->assertSee('data-credit-details', false)
        ->assertDontSee('id="credit-form-', false)
        ->assertSee('$4,000.00')
        ->assertSee('$12,000.00');
});

it('loads only the requested credit details and rejects another owners credit', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $credit = creditsPerformancePurchase($user);
    $installment = $credit->installments()->first();
    $other = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($other);
    $otherCredit = creditsPerformancePurchase($other, 'Otra compra');

    $this->actingAs($user)->get(route('finance.credits.details', $credit))
        ->assertOk()->assertHeader('X-Finance-Credit-Details', (string) $credit->id)
        ->assertSee('id="credit-form-'.$credit->id.'"', false)
        ->assertSee('installment-form-'.$installment->id, false)
        ->assertSee('2026-10-25')->assertDontSee('Otra compra');
    $this->get(route('finance.credits.details', $otherCredit))->assertForbidden();
    expect($credit->fresh()->total_amount)->toBe('400.00')
        ->and($installment->fresh()->status)->toBe('pending');
});

it('supports a full page fallback including paid credit details', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $credit = creditsPerformancePurchase($user, 'Compra pagada', true);
    $other = creditsPerformancePurchase($user, 'Otra compra');

    $this->actingAs($user)->get(route('finance.credits.index', ['credit' => $credit->id]))
        ->assertOk()->assertSee('id="credit-form-'.$credit->id.'"', false)
        ->assertDontSee('id="credit-form-'.$other->id.'"', false)
        ->assertSee('var activeStatus = "paid"', false);
});

it('preserves payment and refund totals and still reads fresh totals for writes', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);
    $credit = creditsPerformancePurchase($user);
    $installment = $credit->installments()->first();
    $installment->update(['paid_amount' => 50]);
    foreach ([['cash', 25], ['refund', 30]] as [$type, $amount]) {
        CreditFreePayment::create([
            'user_id' => $user->id, 'credit_purchase_id' => $credit->id,
            'paid_on' => '2026-10-06', 'payment_type' => $type,
            'amount_applied' => $amount,
            'target_installment_id' => $type === 'refund' ? $installment->id : null,
        ]);
    }
    $credit->load(['installments', 'freePayments']);
    $service = app(CreditFreePaymentService::class);
    expect($service->totals($credit, true))->toBe($service->totals($credit));
    $installment->update(['paid_amount' => 100]);
    expect($service->totals($credit)['balance_due'])->toBe(245.0);
});

it('checks existing catalogs in four queries while preserving user settings', function () {
    $user = User::factory()->create();
    $catalogs = app(FinanceCatalogService::class);
    $catalogs->ensureForUser($user);
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update(['color' => '#123456', 'credit_limit' => 12000]);

    DB::enableQueryLog();
    DB::flushQueryLog();
    $catalogs->ensureForUser($user);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect($queries)->toHaveCount(4)
        ->and($nu->fresh()->color)->toBe('#123456')
        ->and($nu->fresh()->credit_limit)->toBe('12000.00');
});
