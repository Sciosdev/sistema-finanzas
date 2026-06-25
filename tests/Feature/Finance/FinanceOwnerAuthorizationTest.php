<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
});

it('allows the configured finance owner to access protected finance security routes', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk();
});

it('rejects an authenticated non owner from protected finance routes', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.security.index'))
        ->assertForbidden();
});

it('redirects guests to login before checking finance ownership', function () {
    config()->set('finance.owner_email', 'owner@example.com');

    $this->get(route('finance.security.index'))
        ->assertRedirect(route('login'));
});

it('exposes a reusable finance owner gate backed by the user helper', function () {
    $owner = User::factory()->create(['email' => 'OWNER@example.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    expect($owner->isFinanceOwner())->toBeTrue()
        ->and($other->isFinanceOwner())->toBeFalse()
        ->and(Gate::forUser($owner)->allows('finance.owner'))->toBeTrue()
        ->and(Gate::forUser($other)->allows('finance.owner'))->toBeFalse();
});
