<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use App\Services\Finance\FinanceWeeklyEnvelopeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-01 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function envelopeAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Efectivo',
        'type' => 'cash',
        'opening_balance' => 5100,
        'is_active' => true,
    ], $attributes));
}

function envelopeCategory(User $user, string $name): Category
{
    return Category::create([
        'user_id' => $user->id,
        'name' => $name,
        'type' => 'expense',
        'group' => 'Flexible',
        'is_active' => true,
    ]);
}

function envelopeMovement(User $user, Category $category, float $amount, string $date): Movement
{
    return Movement::create([
        'user_id' => $user->id,
        'happened_on' => $date,
        'movement_type' => 'expense',
        'amount' => $amount,
        'description' => 'Gasto '.$category->name,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);
}

function envelopePlan(User $user): array
{
    return app(FinanceWeeklyEnvelopeService::class)->build($user);
}

it('splits the monthly living pool into weekly caps across both quincenas', function () {
    $user = User::factory()->create();
    envelopeAccount($user, ['opening_balance' => 5100]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2000]);
    $comida = envelopeCategory($user, 'Comida');
    $tienda = envelopeCategory($user, 'Tienda');
    envelopeMovement($user, $comida, 600, '2026-06-10');
    envelopeMovement($user, $tienda, 400, '2026-06-12');

    $plan = envelopePlan($user);

    expect($plan['meta']['living_pool_month'])->toBe(3100.0)
        ->and($plan['meta']['total_days'])->toBe(31)
        ->and($plan['meta']['daily_cap'])->toBe(100.0)
        ->and($plan['meta']['weeks_count'])->toBe(6)
        ->and($plan['meta']['has_historical_basis'])->toBeTrue();

    // Reparte por todo el mes: la 1ª quincena no se lleva todo el pool.
    $weeks = collect($plan['weeks']);
    $q1Caps = $weeks->take(3)->sum('week_cap');
    $q2Caps = $weeks->slice(3)->sum('week_cap');

    expect(round($q1Caps + $q2Caps, 2))->toBe(3100.0)
        ->and($q1Caps)->toBe(1500.0)
        ->and($q2Caps)->toBe(1600.0)
        ->and($plan['weeks'][3]['quincena_label'])->toBe('2ª quincena de julio 2026')
        ->and($plan['weeks'][3]['week_cap'])->toBe(700.0);
});

it('builds category envelopes from last month spending pattern', function () {
    $user = User::factory()->create();
    envelopeAccount($user, ['opening_balance' => 5100]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2000]);
    $comida = envelopeCategory($user, 'Comida');
    $tienda = envelopeCategory($user, 'Tienda');
    envelopeMovement($user, $comida, 600, '2026-06-10');
    envelopeMovement($user, $tienda, 400, '2026-06-12');

    $plan = envelopePlan($user);
    $weights = collect($plan['category_weights'])->keyBy('category_name');
    $currentWeek = $plan['current_week'];
    $currentByName = collect($currentWeek['categories'])->keyBy('category_name');

    expect($currentWeek['is_current'])->toBeTrue()
        ->and($currentWeek['week_cap'])->toBe(700.0)
        ->and($weights['Comida']['weight_percent'])->toBe(60.0)
        ->and($weights['Tienda']['weight_percent'])->toBe(40.0)
        // 700 x 60% y 700 x 40%
        ->and($currentByName['Comida']['envelope'])->toBe(420.0)
        ->and($currentByName['Tienda']['envelope'])->toBe(280.0);
});

it('applies cross category tradeoff when the weekly cap is spent in one category', function () {
    $user = User::factory()->create();
    envelopeAccount($user, ['opening_balance' => 5100]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2000]);
    $comida = envelopeCategory($user, 'Comida');
    $tienda = envelopeCategory($user, 'Tienda');
    envelopeMovement($user, $comida, 600, '2026-06-10');
    envelopeMovement($user, $tienda, 400, '2026-06-12');
    // Esta semana se gasta todo el tope (700) en Comida (sin cuenta: no toca el pool).
    envelopeMovement($user, $comida, 700, '2026-07-01');

    $plan = envelopePlan($user);
    $currentWeek = $plan['current_week'];
    $byName = collect($currentWeek['categories'])->keyBy('category_name');

    expect($currentWeek['week_cap'])->toBe(700.0)
        ->and($currentWeek['spent_total'])->toBe(700.0)
        ->and($currentWeek['remaining_total'])->toBe(0.0)
        ->and($currentWeek['tradeoff_active'])->toBeTrue()
        ->and($byName['Comida']['spent'])->toBe(700.0)
        ->and($byName['Comida']['over_envelope'])->toBeTrue()
        ->and($byName['Comida']['effective_remaining'])->toBe(0.0)
        // Aunque Tienda no gastó nada, su disponible efectivo baja a 0 por el tope.
        ->and($byName['Tienda']['own_remaining'])->toBe(280.0)
        ->and($byName['Tienda']['effective_remaining'])->toBe(0.0);
});

it('falls back to a single daily bucket when there is no last month history', function () {
    $user = User::factory()->create();
    envelopeAccount($user, ['opening_balance' => 5100]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2000]);

    $plan = envelopePlan($user);

    expect($plan['meta']['has_historical_basis'])->toBeFalse()
        ->and($plan['category_weights'])->toHaveCount(1)
        ->and($plan['category_weights'][0]['category_name'])->toBe('Gastos diarios')
        ->and($plan['category_weights'][0]['weight_percent'])->toBe(100.0);
});

it('does not create movements or change states while building the envelope plan', function () {
    $user = User::factory()->create();
    envelopeAccount($user, ['opening_balance' => 5100]);
    $comida = envelopeCategory($user, 'Comida');
    envelopeMovement($user, $comida, 600, '2026-06-10');
    $movementCount = Movement::count();

    envelopePlan($user);

    expect(Movement::count())->toBe($movementCount);
});

it('does not use data from another user', function () {
    $user = User::factory()->create();
    envelopeAccount($user, ['opening_balance' => 5100]);
    $comida = envelopeCategory($user, 'Comida');
    envelopeMovement($user, $comida, 600, '2026-06-10');

    $other = User::factory()->create();
    $otherCat = envelopeCategory($other, 'Ajeno');
    envelopeMovement($other, $otherCat, 5000, '2026-06-10');

    $plan = envelopePlan($user);

    expect(json_encode($plan))->not->toContain('Ajeno');
});
