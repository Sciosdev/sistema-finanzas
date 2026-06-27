<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('saves the dashboard layout server-side for the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('finance.dashboard.layout'), [
            'layout' => [
                'order' => ['expenses-real', 'income-real'],
                'sizes' => ['income-real' => 2],
                'hidden' => ['san-juan-profit'],
                'autoLayout' => false,
            ],
        ])
        ->assertNoContent();

    $user->refresh();

    expect($user->dashboard_layout)->toBeArray()
        ->and($user->dashboard_layout['order'])->toBe(['expenses-real', 'income-real'])
        ->and($user->dashboard_layout['sizes'])->toBe(['income-real' => 2])
        ->and($user->dashboard_layout['hidden'])->toBe(['san-juan-profit'])
        ->and($user->dashboard_layout['autoLayout'])->toBeFalse();
});

it('clears the dashboard layout when sent null (factory reset)', function () {
    $user = User::factory()->create(['dashboard_layout' => ['order' => ['income-real']]]);

    $this->actingAs($user)
        ->postJson(route('finance.dashboard.layout'), ['layout' => null])
        ->assertNoContent();

    expect($user->refresh()->dashboard_layout)->toBeNull();
});

it('rejects invalid widget sizes in the layout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('finance.dashboard.layout'), [
            'layout' => ['sizes' => ['income-real' => 9]],
        ])
        ->assertStatus(422);
});

it('requires authentication to save a layout', function () {
    $this->postJson(route('finance.dashboard.layout'), ['layout' => null])
        ->assertUnauthorized();
});

it('renders the saved layout into the dashboard grid', function () {
    $user = User::factory()->create([
        'dashboard_layout' => ['order' => ['expenses-real', 'income-real'], 'autoLayout' => false],
    ]);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('data-save-url', false)
        ->assertSee('expenses-real', false);
});

it('shows the new summary widgets on the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('Pendientes por resolver')
        ->assertSee('Tasa de ahorro del mes')
        ->assertSee('data-dashboard-widget="month-comparison"', false)
        ->assertSee('data-dashboard-widget="pending-summary"', false);
});

it('shows the credit-available widget only when a card has a credit limit', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertDontSee('data-dashboard-widget="credit-available"', false);

    Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail()
        ->update(['credit_limit' => 10000]);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-widget="credit-available"', false)
        ->assertSee('Crédito disponible');
});

it('computes the aggregated credit line the same way as the credits page', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update(['credit_limit' => 10000]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Compra NU',
        'total_amount' => 3000,
        'months' => 1,
        'first_due_month' => '2026-06-01',
        'account_id' => $nu->id,
        'status' => 'active',
    ]);
    $credit->installments()->create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'installment_number' => 1,
        'amount' => 3000,
        'paid_amount' => 1000,
        'status' => 'pending',
    ]);

    $summary = app(FinanceSummaryService::class)->creditLineSummary($user);

    // Usado = saldo pendiente (3000 - 1000 = 2000); disponible = 10000 - 2000.
    expect($summary['has_limits'])->toBeTrue()
        ->and($summary['limit'])->toBe(10000.0)
        ->and($summary['used'])->toBe(2000.0)
        ->and($summary['available'])->toBe(8000.0);
});
