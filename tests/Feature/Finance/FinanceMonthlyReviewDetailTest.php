<?php

use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceMonthlyReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes the affected movements in each suggestion', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Saldo Telcel',
        'source' => 'manual',
    ]);

    $review = app(FinanceMonthlyReviewService::class)->review($user, now()->setDate(2026, 6, 1));
    $suggestion = collect($review['suggestions'])->firstWhere('type', 'category_missing');

    expect($suggestion['movements'])->toHaveCount(1)
        ->and($suggestion['movements'][0]['description'])->toBe('Saldo Telcel')
        ->and($suggestion['movements'][0]['date'])->toBe('2026-06-20')
        ->and($suggestion['movements'][0]['amount'])->toBe(50.0);
});

it('does not create a bogus suggestion for movements that match nobody', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    // Coincide con la persona Andrea (sembrada en el catálogo).
    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'income',
        'amount' => 100,
        'description' => 'Deposito andrea',
        'source' => 'manual',
    ]);

    // No coincide con ninguna persona ni categoría.
    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-11',
        'movement_type' => 'expense',
        'amount' => 30,
        'description' => 'zzqwxyk indescifrable',
        'source' => 'manual',
    ]);

    $review = app(FinanceMonthlyReviewService::class)->review($user, now()->setDate(2026, 6, 1));
    $personSuggestions = collect($review['suggestions'])->where('type', 'person_missing');

    // Ninguna sugerencia de persona debe apuntar a una persona inexistente (id 0)
    // ni venir vacía: el bucket de "sin coincidencia" ya no existe.
    expect($personSuggestions)->isNotEmpty()
        ->and($personSuggestions->every(fn ($s) => ($s['person_id'] ?? 0) > 0))->toBeTrue()
        ->and($personSuggestions->every(fn ($s) => count($s['movements']) === $s['count']))->toBeTrue()
        ->and($personSuggestions->every(fn ($s) => $s['count'] > 0))->toBeTrue();
});

it('shows the affected movements detail on the monthly review screen', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Saldo Telcel detalle',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.monthly-review.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Movimientos que afectaría', false)
        ->assertSee('Saldo Telcel detalle');
});
