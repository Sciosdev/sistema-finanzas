<?php

use App\Models\Finance\Account;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function accountColorUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('applies representative colors by account name', function () {
    $user = accountColorUser();

    $this->actingAs($user)
        ->post(route('finance.accounts.apply-colors'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $efectivo = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    expect($nu->color)->toBe('#7c3aed')
        ->and($efectivo->color)->toBe('#16a34a');
});

it('assigns different colors to accounts with unknown names', function () {
    $user = accountColorUser();

    $a = Account::create(['user_id' => $user->id, 'name' => 'Cuenta Rara Uno', 'type' => 'bank', 'color' => '#000000', 'display_order' => 900, 'is_active' => true]);
    $b = Account::create(['user_id' => $user->id, 'name' => 'Cuenta Rara Dos', 'type' => 'bank', 'color' => '#000000', 'display_order' => 901, 'is_active' => true]);

    $this->actingAs($user)->post(route('finance.accounts.apply-colors'));

    expect($a->fresh()->color)->not->toBe('#000000')
        ->and($b->fresh()->color)->not->toBe('#000000')
        ->and($a->fresh()->color)->not->toBe($b->fresh()->color);
});

it('does not recolor another users accounts', function () {
    $user = accountColorUser();
    $other = accountColorUser();

    $otherNu = Account::where('user_id', $other->id)->where('name', 'NU')->firstOrFail();
    $otherNu->update(['color' => '#111111']);

    $this->actingAs($user)->post(route('finance.accounts.apply-colors'));

    expect($otherNu->fresh()->color)->toBe('#111111');
});
