<?php

use App\Models\Finance\Category;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function colorUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('applies representative colors by category name', function () {
    $user = colorUser();

    $this->actingAs($user)
        ->post(route('finance.categories.apply-colors'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $comida = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $saldo = Category::where('user_id', $user->id)->where('name', 'Saldo / Telefonia')->firstOrFail();
    $credito = Category::where('user_id', $user->id)->where('name', 'Crédito / tarjeta')->firstOrFail();

    expect($comida->color)->toBe('#f97316')
        ->and($saldo->color)->toBe('#06b6d4')
        ->and($credito->color)->toBe('#7c3aed');
});

it('falls back to a group color when the name is unknown', function () {
    $user = colorUser();

    $custom = Category::create([
        'user_id' => $user->id,
        'name' => 'Pizza de los viernes',
        'type' => 'expense',
        'group' => 'Comida',
        'color' => '#000000',
        'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('finance.categories.apply-colors'));

    expect($custom->fresh()->color)->toBe('#f97316');
});

it('assigns a distinct fallback color to categories with unknown name and group', function () {
    $user = colorUser();

    $a = Category::create([
        'user_id' => $user->id, 'name' => 'Categoria rara uno', 'type' => 'expense',
        'group' => 'Grupo Inexistente A', 'color' => '#000000', 'is_active' => true,
    ]);
    $b = Category::create([
        'user_id' => $user->id, 'name' => 'Categoria rara dos', 'type' => 'expense',
        'group' => 'Grupo Inexistente B', 'color' => '#000000', 'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('finance.categories.apply-colors'));

    expect($a->fresh()->color)->not->toBe('#000000')
        ->and($b->fresh()->color)->not->toBe('#000000')
        ->and($a->fresh()->color)->not->toBe($b->fresh()->color);
});

it('does not recolor another users categories', function () {
    $user = colorUser();
    $other = colorUser();

    $otherComida = Category::where('user_id', $other->id)->where('name', 'Comida')->firstOrFail();
    $otherComida->update(['color' => '#123123']);

    $this->actingAs($user)->post(route('finance.categories.apply-colors'));

    expect($otherComida->fresh()->color)->toBe('#123123');
});
