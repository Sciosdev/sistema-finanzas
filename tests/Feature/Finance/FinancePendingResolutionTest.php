<?php

use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinancePendingResolutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-25 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function pendingUser(): User
{
    $user = User::factory()->create();

    app(\App\Services\Finance\FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function pendingGroup(User $user, string $key): array
{
    $result = app(FinancePendingResolutionService::class)->run($user);

    foreach ($result['groups'] as $group) {
        if ($group['key'] === $key) {
            return $group;
        }
    }

    return ['count' => 0, 'items' => []];
}

it('lets an authenticated user open the pending screen', function () {
    $user = pendingUser();

    $this->actingAs($user)
        ->get(route('finance.pending.index'))
        ->assertOk()
        ->assertSee('Pendientes por resolver');
});

it('redirects guests away from the pending screen', function () {
    $this->get(route('finance.pending.index'))
        ->assertRedirect(route('login'));
});

it('detects a movement without category', function () {
    $user = pendingUser();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 123.45,
        'description' => 'Gasto sin clasificar',
    ]);

    $group = pendingGroup($user, 'movements_without_category');

    expect($group['count'])->toBe(1);

    $this->actingAs($user)
        ->get(route('finance.pending.index'))
        ->assertOk()
        ->assertSee('Gasto sin clasificar')
        ->assertSee(route('finance.movements.edit', $movement->id), false);
});

it('detects an overdue pending planned payment', function () {
    $user = pendingUser();

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'name' => 'Renta vencida',
        'amount' => 500,
        'status' => 'pending',
    ]);

    $group = pendingGroup($user, 'planned_overdue');

    expect($group['count'])->toBe(1)
        ->and($group['items'][0]['descripcion'])->toBe('Renta vencida');

    $this->actingAs($user)
        ->get(route('finance.pending.index'))
        ->assertOk()
        ->assertSee('Renta vencida');
});

it('detects an overdue expected income', function () {
    $user = pendingUser();

    ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'name' => 'Pago FESI atrasado',
        'amount' => 8000,
        'status' => 'pending',
        'is_rent' => false,
    ]);

    expect(pendingGroup($user, 'expected_incomes_overdue')['count'])->toBe(1);

    $this->actingAs($user)
        ->get(route('finance.pending.index'))
        ->assertOk()
        ->assertSee('Pago FESI atrasado');
});

it('detects a partial expected income with a remaining balance', function () {
    $user = pendingUser();

    ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-30',
        'name' => 'Pago parcial Andrea',
        'amount' => 1000,
        'received_amount' => 400,
        'status' => 'partial',
        'is_rent' => false,
    ]);

    $group = pendingGroup($user, 'expected_incomes_partial');

    expect($group['count'])->toBe(1)
        ->and($group['items'][0]['monto'])->toBe(600.0);
});

it('detects an overdue credit installment', function () {
    $user = pendingUser();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-05-01',
        'name' => 'Laptop',
        'total_amount' => 1200,
        'months' => 3,
        'first_due_month' => '2026-05-01',
        'due_day' => 10,
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'installment_number' => 2,
        'amount' => 400,
        'status' => 'pending',
    ]);

    $group = pendingGroup($user, 'credit_installments_overdue');

    expect($group['count'])->toBe(1)
        ->and($group['items'][0]['descripcion'])->toContain('Laptop');
});

it('detects a daily cut with a conciliation difference', function () {
    $user = pendingUser();

    DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-06-22',
        'cards_amount' => 1000,
        'real_total' => 1000,
        'difference' => -25.50,
        'status' => 'review',
    ]);

    $group = pendingGroup($user, 'cuts_with_difference');

    expect($group['count'])->toBe(1)
        ->and($group['items'][0]['monto'])->toBe(-25.50);
});

it('does not show pending items from another user', function () {
    $owner = pendingUser();
    $other = pendingUser();

    Movement::create([
        'user_id' => $other->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 99,
        'description' => 'MOVIMIENTO DE OTRO USUARIO',
    ]);

    expect(pendingGroup($owner, 'movements_without_category')['count'])->toBe(0);

    $this->actingAs($owner)
        ->get(route('finance.pending.index'))
        ->assertOk()
        ->assertDontSee('MOVIMIENTO DE OTRO USUARIO');
});

it('builds links that point to existing finance routes', function () {
    $user = pendingUser();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 10,
        'description' => 'Sin categoria',
    ]);

    DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-06-22',
        'cards_amount' => 1000,
        'real_total' => 1000,
        'difference' => 5,
        'status' => 'review',
    ]);

    $urls = collect(app(FinancePendingResolutionService::class)->run($user)['groups'])
        ->flatMap(fn (array $group) => collect($group['items'])->pluck('url'))
        ->all();

    expect($urls)
        ->toContain(route('finance.movements.edit', $movement->id))
        ->toContain(route('finance.cuts.index'));
});

it('shows the pending link in the finance menu for a normal user', function () {
    $user = pendingUser();

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee(route('finance.pending.index'), false);
});
