<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function historyImprovUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

function historyImprovMovement(User $user, array $overrides = []): Movement
{
    return Movement::create(array_merge([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Gasto',
        'source' => 'manual',
    ], $overrides));
}

it('shows the income, expense and net totals of the filtered set', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = historyImprovUser();

    historyImprovMovement($user, ['movement_type' => 'income', 'amount' => 1000, 'description' => 'Sueldo']);
    historyImprovMovement($user, ['movement_type' => 'yield', 'amount' => 50, 'description' => 'Rendimiento']);
    historyImprovMovement($user, ['movement_type' => 'expense', 'amount' => 300, 'description' => 'Super']);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Totales del filtro')
        ->assertSee('$1,050.00')  // ingresos + rendimientos
        ->assertSee('$300.00')    // egresos
        ->assertSee('$750.00');   // neto
});

it('totals only the filtered subset, not the whole month', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = historyImprovUser();

    historyImprovMovement($user, ['movement_type' => 'income', 'amount' => 1000, 'description' => 'Sueldo']);
    historyImprovMovement($user, ['movement_type' => 'expense', 'amount' => 300, 'description' => 'Super']);

    // Filtrando solo egresos, ingresos debe ser $0.00 y egresos $300.00.
    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06', 'type' => 'expense']))
        ->assertOk()
        ->assertSee('$300.00')
        ->assertSee('$0.00');
});

it('filters movements by account', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = historyImprovUser();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    historyImprovMovement($user, ['account_id' => $cash->id, 'description' => 'Gasto en efectivo']);
    historyImprovMovement($user, ['account_id' => $nu->id, 'description' => 'Gasto en NU']);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06', 'account_id' => $cash->id]))
        ->assertOk()
        ->assertSee('Gasto en efectivo')
        ->assertDontSee('Gasto en NU');
});

it('filters movements by the uncategorized flag', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = historyImprovUser();
    $category = Category::where('user_id', $user->id)->where('type', 'expense')->firstOrFail();

    historyImprovMovement($user, ['category_id' => null, 'description' => 'Sin clasificar aun']);
    historyImprovMovement($user, ['category_id' => $category->id, 'description' => 'Ya clasificado']);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06', 'flag' => 'uncategorized']))
        ->assertOk()
        ->assertSee('Sin clasificar aun')
        ->assertDontSee('Ya clasificado');
});

it('groups the history by day with a daily net subtotal', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = historyImprovUser();

    historyImprovMovement($user, ['happened_on' => '2026-06-10', 'movement_type' => 'income', 'amount' => 500, 'description' => 'Entro']);
    historyImprovMovement($user, ['happened_on' => '2026-06-10', 'movement_type' => 'expense', 'amount' => 200, 'description' => 'Salio']);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('table-active', false) // fila de encabezado del día
        ->assertSee('$300.00'); // neto del día (500 - 200)
});

it('warns about a likely duplicate without blocking the save', function () {
    $user = historyImprovUser();

    historyImprovMovement($user, ['happened_on' => '2026-06-15', 'amount' => 250, 'description' => 'Andrea Tienda']);

    $this->actingAs($user)
        ->post(route('finance.movements.store'), [
            'happened_on' => '2026-06-15',
            'movement_type' => 'expense',
            'amount' => 250,
            'description' => 'Andrea Tienda',
        ])
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionHas('warning');

    // Se guardó de todas formas (no bloquea): ahora hay 2.
    expect(Movement::where('user_id', $user->id)->where('description', 'Andrea Tienda')->count())->toBe(2);
});

it('does not warn when the movement is not a duplicate', function () {
    $user = historyImprovUser();

    historyImprovMovement($user, ['happened_on' => '2026-06-15', 'amount' => 250, 'description' => 'Andrea Tienda']);

    $this->actingAs($user)
        ->post(route('finance.movements.store'), [
            'happened_on' => '2026-06-15',
            'movement_type' => 'expense',
            'amount' => 251,
            'description' => 'Otra cosa',
        ])
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionMissing('warning');
});
