<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\SpendingLimit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-15 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function spendingLimitPageAccount(User $user): Account
{
    return Account::create([
        'user_id' => $user->id,
        'name' => 'Efectivo',
        'type' => 'cash',
        'opening_balance' => 1000,
        'is_active' => true,
    ]);
}

function spendingLimitPageCategory(User $user, array $attributes = []): Category
{
    return Category::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Tienda '.uniqid(),
        'type' => 'expense',
        'group' => 'Diario',
        'is_active' => true,
    ], $attributes));
}

it('can create a spending limit by category', function () {
    $user = User::factory()->create();
    spendingLimitPageAccount($user);
    $category = spendingLimitPageCategory($user);

    $this->actingAs($user)
        ->from('/finanzas/planificador')
        ->post('/finanzas/planificador/limites', [
            'category_id' => $category->id,
            'period_type' => 'weekly',
            'limit_amount' => '700.50',
            'warning_threshold_percent' => '85',
            'notes' => 'Tope semanal',
        ])
        ->assertRedirect('/finanzas/planificador');

    $limit = SpendingLimit::where('user_id', $user->id)->firstOrFail();

    expect($limit->category_id)->toBe($category->id)
        ->and($limit->period_type)->toBe('weekly')
        ->and((float) $limit->limit_amount)->toBe(700.5)
        ->and((float) $limit->warning_threshold_percent)->toBe(85.0)
        ->and($limit->is_active)->toBeTrue();
});

it('shows the spending limits section on the planner page', function () {
    $user = User::factory()->create();
    spendingLimitPageAccount($user);
    spendingLimitPageCategory($user);

    $this->actingAs($user)
        ->get('/finanzas/planificador')
        ->assertOk()
        ->assertSee('Límites de gasto')
        ->assertSee('Configurados')
        ->assertSee('Monto límite')
        ->assertSee('Crear');
});

it('planner page allows creating a limit from the form route', function () {
    $user = User::factory()->create();
    spendingLimitPageAccount($user);
    $category = spendingLimitPageCategory($user, ['name' => 'Gasolina']);

    $this->actingAs($user)
        ->get('/finanzas/planificador')
        ->assertOk()
        ->assertSee('finanzas/planificador/limites', false)
        ->assertSee('Gasolina');

    $this->actingAs($user)
        ->post(route('finance.spending-limits.store'), [
            'category_id' => $category->id,
            'period_type' => 'daily',
            'limit_amount' => 120,
        ])
        ->assertRedirect();

    expect(SpendingLimit::where('user_id', $user->id)->where('category_id', $category->id)->exists())->toBeTrue();
});

it('does not allow a user to update or delete another users limits', function () {
    $owner = User::factory()->create();
    spendingLimitPageAccount($owner);
    $ownerCategory = spendingLimitPageCategory($owner);
    $limit = SpendingLimit::create([
        'user_id' => $owner->id,
        'category_id' => $ownerCategory->id,
        'period_type' => 'weekly',
        'limit_amount' => 500,
        'warning_threshold_percent' => 80,
        'is_active' => true,
    ]);

    $other = User::factory()->create();
    spendingLimitPageAccount($other);
    $otherCategory = spendingLimitPageCategory($other);

    $this->actingAs($other)
        ->put(route('finance.spending-limits.update', $limit), [
            'category_id' => $otherCategory->id,
            'period_type' => 'monthly',
            'limit_amount' => 1,
            'warning_threshold_percent' => 90,
            'is_active' => false,
        ])
        ->assertForbidden();

    $this->actingAs($other)
        ->delete(route('finance.spending-limits.destroy', $limit))
        ->assertForbidden();

    expect($limit->fresh())->not->toBeNull()
        ->and((float) $limit->fresh()->limit_amount)->toBe(500.0)
        ->and($limit->fresh()->is_active)->toBeTrue();
});
