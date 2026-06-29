<?php

use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceMaintenanceService;
use App\Services\Finance\FinancePendingResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
    config()->set('finance.owner_email', null);
});

it('lightweight pending counts match the full run total exactly', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = User::factory()->create();

    // Movimiento sin categoría (cuenta para el grupo correspondiente).
    Movement::create(['user_id' => $user->id, 'happened_on' => '2026-06-10', 'movement_type' => 'expense', 'amount' => 100, 'description' => 'Sin cat', 'source' => 'manual', 'category_id' => null]);

    // Pago planeado vencido con saldo (amount > paid) -> SÍ cuenta.
    PlannedPayment::create(['user_id' => $user->id, 'period_month' => '2026-06-01', 'due_date' => '2026-06-05', 'name' => 'Vencido con saldo', 'amount' => 100, 'paid_amount' => 0, 'status' => 'pending']);
    // Pago planeado vencido pero ya cubierto (amount == paid) -> NO cuenta (filtro whereColumn).
    PlannedPayment::create(['user_id' => $user->id, 'period_month' => '2026-06-01', 'due_date' => '2026-06-05', 'name' => 'Vencido cubierto', 'amount' => 100, 'paid_amount' => 100, 'status' => 'pending']);

    $service = app(FinancePendingResolutionService::class);
    $counts = $service->summaryCounts($user);
    $full = $service->run($user)['summary'];

    expect($counts['total'])->toBe($full['total'])
        ->and($counts['groups'])->toBe($full['groups'])
        ->and($counts['groups']['planned_overdue'])->toBe(1) // solo el que tiene saldo
        ->and($counts['groups']['movements_without_category'])->toBe(1);
});

it('lets the owner optimize the app for production', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    // Servicio falso: NO ejecuta `optimize` de verdad (evita cachear config/rutas
    // dentro de la suite de pruebas).
    app()->instance(FinanceMaintenanceService::class, new class extends FinanceMaintenanceService {
        public function optimizeForProduction(): array
        {
            return ['ok' => true, 'action' => 'optimize', 'output' => 'cacheado (fake)'];
        }
    });

    $this->actingAs($owner)
        ->post(route('finance.maintenance.optimize'))
        ->assertRedirect(route('finance.security.index'))
        ->assertSessionHas('success');
});

it('forbids a normal user from optimizing the app', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->post(route('finance.maintenance.optimize'))
        ->assertForbidden();
});
