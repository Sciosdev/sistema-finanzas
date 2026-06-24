<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('shows credit debts grouped by creditor with meaningful colors', function () {
    Carbon::setTestNow('2026-06-23 09:00:00');

    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $mpw = Account::where('user_id', $user->id)->where('name', 'MPW')->firstOrFail();

    $nuCredit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-23',
        'name' => 'NU celular',
        'total_amount' => 1200,
        'months' => 3,
        'first_due_month' => '2026-07-01',
        'due_day' => 25,
        'account_id' => $nu->id,
        'status' => 'active',
    ]);

    $mpwCredit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-23',
        'name' => 'MPW herramienta',
        'total_amount' => 800,
        'months' => 2,
        'first_due_month' => '2026-07-01',
        'due_day' => 27,
        'account_id' => $mpw->id,
        'status' => 'active',
    ]);

    foreach ([
        ['period' => '2026-06-01', 'due' => '2026-06-25', 'number' => 1, 'amount' => 400, 'paid' => 0, 'status' => 'pending', 'paid_on' => null],
        ['period' => '2026-06-01', 'due' => '2026-06-26', 'number' => 2, 'amount' => 200, 'paid' => 200, 'status' => 'paid', 'paid_on' => '2026-06-22'],
        ['period' => '2026-07-01', 'due' => '2026-07-25', 'number' => 3, 'amount' => 600, 'paid' => 0, 'status' => 'pending', 'paid_on' => null],
    ] as $installment) {
        CreditInstallment::create([
            'user_id' => $user->id,
            'credit_purchase_id' => $nuCredit->id,
            'period_month' => $installment['period'],
            'due_date' => $installment['due'],
            'installment_number' => $installment['number'],
            'amount' => $installment['amount'],
            'paid_amount' => $installment['paid'],
            'status' => $installment['status'],
            'paid_on' => $installment['paid_on'],
        ]);
    }

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $mpwCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-27',
        'installment_number' => 1,
        'amount' => 800,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('A quién se le debe')
        ->assertSee('Se le deben estos créditos a NU')
        ->assertSee('Se le deben estos créditos a MPW')
        ->assertSee('Se debe a NU')
        ->assertSee('Se debe a MPW')
        ->assertSee('Este mes se debe')
        ->assertSee('Se pagó este mes')
        ->assertSee('Siguiente mes')
        ->assertSee('Total se le debe')
        ->assertSee('$400.00')
        ->assertSee('$200.00')
        ->assertSee('$600.00')
        ->assertSee('$1,000.00')
        ->assertSee('#7c3aed')
        ->assertSee('#facc15');
});

it('shows credit summary cards without the Onix car credit', function () {
    Carbon::setTestNow('2026-06-23 09:00:00');

    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $onix = Account::where('user_id', $user->id)->where('name', 'Onix')->firstOrFail();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $carCredit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-23',
        'name' => 'Onix',
        'total_amount' => 150000,
        'months' => 30,
        'first_due_month' => '2026-06-01',
        'due_day' => 15,
        'account_id' => $onix->id,
        'status' => 'active',
    ]);

    $normalCredit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-23',
        'name' => 'Celular',
        'total_amount' => 12000,
        'months' => 6,
        'first_due_month' => '2026-06-01',
        'due_day' => 25,
        'account_id' => $nu->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $carCredit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-15',
        'installment_number' => 1,
        'amount' => 5000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $normalCredit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'installment_number' => 1,
        'amount' => 2000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $normalCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-25',
        'installment_number' => 2,
        'amount' => 2000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Vista realista sin el crédito del Onix')
        ->assertSee('Deuda sin Onix')
        ->assertSee('Pendiente sin Onix')
        ->assertSee('Este mes sin Onix')
        ->assertSee('Siguiente mes sin Onix')
        ->assertSee('$12,000.00')
        ->assertSee('$2,000.00');
});
