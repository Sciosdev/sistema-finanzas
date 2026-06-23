<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows credit debts grouped by creditor with meaningful colors', function () {
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

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $nuCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-25',
        'installment_number' => 1,
        'amount' => 1200,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

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
        ->assertSee('#7c3aed')
        ->assertSee('#facc15');
});
