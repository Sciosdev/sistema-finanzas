<?php

use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceMonthlyReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewIgnoreUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Saldo Telcel para ignorar',
        'source' => 'manual',
    ]);

    return $user;
}

function firstReviewKey(User $user): string
{
    $review = app(FinanceMonthlyReviewService::class)->review($user, now()->setDate(2026, 6, 1));

    return collect($review['suggestions'])->firstWhere('type', 'category_missing')['key'];
}

it('hides a suggestion after it is ignored', function () {
    $user = reviewIgnoreUser();
    $key = firstReviewKey($user);

    $this->actingAs($user)
        ->get(route('finance.monthly-review.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Saldo Telcel para ignorar');

    $this->actingAs($user)
        ->post(route('finance.monthly-review.ignore', ['key' => $key, 'month' => '2026-06']))
        ->assertRedirect(route('finance.monthly-review.index', ['month' => '2026-06']));

    $this->actingAs($user)
        ->get(route('finance.monthly-review.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertDontSee('Saldo Telcel para ignorar')
        ->assertSee('Restaurar ignoradas (1)');
});

it('restores ignored suggestions for the month', function () {
    $user = reviewIgnoreUser();
    $key = firstReviewKey($user);

    $this->actingAs($user)->post(route('finance.monthly-review.ignore', ['key' => $key, 'month' => '2026-06']));

    $this->actingAs($user)
        ->post(route('finance.monthly-review.restore-ignored', ['month' => '2026-06']))
        ->assertRedirect(route('finance.monthly-review.index', ['month' => '2026-06']));

    $this->actingAs($user)
        ->get(route('finance.monthly-review.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Saldo Telcel para ignorar');
});

it('does not modify any movement when ignoring', function () {
    $user = reviewIgnoreUser();
    $key = firstReviewKey($user);
    $movement = Movement::where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->post(route('finance.monthly-review.ignore', ['key' => $key, 'month' => '2026-06']));

    expect($movement->fresh()->category_id)->toBeNull();
});
