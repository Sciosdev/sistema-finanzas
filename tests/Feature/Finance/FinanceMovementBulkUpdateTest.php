<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bulkUser(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function makeMovement(User $user, array $overrides = []): Movement
{
    return Movement::create(array_merge([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Movimiento de prueba',
        'source' => 'manual',
    ], $overrides));
}

it('lets a user bulk-change category on their own movements', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $a = makeMovement($user);
    $b = makeMovement($user);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$a->id, $b->id],
            'category_id' => $category->id,
        ])
        ->assertRedirect();

    expect($a->fresh()->category_id)->toBe($category->id)
        ->and($b->fresh()->category_id)->toBe($category->id);
});

it('ignores ids that belong to another user', function () {
    $user = bulkUser();
    $other = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $mine = makeMovement($user);
    $theirs = makeMovement($other, ['category_id' => null]);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$mine->id, $theirs->id],
            'category_id' => $category->id,
        ])
        ->assertRedirect();

    expect($mine->fresh()->category_id)->toBe($category->id)
        ->and($theirs->fresh()->category_id)->toBeNull();
});

it('does nothing and warns when no movements are selected', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [],
            'category_id' => $category->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('does not overwrite fields left as "no cambiar"', function () {
    $user = bulkUser();
    $original = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $account = Account::where('user_id', $user->id)->firstOrFail();

    $movement = makeMovement($user, [
        'category_id' => $original->id,
        'account_id' => $account->id,
        'person_id' => null,
    ]);

    // Solo cambia la cuenta; categoría y persona deben quedar igual (vienen vacías).
    $newAccount = Account::where('user_id', $user->id)->where('id', '!=', $account->id)->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'category_id' => '',
            'person_id' => '',
            'account_id' => $newAccount->id,
        ])
        ->assertRedirect();

    $movement->refresh();

    expect($movement->account_id)->toBe($newAccount->id)
        ->and($movement->category_id)->toBe($original->id)
        ->and($movement->person_id)->toBeNull();
});

it('returns to a safe internal return_to preserving query params', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $movement = makeMovement($user);

    // La vista envía request()->fullUrl(); aquí usamos la forma relativa equivalente,
    // independiente del host del entorno de pruebas.
    $returnTo = '/finanzas/movimientos?page=2&per_page=100&q=uber';

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'category_id' => $category->id,
            'return_to' => $returnTo,
        ])
        ->assertRedirect('/finanzas/movimientos?page=2&per_page=100&q=uber');
});

it('preserves an absolute same-origin return_to like the view sends', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $movement = makeMovement($user);

    $returnTo = url('/finanzas/movimientos?page=4&per_page=50');

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'category_id' => $category->id,
            'return_to' => $returnTo,
        ])
        ->assertRedirect('/finanzas/movimientos?page=4&per_page=50');
});

it('rejects an external return_to and falls back to the movements index', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $movement = makeMovement($user);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'category_id' => $category->id,
            'return_to' => 'https://evil.example.com/phishing',
        ])
        ->assertRedirect(route('finance.movements.index'));
});

it('can bulk-change person and account', function () {
    $user = bulkUser();
    $person = Person::where('user_id', $user->id)->firstOrFail();
    $account = Account::where('user_id', $user->id)->firstOrFail();
    $movement = makeMovement($user, ['person_id' => null, 'account_id' => null]);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'person_id' => $person->id,
            'account_id' => $account->id,
        ])
        ->assertRedirect();

    $movement->refresh();

    expect($movement->person_id)->toBe($person->id)
        ->and($movement->account_id)->toBe($account->id);
});

it('does not change amount date or description via bulk update', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $movement = makeMovement($user, ['amount' => 100, 'description' => 'Original', 'happened_on' => '2026-06-20']);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'category_id' => $category->id,
            'amount' => 9999,
            'description' => 'Hackeado',
            'happened_on' => '2030-01-01',
        ])
        ->assertRedirect();

    $movement->refresh();

    expect((float) $movement->amount)->toBe(100.0)
        ->and($movement->description)->toBe('Original')
        ->and($movement->happened_on->toDateString())->toBe('2026-06-20')
        ->and($movement->category_id)->toBe($category->id);
});

it('can mark and unmark the is_unknown flag in bulk', function () {
    $user = bulkUser();
    $movement = makeMovement($user, ['is_unknown' => false]);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'is_unknown' => '1',
        ])
        ->assertRedirect();

    expect($movement->fresh()->is_unknown)->toBeTrue();

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$movement->id],
            'is_unknown' => '0',
        ])
        ->assertRedirect();

    expect($movement->fresh()->is_unknown)->toBeFalse();
});

it('shows the bulk action panel and checkboxes on the movements page', function () {
    $user = bulkUser();
    $movement = makeMovement($user);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => $movement->happened_on->format('Y-m')]))
        ->assertOk()
        ->assertSee('Aplicar cambios masivos')
        ->assertSee('bulk-select-all', false)
        ->assertSee('name="ids[]"', false)
        ->assertSee('No cambiar');
});

it('reports how many movements were updated', function () {
    $user = bulkUser();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $a = makeMovement($user);
    $b = makeMovement($user);
    $c = makeMovement($user);

    $this->actingAs($user)
        ->post(route('finance.movements.bulk-update'), [
            'ids' => [$a->id, $b->id, $c->id],
            'category_id' => $category->id,
        ])
        ->assertSessionHas('success', 'Se actualizaron 3 movimiento(s).');
});
