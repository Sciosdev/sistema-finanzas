<?php

use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function mobileUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('links the static mobile stylesheet in the head', function () {
    $this->actingAs(mobileUser())
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee('css/finance-mobile.css', false);
});

it('renders the mobile bottom navigation with quick capture', function () {
    $this->actingAs(mobileUser())
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('finance-bottom-nav', false)
        ->assertSee('Capturar')
        ->assertSee('capture=1', false)
        ->assertSee('button-toggle-menu', false);
});

it('keeps the desktop table but adds a mobile card list on movements', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = mobileUser();

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 150,
        'description' => 'Tacos del centro',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('finance-mobile-list', false)               // lista de tarjetas móvil
        ->assertSee('table-responsive d-none d-md-block', false) // tabla solo en escritorio
        ->assertSee('Tacos del centro');                         // dato visible en ambas vistas
});

it('focuses the capture form only when arriving with capture=1', function () {
    $user = mobileUser();

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['capture' => 1]))
        ->assertOk()
        ->assertSee('scrollIntoView', false);

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertDontSee('scrollIntoView', false);
});
