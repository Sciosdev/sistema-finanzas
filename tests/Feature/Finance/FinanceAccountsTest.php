<?php

use App\Models\Finance\Account;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates the default Onix account once and shows it across finance forms', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);
    app(FinanceCatalogService::class)->ensureForUser($user);

    expect(Account::where('user_id', $user->id)->where('name', 'Onix')->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('finance.accounts.index'))
        ->assertOk()
        ->assertSee('Cuentas financieras')
        ->assertSee('Onix')
        ->assertSee('Banco');

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee('Onix');

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Onix');

    $this->actingAs($user)
        ->get(route('finance.expected-incomes.index'))
        ->assertOk()
        ->assertSee('Onix');

    $this->actingAs($user)
        ->get(route('finance.cuts.index'))
        ->assertOk()
        ->assertSee('Onix');
});

it('lets users create edit and deactivate accounts without deleting history', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('finance.accounts.store'), [
            'name' => 'Caja prueba',
            'type' => 'efectivo',
            'color' => '#16a34a',
            'display_order' => 95,
            'is_active' => '1',
            'notes' => 'Cuenta temporal para prueba',
        ])
        ->assertRedirect();

    $account = Account::where('user_id', $user->id)->where('name', 'Caja prueba')->firstOrFail();

    expect($account->type)->toBe('cash')
        ->and($account->is_active)->toBeTrue()
        ->and($account->color)->toBe('#16a34a');

    $this->actingAs($user)
        ->put(route('finance.accounts.update', $account), [
            'name' => 'Caja prueba editada',
            'type' => 'banco',
            'color' => '#2563eb',
            'display_order' => 96,
            'is_active' => '0',
            'notes' => 'Ya no se usa para capturas nuevas',
        ])
        ->assertRedirect();

    $account->refresh();

    expect($account->name)->toBe('Caja prueba editada')
        ->and($account->type)->toBe('bank')
        ->and($account->is_active)->toBeFalse();

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertDontSee('Caja prueba editada');
});
