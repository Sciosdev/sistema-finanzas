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

it('keeps the current color when neither name nor group is known', function () {
    $user = colorUser();

    $custom = Category::create([
        'user_id' => $user->id,
        'name' => 'Categoria rara xyz',
        'type' => 'expense',
        'group' => 'Grupo Inexistente',
        'color' => '#abcdef',
        'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('finance.categories.apply-colors'));

    expect($custom->fresh()->color)->toBe('#abcdef');
});

it('does not recolor another users categories', function () {
    $user = colorUser();
    $other = colorUser();

    $otherComida = Category::where('user_id', $other->id)->where('name', 'Comida')->firstOrFail();
    $otherComida->update(['color' => '#123123']);

    $this->actingAs($user)->post(route('finance.categories.apply-colors'));

    expect($otherComida->fresh()->color)->toBe('#123123');
});
