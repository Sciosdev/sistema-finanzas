<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the dashboard with the hide-widget hooks', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk();

    // La distribución (incluidos los ocultos) se guarda en el servidor.
    $response->assertSee('data-save-url', false);

    // Hooks de CSS/JS para ocultar y restaurar cuadros desde el modo Diseño.
    $response->assertSee('dashboard-widget-hide', false)
        ->assertSee('dashboardHiddenTray', false)
        ->assertSee('Cuadros ocultos', false)
        ->assertSee('Mostrar todos', false);
});

it('keeps every summary widget present server-side so hiding stays client-only', function () {
    $user = User::factory()->create();

    // Ocultar es puramente visual en el navegador: el servidor sigue enviando
    // todos los cuadros (no se borra ni filtra nada en el backend).
    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-widget="income-real"', false)
        ->assertSee('data-dashboard-widget="san-juan-profit"', false);
});
