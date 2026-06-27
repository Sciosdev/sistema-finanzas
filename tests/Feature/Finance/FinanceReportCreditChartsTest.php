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

it('renders credit chart containers and data on the reports page', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update(['credit_limit' => 9000]);

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
        ->get(route('finance.reports.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Créditos y tarjetas')
        ->assertSee('reports-credit-by-card-donut', false)
        ->assertSee('reports-credit-available-bar', false)
        ->assertSee('reports-credit-upcoming-bar', false)
        // El JSON de datos incluye las nuevas secciones de crédito:
        ->assertSee('creditByCard', false)
        ->assertSee('creditAvailable', false);
});

it('renders a separate credit chart set that excludes the Onix credit', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $onix = Account::where('user_id', $user->id)->where('name', 'Onix')->firstOrFail();

    foreach ([[$nu, 'Tele NU', 1000], [$onix, 'Carro Onix', 160000]] as [$account, $name, $amount]) {
        $credit = CreditPurchase::create([
            'user_id' => $user->id, 'purchase_date' => '2026-06-01', 'name' => $name,
            'total_amount' => $amount, 'months' => 1, 'first_due_month' => '2026-06-01',
            'due_day' => 27, 'account_id' => $account->id, 'status' => 'active',
        ]);
        CreditInstallment::create([
            'user_id' => $user->id, 'credit_purchase_id' => $credit->id, 'period_month' => '2026-06-01',
            'due_date' => '2026-06-27', 'installment_number' => 1, 'amount' => $amount, 'paid_amount' => 0, 'status' => 'pending',
        ]);
    }

    $response = $this->actingAs($user)
        ->get(route('finance.reports.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Créditos sin Onix')
        ->assertSee('reports-credit-by-card-donut-noonix', false);

    preg_match('/id="finance-report-chart-data">(.*?)<\/script>/s', $response->getContent(), $matches);
    $data = json_decode($matches[1] ?? '{}', true);

    $fullCards = collect($data['creditByCard']['rows'] ?? [])->pluck('name');
    $noOnixCards = collect($data['creditByCardNoOnix']['rows'] ?? [])->pluck('name');

    // El set completo incluye Onix y NU; el set "sin Onix" tiene NU pero NO Onix.
    expect($fullCards)->toContain('Onix')
        ->and($fullCards)->toContain('NU')
        ->and($noOnixCards)->toContain('NU')
        ->and($noOnixCards)->not->toContain('Onix');
});
