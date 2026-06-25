<?php

use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function editReturnUser(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function makeEditMovement(User $user): Movement
{
    return Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Movimiento editable',
        'source' => 'manual',
    ]);
}

function editUpdatePayload(array $overrides = []): array
{
    return array_merge([
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 150,
        'description' => 'Movimiento corregido',
    ], $overrides);
}

it('returns to the same page after saving an individual edit', function () {
    $user = editReturnUser();
    $movement = makeEditMovement($user);

    $returnTo = '/finanzas/movimientos?page=2&per_page=200&q=uber';

    $this->actingAs($user)
        ->put(route('finance.movements.update', $movement), editUpdatePayload([
            'return_to' => $returnTo,
        ]))
        ->assertRedirect($returnTo);

    expect($movement->fresh()->description)->toBe('Movimiento corregido');
});

it('preserves per_page search and filters in the return_to', function () {
    $user = editReturnUser();
    $movement = makeEditMovement($user);
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $returnTo = '/finanzas/movimientos?month=2026-06&type=expense&q=uber&per_page=200&page=3';

    $this->actingAs($user)
        ->put(route('finance.movements.update', $movement), editUpdatePayload([
            'category_id' => $category->id,
            'return_to' => $returnTo,
        ]))
        ->assertRedirect($returnTo);
});

it('renders the return_to in the edit form and the back button', function () {
    $user = editReturnUser();
    $movement = makeEditMovement($user);

    $returnTo = '/finanzas/movimientos?page=2&per_page=200&q=uber';

    $this->actingAs($user)
        ->get(route('finance.movements.edit', ['movement' => $movement, 'return_to' => $returnTo]))
        ->assertOk()
        ->assertSee('name="return_to"', false)
        // El valor se renderiza escapado (& -> &amp;); validamos un tramo estable.
        ->assertSee('/finanzas/movimientos?page=2', false);
});

it('ignores an external return_to and falls back to the movements index', function () {
    $user = editReturnUser();
    $movement = makeEditMovement($user);

    $this->actingAs($user)
        ->put(route('finance.movements.update', $movement), editUpdatePayload([
            'return_to' => 'https://otro-sitio.com/phishing',
        ]))
        ->assertRedirect(route('finance.movements.index'));
});

it('keeps the normal month-based redirect when no return_to is sent', function () {
    $user = editReturnUser();
    $movement = makeEditMovement($user);

    $this->actingAs($user)
        ->put(route('finance.movements.update', $movement), editUpdatePayload([
            'happened_on' => '2026-06-20',
        ]))
        ->assertRedirect(route('finance.movements.index', ['month' => '2026-06']));
});

it('does not let a user edit another users movement even with return_to', function () {
    $owner = editReturnUser();
    $other = editReturnUser();
    $movement = makeEditMovement($other);

    $this->actingAs($owner)
        ->put(route('finance.movements.update', $movement), editUpdatePayload([
            'description' => 'Intento de cambio ajeno',
            'return_to' => '/finanzas/movimientos?page=2',
        ]))
        ->assertForbidden();

    expect($movement->fresh()->description)->toBe('Movimiento editable');
});
