<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows expense category report, important concepts and concept detail', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $food = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $saldo = Category::where('user_id', $user->id)->where('name', 'Saldo / Telefonia')->firstOrFail();
    $incomeCategory = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();
    $creditCategory = Category::where('user_id', $user->id)->where('keywords', 'like', '%tarjeta%')->firstOrFail();

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-04',
        'movement_type' => 'income',
        'amount' => 1800,
        'description' => 'Pago FESI',
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-08',
        'movement_type' => 'yield',
        'amount' => 25,
        'description' => 'Rendimiento NU',
        'account_id' => $account->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-05',
        'movement_type' => 'expense',
        'amount' => 300,
        'description' => 'Taqueria',
        'account_id' => $account->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-06',
        'movement_type' => 'expense',
        'amount' => 450,
        'description' => 'Uber Eats',
        'account_id' => $account->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-07',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Saldo Telcel',
        'account_id' => $account->id,
        'category_id' => $saldo->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-05-07',
        'movement_type' => 'expense',
        'amount' => 999,
        'description' => 'Gasto de otro mes',
        'account_id' => $account->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-20',
        'name' => 'Internet',
        'amount' => 600,
        'status' => 'pending',
        'category_id' => $saldo->id,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Laptop',
        'total_amount' => 1200,
        'months' => 3,
        'first_due_month' => '2026-06-01',
        'due_day' => 20,
        'account_id' => $account->id,
        'category_id' => $creditCategory->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-20',
        'installment_number' => 1,
        'amount' => 400,
        'status' => 'pending',
    ]);

    ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-30',
        'name' => 'Mensualidad FESI',
        'amount' => 2500,
        'status' => 'pending',
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
    ]);

    $this->actingAs($user)
        ->get(route('finance.reports.index', ['month' => '2026-06', 'year' => 2026]))
        ->assertOk()
        ->assertSee('Distribucion real del mes')
        ->assertSee('Obligaciones del mes')
        ->assertSee('Top ingresos')
        ->assertSee('Top egresos')
        ->assertSee('Cobertura del mes')
        ->assertSee('Ano en perspectiva')
        ->assertSee('finance-report-chart-data')
        ->assertSee('Flujo planeado')
        ->assertSee('Creditos')
        ->assertSee('Recibido/Pagado')
        ->assertSee('No pagado')
        ->assertSee('Egresos por categoría')
        ->assertSee('Categorías con más egresos')
        ->assertSee('Conceptos importantes')
        ->assertSee('Comida')
        ->assertSee('Saldo / Telefonia')
        ->assertSee('$750.00')
        ->assertSee('$800.00');

    $this->actingAs($user)
        ->get(route('finance.reports.index', [
            'month' => '2026-06',
            'year' => 2026,
            'category_id' => $food->id,
        ]))
        ->assertOk()
        ->assertSee('Detalle por concepto: Comida')
        ->assertSee('Taqueria')
        ->assertSee('Uber Eats')
        ->assertDontSee('Saldo Telcel');
});

it('renders report chart containers without data', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $this->actingAs($user)
        ->get(route('finance.reports.index', ['month' => '2026-08', 'year' => 2026]))
        ->assertOk()
        ->assertSee('Distribucion real del mes')
        ->assertSee('Obligaciones del mes')
        ->assertSee('Top ingresos')
        ->assertSee('Top egresos')
        ->assertSee('Cobertura del mes')
        ->assertSee('Ano en perspectiva')
        ->assertSee('finance-report-chart-data');
});

it('shows category suggestions and similar category warnings without changing movement history', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $food = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    Category::create([
        'user_id' => $user->id,
        'name' => 'Comidas',
        'type' => 'expense',
        'group' => 'Comida',
        'color' => '#f97316',
        'is_active' => true,
    ]);

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-05',
        'movement_type' => 'expense',
        'amount' => 300,
        'description' => 'Taqueria',
        'account_id' => $account->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.categories.index'))
        ->assertOk()
        ->assertSee('Categorías generales sugeridas')
        ->assertSee('Ropa')
        ->assertSee('Posibles categorías repetidas')
        ->assertSee('Comida')
        ->assertSee('Comidas')
        ->assertSee('Unificar en Comida')
        ->assertSee('El historial se mueve a la categoría destino');

    $this->actingAs($user)
        ->post(route('finance.categories.store'), [
            'name' => 'Ropa',
            'type' => 'expense',
            'group' => 'Personal',
            'color' => '#ec4899',
            'keywords' => 'ropa,zapato,playera',
        ])
        ->assertRedirect();

    expect(Category::where('user_id', $user->id)->where('name', 'Ropa')->exists())->toBeTrue();
    expect($movement->refresh()->category_id)->toBe($food->id);
});

it('safely merges repeated categories into a target category', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $target = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $source = Category::create([
        'user_id' => $user->id,
        'name' => 'Comidas',
        'type' => 'expense',
        'group' => 'Comida',
        'color' => '#f97316',
        'keywords' => 'comidas',
        'is_active' => true,
    ]);
    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-05',
        'movement_type' => 'expense',
        'amount' => 300,
        'description' => 'Taqueria',
        'account_id' => $account->id,
        'category_id' => $source->id,
        'source' => 'manual',
    ]);

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'name' => 'Comida planeada',
        'amount' => 120,
        'status' => 'pending',
        'category_id' => $source->id,
    ]);

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-12',
        'name' => 'Reembolso comida',
        'amount' => 80,
        'status' => 'pending',
        'category_id' => $source->id,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Compra comida',
        'total_amount' => 600,
        'months' => 3,
        'first_due_month' => '2026-07-01',
        'status' => 'active',
        'category_id' => $source->id,
    ]);

    $this->actingAs($user)
        ->post(route('finance.categories.merge', $target), [
            'source_category_ids' => [$source->id],
            'confirm_merge' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($movement->refresh()->category_id)->toBe($target->id);
    expect($payment->refresh()->category_id)->toBe($target->id);
    expect($income->refresh()->category_id)->toBe($target->id);
    expect($credit->refresh()->category_id)->toBe($target->id);
    expect($source->refresh()->is_active)->toBeFalse();
});
