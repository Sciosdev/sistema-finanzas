<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditOption;
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

function creditOptionPageAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'NU',
        'type' => 'card',
        'opening_balance' => 1000,
        'payment_day' => 15,
        'is_active' => true,
    ], $attributes));
}

function creditOptionPageOption(User $user, array $attributes = []): CreditOption
{
    return CreditOption::create(array_merge([
        'user_id' => $user->id,
        'name' => 'NU',
        'provider' => 'NU',
        'available_amount' => 5000,
        'min_amount' => 0,
        'cost_type' => 'total_percent',
        'cost_percent' => 3,
        'fixed_fee' => 0,
        'term_months' => 1,
        'payment_day' => 15,
        'is_active' => true,
    ], $attributes));
}

it('can create a credit option', function () {
    $user = User::factory()->create();
    $account = creditOptionPageAccount($user);

    $this->actingAs($user)
        ->from('/finanzas/planificador')
        ->post('/finanzas/planificador/creditos/opciones', [
            'name' => 'NU efectivo',
            'provider' => 'NU',
            'account_id' => $account->id,
            'available_amount' => 5000,
            'min_amount' => 100,
            'cost_type' => 'total_percent',
            'cost_percent' => 3,
            'term_months' => 1,
            'payment_day' => 15,
            'notes' => 'Por cada 100 pago 103',
        ])
        ->assertRedirect('/finanzas/planificador');

    $option = CreditOption::where('user_id', $user->id)->firstOrFail();

    expect($option->name)->toBe('NU efectivo')
        ->and($option->account_id)->toBe($account->id)
        ->and((float) $option->available_amount)->toBe(5000.0)
        ->and((float) $option->cost_percent)->toBe(3.0)
        ->and($option->is_active)->toBeTrue();
});

it('does not allow using an account from another user', function () {
    $user = User::factory()->create();
    creditOptionPageAccount($user);
    $other = User::factory()->create();
    $otherAccount = creditOptionPageAccount($other);

    $this->actingAs($user)
        ->from('/finanzas/planificador')
        ->post('/finanzas/planificador/creditos/opciones', [
            'name' => 'Cuenta ajena',
            'account_id' => $otherAccount->id,
            'available_amount' => 1000,
            'cost_type' => 'total_percent',
            'cost_percent' => 3,
            'term_months' => 1,
        ])
        ->assertRedirect('/finanzas/planificador')
        ->assertSessionHasErrors('account_id');

    expect(CreditOption::count())->toBe(0);
});

it('shows the credit and cash comparator section on the planner page', function () {
    $user = User::factory()->create();
    creditOptionPageAccount($user);

    $this->actingAs($user)
        ->get('/finanzas/planificador')
        ->assertOk()
        ->assertSee('Comparador de cr', false)
        ->assertSee('Simular opciones')
        ->assertSee('Registrar opci', false);
});

it('planner page allows registering an option from the form route', function () {
    $user = User::factory()->create();
    creditOptionPageAccount($user);

    $this->actingAs($user)
        ->get('/finanzas/planificador')
        ->assertOk()
        ->assertSee('finanzas/planificador/creditos/opciones', false);

    $this->actingAs($user)
        ->post(route('finance.credit-options.store'), [
            'name' => 'DIDI',
            'provider' => 'DIDI',
            'available_amount' => 3000,
            'min_amount' => 100,
            'cost_type' => 'percent_plus_fee',
            'cost_percent' => 15,
            'fixed_fee' => 50,
            'term_months' => 3,
        ])
        ->assertRedirect();

    expect(CreditOption::where('user_id', $user->id)->where('name', 'DIDI')->exists())->toBeTrue();
});

it('simulation route shows results on the planner page', function () {
    $user = User::factory()->create();
    creditOptionPageAccount($user);
    creditOptionPageOption($user, ['name' => 'NU', 'cost_percent' => 3]);

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('finance.credit-simulation.simulate', [
            'amount' => 1000,
            'horizon_days' => 30,
            'strategy' => 'balanced',
        ]))
        ->assertOk()
        ->assertSee('recomendada')
        ->assertSee('NU')
        ->assertSee('$1,030.00');
});

it('does not allow a user to update or delete another users credit options', function () {
    $owner = User::factory()->create();
    creditOptionPageAccount($owner);
    $option = creditOptionPageOption($owner, ['name' => 'Owner option']);

    $other = User::factory()->create();
    creditOptionPageAccount($other);

    $this->actingAs($other)
        ->patch(route('finance.credit-options.update', $option), [
            'name' => 'Robada',
            'available_amount' => 1,
            'cost_type' => 'total_percent',
            'cost_percent' => 1,
            'term_months' => 1,
        ])
        ->assertForbidden();

    $this->actingAs($other)
        ->delete(route('finance.credit-options.destroy', $option))
        ->assertForbidden();

    expect($option->fresh())->not->toBeNull()
        ->and($option->fresh()->name)->toBe('Owner option');
});
