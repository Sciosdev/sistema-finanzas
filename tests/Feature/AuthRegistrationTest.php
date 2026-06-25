<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('blocks the registration form when registration is disabled', function () {
    config(['auth.registration_enabled' => false]);

    get('/register')->assertNotFound();
});

it('blocks registration submissions when registration is disabled', function () {
    config(['auth.registration_enabled' => false]);

    post('/register', [
        'name' => 'Nueva Persona',
        'email' => 'nueva@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    assertGuest();
    expect(User::query()->where('email', 'nueva@example.com')->exists())->toBeFalse();
});

it('does not show a registration link on the login page when registration is disabled', function () {
    config(['auth.registration_enabled' => false]);

    get('/login')
        ->assertOk()
        ->assertDontSee(route('register'), false);
});

it('shows the registration form when registration is enabled', function () {
    config(['auth.registration_enabled' => true]);

    get('/register')
        ->assertOk()
        ->assertSee('Sign Up');
});

it('keeps the current registration flow when registration is enabled', function () {
    config(['auth.registration_enabled' => true]);

    post('/register', [
        'name' => 'Nueva Persona',
        'email' => 'nueva@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/finanzas');

    $user = User::query()->where('email', 'nueva@example.com')->firstOrFail();

    assertAuthenticatedAs($user);
    assertDatabaseHas('users', [
        'name' => 'Nueva Persona',
        'email' => 'nueva@example.com',
    ]);
    expect(Hash::check('password', $user->password))->toBeTrue();
});
