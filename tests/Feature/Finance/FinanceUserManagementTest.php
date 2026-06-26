<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
});

function ownerActing(): User
{
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    return $owner;
}

it('lets the owner open the user management screen', function () {
    $owner = ownerActing();

    $this->actingAs($owner)
        ->get(route('finance.users.index'))
        ->assertOk()
        ->assertSee('Crear usuario');
});

it('forbids a non owner from the user management screen', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.users.index'))
        ->assertForbidden();
});

it('redirects guests away from user management', function () {
    config()->set('finance.owner_email', 'owner@example.com');

    $this->get(route('finance.users.index'))
        ->assertRedirect(route('login'));
});

it('lets the owner create a new user', function () {
    $owner = ownerActing();

    $this->actingAs($owner)
        ->post(route('finance.users.store'), [
            'name' => 'Persona Nueva',
            'email' => 'nueva@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])
        ->assertRedirect(route('finance.users.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('users', ['email' => 'nueva@example.com', 'name' => 'Persona Nueva']);
});

it('lets the created user actually log in', function () {
    $owner = ownerActing();

    $this->actingAs($owner)->post(route('finance.users.store'), [
        'name' => 'Persona Nueva',
        'email' => 'nueva@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    auth()->logout();

    $this->post('/login', [
        'email' => 'nueva@example.com',
        'password' => 'secret123',
    ])->assertRedirect();

    $this->assertAuthenticated();
});

it('rejects a duplicate email', function () {
    $owner = ownerActing();
    User::factory()->create(['email' => 'dup@example.com']);

    $this->actingAs($owner)
        ->post(route('finance.users.store'), [
            'name' => 'Otro',
            'email' => 'dup@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])
        ->assertSessionHasErrors('email');
});

it('rejects an unconfirmed or short password', function () {
    $owner = ownerActing();

    $this->actingAs($owner)
        ->post(route('finance.users.store'), [
            'name' => 'Otro',
            'email' => 'corto@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ])
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'corto@example.com')->exists())->toBeFalse();
});

it('does not make a created user an administrator', function () {
    $owner = ownerActing();

    $this->actingAs($owner)->post(route('finance.users.store'), [
        'name' => 'Persona Nueva',
        'email' => 'nueva@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $created = User::where('email', 'nueva@example.com')->firstOrFail();

    expect($created->isFinanceOwner())->toBeFalse();
});

it('keeps public registration closed when disabled', function () {
    config()->set('auth.registration_enabled', false);

    $this->get('/register')->assertNotFound();
});
