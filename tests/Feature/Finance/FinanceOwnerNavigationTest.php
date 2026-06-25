<?php

use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
});

function navUser(string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('shows owner-only nav links to the finance owner', function () {
    $owner = navUser('owner@example.com');
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($owner)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee(route('finance.security.index'), false)
        ->assertSee(route('finance.health.index'), false);
});

it('hides owner-only nav links from a normal user', function () {
    $user = navUser('user@example.com');
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertDontSee(route('finance.security.index'), false)
        ->assertDontSee(route('finance.health.index'), false);
});

it('shows the movement export buttons to the owner', function () {
    $owner = navUser('owner@example.com');
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($owner)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee(route('finance.movements.export'), false);
});

it('hides the movement export buttons from a normal user', function () {
    $user = navUser('user@example.com');
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertDontSee(route('finance.movements.export'), false);
});

it('hides the report export buttons from a normal user', function () {
    $user = navUser('user@example.com');
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.reports.index'))
        ->assertOk()
        ->assertDontSee(route('finance.reports.export'), false);
});

it('forbids a normal user from forcing sensitive export urls', function () {
    $user = navUser('user@example.com');
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.movements.export'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('finance.reports.export'))
        ->assertForbidden();
});
