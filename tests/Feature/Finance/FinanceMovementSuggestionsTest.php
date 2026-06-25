<?php

use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\MovementClassificationSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function sugUser(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function sugMovement(User $user, string $description, array $overrides = []): Movement
{
    return Movement::create(array_merge([
        'user_id' => $user->id,
        'happened_on' => '2026-06-15',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => $description,
        'source' => 'manual',
    ], $overrides));
}

function suggestFor(User $user, Movement $movement): array
{
    return app(MovementClassificationSuggestionService::class)->suggest($user, collect([$movement]))[$movement->id];
}

it('suggests a category by direct category name match', function () {
    $user = sugUser();
    Category::create(['user_id' => $user->id, 'name' => 'Veterinario', 'type' => 'expense', 'group' => 'Salud', 'is_active' => true]);

    $movement = sugMovement($user, 'Pago veterinario centro');
    $sug = suggestFor($user, $movement);

    expect($sug['category'])->not->toBeNull()
        ->and($sug['category']['name'])->toBe('Veterinario')
        ->and($sug['category']['confidence'])->toBe('alta')
        ->and($sug['category']['reason'])->toContain('Veterinario');
});

it('suggests a category by category group', function () {
    $user = sugUser();
    $cat = Category::create(['user_id' => $user->id, 'name' => 'Croquetas', 'type' => 'expense', 'group' => 'Mascotas', 'keywords' => '', 'is_active' => true]);

    $movement = sugMovement($user, 'Compra mascotas del mes');
    $sug = suggestFor($user, $movement);

    expect($sug['category'])->not->toBeNull()
        ->and($sug['category']['id'])->toBe($cat->id)
        ->and($sug['category']['confidence'])->toBe('media')
        ->and($sug['category']['reason'])->toContain('grupo');
});

it('suggests a person by name', function () {
    $user = sugUser();
    $movement = sugMovement($user, 'Deposito andrea apoyo');
    $sug = suggestFor($user, $movement);

    expect($sug['person'])->not->toBeNull()
        ->and($sug['person']['name'])->toBe('Andrea')
        ->and($sug['person']['confidence'])->toBe('alta');
});

it('suggests a person by alias', function () {
    $user = sugUser();
    Person::create(['user_id' => $user->id, 'name' => 'Roberto', 'alias' => 'Beto', 'type' => 'family', 'is_active' => true]);

    $movement = sugMovement($user, 'Pago a beto prestamo');
    $sug = suggestFor($user, $movement);

    expect($sug['person'])->not->toBeNull()
        ->and($sug['person']['name'])->toBe('Roberto')
        ->and($sug['person']['reason'])->toContain('alias');
});

it('suggests Comida for related food words when a Comida category exists', function () {
    $user = sugUser();
    $comida = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    foreach (['Hamburguesa doble', 'Espagueti boloñesa', 'Pizza familiar'] as $description) {
        $movement = sugMovement($user, $description);
        $sug = suggestFor($user, $movement);

        expect($sug['category'])->not->toBeNull()
            ->and($sug['category']['id'])->toBe($comida->id)
            ->and($sug['category']['reason'])->toContain('relacionada');
    }
});

it('suggests the telephone category for saldo/telcel/recarga', function () {
    $user = sugUser();
    $telefonia = Category::where('user_id', $user->id)->where('name', 'Saldo / Telefonia')->firstOrFail();

    $movement = sugMovement($user, 'Telcel recarga semanal');
    $sug = suggestFor($user, $movement);

    expect($sug['category'])->not->toBeNull()
        ->and($sug['category']['id'])->toBe($telefonia->id)
        ->and($sug['category']['confidence'])->toBe('alta');
});

it('suggests a category learned from user history', function () {
    $user = sugUser();
    $tienda = Category::create(['user_id' => $user->id, 'name' => 'Conveniencia', 'type' => 'expense', 'group' => 'Compras', 'keywords' => '', 'is_active' => true]);

    foreach (['zzmart compra', 'zzmart nocturno', 'zzmart cafe'] as $description) {
        sugMovement($user, $description, ['category_id' => $tienda->id]);
    }

    $movement = sugMovement($user, 'zzmart esquina', ['category_id' => null]);
    $sug = suggestFor($user, $movement);

    expect($sug['category'])->not->toBeNull()
        ->and($sug['category']['id'])->toBe($tienda->id)
        ->and($sug['category']['reason'])->toContain('anteriores');
});

it('does not suggest catalogs that belong to another user', function () {
    $user = sugUser();
    $other = sugUser();
    Category::create(['user_id' => $other->id, 'name' => 'Zoologico', 'type' => 'expense', 'group' => 'Otro', 'is_active' => true]);

    $movement = sugMovement($user, 'Zoologico visita guiada');
    $sug = suggestFor($user, $movement);

    expect($sug['category'])->toBeNull();
});

it('does not modify movements when opening the suggestions screen', function () {
    $user = sugUser();
    $movement = sugMovement($user, 'Telcel recarga semanal', ['category_id' => null]);

    $this->actingAs($user)
        ->get(route('finance.movements.suggestions.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Sugerencias de clasificación')
        ->assertSee('name="ids[]"', false)
        ->assertSee('Aplicar sugerencias seleccionadas');

    expect($movement->fresh()->category_id)->toBeNull();
});

it('applies suggestions only to selected movements of the user', function () {
    $user = sugUser();
    $other = sugUser();
    $telefonia = Category::where('user_id', $user->id)->where('name', 'Saldo / Telefonia')->firstOrFail();

    $mine = sugMovement($user, 'Telcel recarga semanal', ['category_id' => null]);
    $theirs = sugMovement($other, 'Telcel recarga semanal', ['category_id' => null]);

    $this->actingAs($user)
        ->post(route('finance.movements.suggestions.apply'), [
            'ids' => [$mine->id, $theirs->id],
            'apply_category' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($mine->fresh()->category_id)->toBe($telefonia->id)
        ->and($theirs->fresh()->category_id)->toBeNull();
});

it('does not change amount date or description when applying suggestions', function () {
    $user = sugUser();
    $movement = sugMovement($user, 'Telcel recarga semanal', [
        'category_id' => null,
        'amount' => 250,
        'happened_on' => '2026-06-15',
    ]);

    $this->actingAs($user)
        ->post(route('finance.movements.suggestions.apply'), [
            'ids' => [$movement->id],
            'apply_category' => '1',
        ])
        ->assertRedirect();

    $movement->refresh();

    expect((float) $movement->amount)->toBe(250.0)
        ->and($movement->happened_on->toDateString())->toBe('2026-06-15')
        ->and($movement->description)->toBe('Telcel recarga semanal')
        ->and($movement->category_id)->not->toBeNull();
});

it('warns and does nothing when no movements are selected to apply', function () {
    $user = sugUser();

    $this->actingAs($user)
        ->post(route('finance.movements.suggestions.apply'), [
            'ids' => [],
            'apply_category' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});
