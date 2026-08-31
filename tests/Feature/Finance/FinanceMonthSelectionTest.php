<?php

use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-08-31 12:00:00');
    $this->user = User::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('keeps september selected and filters the requested records', function () {
    PlannedPayment::create([
        'user_id' => $this->user->id,
        'period_month' => '2026-09-01',
        'due_date' => '2026-09-15',
        'name' => 'Pago exclusivo septiembre',
        'amount' => 100,
        'status' => 'pending',
    ]);
    PlannedPayment::create([
        'user_id' => $this->user->id,
        'period_month' => '2026-10-01',
        'due_date' => '2026-10-15',
        'name' => 'Pago exclusivo octubre',
        'amount' => 200,
        'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $this->user->id,
        'period_month' => '2026-09-01',
        'due_date' => '2026-09-15',
        'name' => 'Ingreso exclusivo septiembre',
        'amount' => 300,
        'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $this->user->id,
        'period_month' => '2026-10-01',
        'due_date' => '2026-10-15',
        'name' => 'Ingreso exclusivo octubre',
        'amount' => 400,
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->get(route('finance.planned.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertViewHas('monthValue', '2026-09')
        ->assertSee('Pago exclusivo septiembre')
        ->assertDontSee('Pago exclusivo octubre')
        ->assertSee('value="2026-10"', false);

    $this->actingAs($this->user)
        ->get(route('finance.expected-incomes.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertViewHas('monthValue', '2026-09')
        ->assertSee('Ingreso exclusivo septiembre')
        ->assertDontSee('Ingreso exclusivo octubre')
        ->assertSee('value="2026-10"', false);
});

it('persists and copies september instead of overflowing to october', function () {
    $this->actingAs($this->user)
        ->post(route('finance.planned.store'), [
            'period_month' => '2026-09',
            'due_date' => '2026-09-20',
            'name' => 'Pago creado septiembre',
            'amount' => 150,
        ])
        ->assertRedirect();

    $this->actingAs($this->user)
        ->post(route('finance.expected-incomes.store'), [
            'period_month' => '2026-09',
            'due_date' => '2026-09-20',
            'name' => 'Ingreso creado septiembre',
            'amount' => 250,
        ])
        ->assertRedirect();

    expect(PlannedPayment::where('name', 'Pago creado septiembre')->firstOrFail()->period_month->toDateString())
        ->toBe('2026-09-01')
        ->and(ExpectedIncome::where('name', 'Ingreso creado septiembre')->firstOrFail()->period_month->toDateString())
        ->toBe('2026-09-01');

    PlannedPayment::create([
        'user_id' => $this->user->id,
        'period_month' => '2026-08-01',
        'due_date' => '2026-08-31',
        'name' => 'Plantilla pago agosto',
        'amount' => 175,
        'status' => 'pending',
    ]);
    ExpectedIncome::create([
        'user_id' => $this->user->id,
        'period_month' => '2026-08-01',
        'due_date' => '2026-08-31',
        'name' => 'Plantilla ingreso agosto',
        'amount' => 275,
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->post(route('finance.planned.copy'), [
            'source_month' => '2026-08',
            'target_month' => '2026-09',
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-09']));

    $this->actingAs($this->user)
        ->post(route('finance.expected-incomes.copy'), [
            'source_month' => '2026-08',
            'target_month' => '2026-09',
        ])
        ->assertRedirect(route('finance.expected-incomes.index', ['month' => '2026-09']));

    $copiedPayment = PlannedPayment::where('name', 'Plantilla pago agosto')
        ->whereDate('period_month', '2026-09-01')
        ->firstOrFail();
    $copiedIncome = ExpectedIncome::where('name', 'Plantilla ingreso agosto')
        ->whereDate('period_month', '2026-09-01')
        ->firstOrFail();

    expect($copiedPayment->due_date->toDateString())->toBe('2026-09-30')
        ->and($copiedIncome->due_date->toDateString())->toBe('2026-09-30');
});

it('keeps september selected across finance monthly screens', function (string $routeName) {
    $this->actingAs($this->user)
        ->get(route($routeName, ['month' => '2026-09']))
        ->assertOk()
        ->assertSee('value="2026-09"', false);
})->with([
    'resumen' => 'finance.dashboard',
    'movimientos' => 'finance.movements.index',
    'cortes' => 'finance.cuts.index',
    'flujo planeado' => 'finance.planned.index',
    'ingresos esperados' => 'finance.expected-incomes.index',
    'san juan' => 'finance.san-juan.index',
    'reportes' => 'finance.reports.index',
    'revision mensual' => 'finance.monthly-review.index',
]);
