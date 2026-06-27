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
