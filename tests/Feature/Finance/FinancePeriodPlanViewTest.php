<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-03 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function periodViewFixture(User $user): void
{
    Account::create([
        'user_id' => $user->id, 'name' => 'Efectivo', 'type' => 'cash',
        'opening_balance' => 12000, 'is_active' => true,
    ]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 1000]);

    ExpectedIncome::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-15',
        'name' => 'Quincena', 'amount' => 5000, 'received_amount' => 0, 'status' => 'pending',
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id, 'purchase_date' => '2026-06-01', 'name' => 'NU',
        'total_amount' => 2000, 'months' => 1, 'first_due_month' => '2026-07-01', 'status' => 'active',
    ]);
    CreditInstallment::create([
        'credit_purchase_id' => $credit->id, 'user_id' => $user->id,
        'period_month' => '2026-07-01', 'due_date' => '2026-07-20',
        'installment_number' => 1, 'amount' => 2000, 'paid_amount' => 0, 'status' => 'pending',
    ]);

    $comida = Category::create([
        'user_id' => $user->id, 'name' => 'Comida', 'type' => 'expense', 'group' => 'Flexible', 'is_active' => true,
    ]);
    Movement::create([
        'user_id' => $user->id, 'happened_on' => '2026-06-10', 'movement_type' => 'expense',
        'amount' => 500, 'description' => 'Super', 'category_id' => $comida->id, 'source' => 'manual',
    ]);

    PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-07-01', 'due_date' => '2026-07-10',
        'name' => 'Agua', 'amount' => 300, 'paid_amount' => 0, 'status' => 'pending',
    ]);
}

it('shows the period plan section with its three engines on the projection page', function () {
    $user = User::factory()->create();
    periodViewFixture($user);

    $this->actingAs($user)
        ->get(route('finance.projection.index'))
        ->assertOk()
        ->assertSee('Plan por periodos', false)
        ->assertSee('Pista de efectivo por tramos', false)
        ->assertSee('Cronograma de crédito', false)
        ->assertSee('Sobres semanales para vivir', false)
        ->assertSee('NU', false)
        ->assertSee('Comida', false);
});

it('shows the period plan runway numbers and disclaimer on the projection page', function () {
    $user = User::factory()->create();
    periodViewFixture($user);

    $this->actingAs($user)
        ->get(route('finance.projection.index'))
        ->assertOk()
        // Saldo inicial y tramo por quincena visibles.
        ->assertSee('Saldo inicial: $12,000.00', false)
        ->assertSee('1ª quincena de julio 2026', false)
        ->assertSee('Esto es solo una recomendación. No se creó ningún movimiento ni se cambió ningún estado.', false);
});
