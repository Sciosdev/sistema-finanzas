<?php

use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceMonthlyReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('shows and applies safe monthly review suggestions', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Saldo Telcel',
        'source' => 'manual',
    ]);

    $review = app(FinanceMonthlyReviewService::class)->review($user, now()->setDate(2026, 6, 1));
    $suggestion = collect($review['suggestions'])->firstWhere('type', 'category_missing');

    expect($suggestion)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('finance.monthly-review.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Corrector mensual')
        ->assertSee('Categoría sugerida')
        ->assertSee('Saldo / Telefonia');

    $this->actingAs($user)
        ->post(route('finance.monthly-review.apply', ['key' => $suggestion['key'], 'month' => '2026-06']))
        ->assertRedirect(route('finance.monthly-review.index', ['month' => '2026-06']))
        ->assertSessionHas('success');

    $category = Category::where('user_id', $user->id)->where('name', 'Saldo / Telefonia')->firstOrFail();

    expect(Movement::findOrFail($movement->id)->category_id)->toBe($category->id);
});

it('exposes the PWA manifest and service worker', function () {
    $user = User::factory()->create();

    expect(File::exists(public_path('manifest.webmanifest')))->toBeTrue();
    expect(File::get(public_path('manifest.webmanifest')))->toContain('Sistema de Finanzas');
    expect(File::exists(public_path('service-worker.js')))->toBeTrue();
    expect(File::get(public_path('service-worker.js')))->toContain('finanzas-app-v1');

    $this->actingAs($user)
        ->get(route('finance.operations.index'))
        ->assertOk()
        ->assertSee('manifest')
        ->assertSee('service worker')
        ->assertSee('llaves VAPID');
});
