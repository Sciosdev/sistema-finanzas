<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\RentalContract;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('lets users edit expected incomes', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();
    $person = Person::create([
        'user_id' => $user->id,
        'name' => 'ITTLA',
        'type' => 'other',
        'is_active' => true,
    ]);

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-26',
        'name' => 'Pago ITTLA',
        'amount' => 5800,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.expected-incomes.index', ['month' => '2026-06', 'edit' => $income->id]))
        ->assertOk()
        ->assertSee('Pago ITTLA')
        ->assertSee('Guardar');

    $this->actingAs($user)
        ->put(route('finance.expected-incomes.update', $income), [
            'period_month' => '2026-06',
            'due_date' => '2026-06-27',
            'name' => 'Pago ITTLA actualizado',
            'amount' => 6000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'person_id' => $person->id,
            'notes' => 'Factura junio',
        ])
        ->assertRedirect(route('finance.expected-incomes.index', ['month' => '2026-06']));

    $income->refresh();

    expect($income->name)->toBe('Pago ITTLA actualizado');
    expect($income->due_date->toDateString())->toBe('2026-06-27');
    expect((float) $income->amount)->toBe(6000.0);
    expect($income->account_id)->toBe($account->id);
    expect($income->category_id)->toBe($category->id);
    expect($income->person_id)->toBe($person->id);
});

it('links and unlinks expected incomes with real income movements', function () {
    Carbon::setTestNow('2026-06-22 12:00:00');
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-26',
        'name' => 'Pago ITTLA',
        'amount' => 5800,
        'status' => 'pending',
        'category_id' => $category->id,
    ]);

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-25',
        'movement_type' => 'income',
        'amount' => 5800,
        'description' => 'Deposito ITTLA',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.expected-incomes.link', $income))
        ->assertOk()
        ->assertSee('Vincular ingreso esperado')
        ->assertSee('Deposito ITTLA')
        ->assertSee('Monto coincide');

    $this->actingAs($user)
        ->post(route('finance.expected-incomes.link-movement', $income), [
            'movement_id' => $movement->id,
        ])
        ->assertRedirect(route('finance.expected-incomes.index', ['month' => '2026-06']));

    $income->refresh();

    expect($income->status)->toBe('received');
    expect((float) $income->received_amount)->toBe(5800.0);
    expect($income->received_on->toDateString())->toBe('2026-06-25');
    expect($income->movement_id)->toBe($movement->id);
    expect($income->account_id)->toBe($account->id);

    $this->actingAs($user)
        ->get(route('finance.expected-incomes.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Ligado')
        ->assertSee('Deposito ITTLA');

    $this->actingAs($user)
        ->post(route('finance.expected-incomes.unlink-movement', $income))
        ->assertRedirect();

    $income->refresh();

    expect($income->status)->toBe('pending');
    expect((float) $income->received_amount)->toBe(0.0);
    expect($income->received_on)->toBeNull();
    expect($income->movement_id)->toBeNull();
});

it('shows San Juan rent detail expense concepts and movement actions', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $cesar = Person::where('user_id', $user->id)->where('name', 'Cesar')->firstOrFail();
    $rentCategory = Category::where('user_id', $user->id)->where('name', 'Rentas San Juan')->firstOrFail();
    $japam = Category::where('user_id', $user->id)
        ->where('name', 'JAPAM')
        ->where('type', 'expense')
        ->firstOrFail();

    RentalContract::where('user_id', $user->id)
        ->where('person_id', $cesar->id)
        ->update([
            'room' => '4',
            'expected_amount' => 2200,
            'due_day' => 27,
            'is_active' => true,
        ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-26',
        'movement_type' => 'income',
        'amount' => 2200,
        'description' => 'Renta Cesar',
        'account_id' => $account->id,
        'category_id' => $rentCategory->id,
        'person_id' => $cesar->id,
        'is_san_juan' => true,
        'is_rent' => true,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 350,
        'description' => 'JAPAM San Juan',
        'account_id' => $account->id,
        'category_id' => $japam->id,
        'is_san_juan' => true,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.san-juan.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('Detalle de rentas del mes')
        ->assertSee('Egresos por concepto')
        ->assertSee('Movimientos anidados por relación')
        ->assertSee('Renta: Cesar')
        ->assertSee('Resumen por persona')
        ->assertSee('Cesar')
        ->assertSee('Renta Cesar')
        ->assertSee('JAPAM')
        ->assertSee('$2,200.00')
        ->assertSee('$350.00')
        ->assertSee('Editar movimiento')
        ->assertSee('Eliminar con deshacer');
});
