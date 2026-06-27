<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('shows credit line limit, used and available per card', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update(['credit_limit' => 9000, 'statement_day' => 25, 'payment_day' => 27]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Compra NU',
        'total_amount' => 1000,
        'months' => 1,
        'first_due_month' => '2026-06-01',
        'due_day' => 27,
        'account_id' => $nu->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-27',
        'installment_number' => 1,
        'amount' => 1000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Límite $9,000.00')
        ->assertSee('Usado $1,000.00')
        ->assertSee('Disponible $8,000.00')
        ->assertSee('Paga el día 27 de cada mes')
        ->assertSee('Crédito disponible en todas tus tarjetas');
});

it('recalculates existing credit due dates using the card cycle without changing amounts', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update(['statement_day' => 25, 'payment_day' => 27]);

    // Crédito viejo con fechas "equivocadas" (no calculadas con el ciclo).
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-10',
        'name' => 'Compra vieja NU',
        'total_amount' => 600,
        'months' => 2,
        'first_due_month' => '2026-09-01',
        'due_day' => 5,
        'account_id' => $nu->id,
        'status' => 'active',
    ]);
    $i1 = CreditInstallment::create([
        'user_id' => $user->id, 'credit_purchase_id' => $credit->id, 'period_month' => '2026-09-01',
        'due_date' => '2026-09-05', 'installment_number' => 1, 'amount' => 300, 'paid_amount' => 300, 'status' => 'paid', 'paid_on' => '2026-09-05',
    ]);
    $i2 = CreditInstallment::create([
        'user_id' => $user->id, 'credit_purchase_id' => $credit->id, 'period_month' => '2026-10-01',
        'due_date' => '2026-10-05', 'installment_number' => 2, 'amount' => 300, 'paid_amount' => 0, 'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('finance.credits.recalculate-dates'))
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionHas('recalculated', fn (array $rows) => collect($rows)->contains(fn (array $row) => $row['name'] === 'Compra vieja NU'
            && $row['from'] === '2026-09 (día 5)'
            && $row['to'] === '2026-06 (día 27)'));

    $credit->refresh();

    expect($credit->first_due_month->format('Y-m'))->toBe('2026-06')
        ->and($credit->due_day)->toBe(27)
        ->and($i1->fresh()->due_date->toDateString())->toBe('2026-06-27')
        ->and($i2->fresh()->due_date->toDateString())->toBe('2026-07-27')
        // No cambia montos ni el pago ya hecho:
        ->and((float) $i1->fresh()->amount)->toBe(300.0)
        ->and($i1->fresh()->status)->toBe('paid')
        ->and((float) $i2->fresh()->amount)->toBe(300.0);
});

it('does not recalculate credits whose card has no cycle', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $bbva = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();

    $credit = CreditPurchase::create([
        'user_id' => $user->id, 'purchase_date' => '2026-06-10', 'name' => 'Sin ciclo',
        'total_amount' => 300, 'months' => 1, 'first_due_month' => '2026-08-01', 'due_day' => 10,
        'account_id' => $bbva->id, 'status' => 'active',
    ]);

    $this->actingAs($user)->post(route('finance.credits.recalculate-dates'));

    expect($credit->fresh()->first_due_month->format('Y-m'))->toBe('2026-08')
        ->and($credit->fresh()->due_day)->toBe(10);
});

it('does not show credit line usage when the card has no limit', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $bbva = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Compra BBVA',
        'total_amount' => 500,
        'months' => 1,
        'first_due_month' => '2026-06-01',
        'due_day' => 10,
        'account_id' => $bbva->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'installment_number' => 1,
        'amount' => 500,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertDontSee('Disponible');
});
