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

function creditFilterUser(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function makeCreditWithInstallment(User $user, string $accountName, string $name, string $periodMonth): CreditPurchase
{
    $account = Account::where('user_id', $user->id)->where('name', $accountName)->firstOrFail();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => $name,
        'total_amount' => 400,
        'months' => 1,
        'first_due_month' => $periodMonth . '-01',
        'due_day' => 10,
        'account_id' => $account->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => $periodMonth . '-01',
        'due_date' => $periodMonth . '-10',
        'installment_number' => 1,
        'amount' => 400,
        'status' => 'pending',
    ]);

    return $credit;
}

it('renders the credit filter bar with creditor and current-month controls', function () {
    $user = creditFilterUser();
    makeCreditWithInstallment($user, 'NU', 'Tele NU', '2026-06');
    makeCreditWithInstallment($user, 'BBVA', 'Mueble BBVA', '2026-07');

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Filtrar lista de créditos')
        ->assertSee('Este mes')
        ->assertSee('Todos')
        ->assertSee('data-credit-filter="current-month"', false)
        ->assertSee('data-credit-filter="creditor"', false)
        ->assertSee('finance-credit-card', false)
        ->assertSee('data-creditor-key="creditor-nu"', false)
        ->assertSee('data-current-due=', false);
});

it('marks each credit card with its creditor key for filtering', function () {
    $user = creditFilterUser();
    makeCreditWithInstallment($user, 'NU', 'Tele NU', '2026-06');
    makeCreditWithInstallment($user, 'BBVA', 'Mueble BBVA', '2026-06');

    $html = $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'class="card finance-credit-card"'))->toBe(2)
        ->and($html)->toContain('data-creditor-key="creditor-nu"')
        ->and($html)->toContain('data-creditor-key="creditor-bbva"');
});
