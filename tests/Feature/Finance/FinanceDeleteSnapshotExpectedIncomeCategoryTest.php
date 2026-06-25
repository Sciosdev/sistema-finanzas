<?php

use App\Models\Finance\Category;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createFinanceUserForExpectedIncomeCategorySnapshots(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return makeFinanceOwner($user);
}

it('deletes a pending expected income and restores it before two minutes', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();
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

    $this->actingAs($user)
        ->delete(route('finance.expected-incomes.destroy', $income))
        ->assertRedirect()
        ->assertSessionHas('success', 'Ingreso esperado eliminado.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(ExpectedIncome::whereKey($income->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('success', 'Ingreso esperado restaurado.');

    $restored = ExpectedIncome::findOrFail($income->id);

    expect($restored->name)->toBe('Pago ITTLA');
    expect((float) $restored->amount)->toBe(5800.0);
    expect($restored->movement_id)->toBeNull();

    Carbon::setTestNow();
});

it('deletes an expected income with movement id and restores the link when movement exists', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'income',
        'amount' => 1200,
        'description' => 'Ingreso recibido',
        'source' => 'manual',
    ]);

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-22',
        'name' => 'Ingreso ligado',
        'amount' => 1200,
        'received_amount' => 1200,
        'received_on' => '2026-06-22',
        'status' => 'received',
        'movement_id' => $movement->id,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.expected-incomes.destroy', $income))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Ingreso esperado restaurado.');

    expect(ExpectedIncome::findOrFail($income->id)->movement_id)->toBe($movement->id);

    Carbon::setTestNow();
});

it('restores expected income without movement id when the linked movement no longer exists', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'income',
        'amount' => 700,
        'description' => 'Ingreso que desaparece',
        'source' => 'manual',
    ]);

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-22',
        'name' => 'Ingreso sin movimiento futuro',
        'amount' => 700,
        'received_amount' => 700,
        'received_on' => '2026-06-22',
        'status' => 'received',
        'movement_id' => $movement->id,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.expected-incomes.destroy', $income))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Movement::whereKey($movement->id)->delete();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Ingreso esperado restaurado, pero el movimiento vinculado ya no existe.');

    expect(ExpectedIncome::findOrFail($income->id)->movement_id)->toBeNull();

    Carbon::setTestNow();
});

it('does not restore expected income after the two minute window expires', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'name' => 'Ingreso expirado',
        'amount' => 500,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.expected-incomes.destroy', $income))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Carbon::setTestNow('2026-06-22 10:03:00');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'El tiempo para deshacer ya expiró.');

    expect(ExpectedIncome::whereKey($income->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('prevents restoring the same expected income token twice', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'name' => 'Ingreso unico',
        'amount' => 900,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.expected-incomes.destroy', $income))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Ingreso esperado restaurado.');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'Ese borrado ya fue restaurado.');

    expect(ExpectedIncome::whereKey($income->id)->count())->toBe(1);

    Carbon::setTestNow();
});

it('undoes deactivation for a category that is in use', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Categoria usada',
        'type' => 'expense',
        'group' => 'Pruebas',
        'color' => '#123456',
        'is_active' => true,
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Movimiento con categoria',
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.categories.destroy', $category))
        ->assertSessionHas('success', 'Categoría desactivada para conservar el historial.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(Category::findOrFail($category->id)->is_active)->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Categoría restaurada.');

    $category->refresh();

    expect($category->is_active)->toBeTrue();
    expect($category->color)->toBe('#123456');

    Carbon::setTestNow();
});

it('restores an unused deleted category', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Categoria temporal',
        'type' => 'expense',
        'group' => 'Pruebas',
        'color' => '#654321',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.categories.destroy', $category))
        ->assertSessionHas('success', 'Categoría eliminada.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(Category::whereKey($category->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Categoría restaurada.');

    $restored = Category::findOrFail($category->id);

    expect($restored->name)->toBe('Categoria temporal');
    expect($restored->is_active)->toBeTrue();

    Carbon::setTestNow();
});

it('fails safely when restoring a deleted category with duplicated name and type', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForExpectedIncomeCategorySnapshots();

    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Categoria duplicada',
        'type' => 'expense',
        'group' => 'Pruebas',
        'color' => '#111111',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.categories.destroy', $category))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Category::create([
        'user_id' => $user->id,
        'name' => 'Categoria duplicada',
        'type' => 'expense',
        'group' => 'Otra',
        'color' => '#222222',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'No se pudo restaurar porque ya existe otra categoría con el mismo nombre y tipo.');

    expect(Category::where('user_id', $user->id)
        ->where('name', 'Categoria duplicada')
        ->where('type', 'expense')
        ->count())->toBe(1);
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});
