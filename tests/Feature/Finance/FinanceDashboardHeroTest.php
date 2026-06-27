<?php

use App\Models\Finance\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('greets the user by name with a time-aware message and shows the hero pills', function () {
    Carbon::setTestNow('2026-06-27 09:00:00');
    $user = User::factory()->create(['name' => 'Axel García Martínez']);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('Buenos días, Axel')
        ->assertSee('Proyectado del mes')
        ->assertSee('Pendientes')
        ->assertSee('Próximo pago')
        ->assertSee('finance-hero', false);
});

it('switches to an evening greeting at night', function () {
    Carbon::setTestNow('2026-06-27 21:00:00');
    $user = User::factory()->create(['name' => 'Axel']);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('Buenas noches, Axel');
});

it('celebrates a healthy savings rate in the hero', function () {
    Carbon::setTestNow('2026-06-15 10:00:00');
    $user = User::factory()->create(['name' => 'Axel']);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'income',
        'amount' => 5000,
        'description' => 'Sueldo',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('¡Vas muy bien este mes, Axel!');
});

it('warns in the hero when spending more than income', function () {
    Carbon::setTestNow('2026-06-15 10:00:00');
    $user = User::factory()->create(['name' => 'Axel']);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 1200,
        'description' => 'Compra grande',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('Vas gastando más de lo que entró');
});

it('exposes count-up hooks on the hero numbers', function () {
    $user = User::factory()->create(['name' => 'Axel']);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('data-countup', false)
        ->assertSee('data-countup-prefix="$"', false);
});
