<?php

use App\Models\User;
use App\Services\Finance\FinanceHealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    config()->set('finance.owner_email', null);
});

it('lets the finance owner open the diagnostics screen', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($owner)
        ->get(route('finance.health.index'))
        ->assertOk()
        ->assertSee('Diagnóstico de finanzas');
});

it('forbids a non owner from the diagnostics screen', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->get(route('finance.health.index'))
        ->assertForbidden();
});

it('includes the new production-stability checks', function () {
    $names = app(FinanceHealthCheckService::class)->run()->pluck('name');

    expect($names)
        ->toContain('APP_URL')
        ->toContain('FINANCE_OWNER_EMAIL')
        ->toContain('Registro de usuarios')
        ->toContain('Versión de PHP')
        ->toContain('Extensión pdo_mysql')
        ->toContain('Extensión mbstring')
        ->toContain('DB_CONNECTION')
        ->toContain('DB_DATABASE')
        ->toContain('DB_USERNAME')
        ->toContain('Autoload de Composer')
        ->toContain('Manifest de assets');
});

it('fails the owner check when no finance owner email is configured', function () {
    config()->set('finance.owner_email', '');

    $check = app(FinanceHealthCheckService::class)->run()->firstWhere('name', 'FINANCE_OWNER_EMAIL');

    expect($check)->not->toBeNull()
        ->and($check['status'])->toBe('fail');
});

it('reports the registration state in the diagnostics', function () {
    config()->set('auth.registration_enabled', true);

    $check = app(FinanceHealthCheckService::class)->run()->firstWhere('name', 'Registro de usuarios');

    expect($check)->not->toBeNull()
        ->and($check['detail'])->toContain('true');
});
