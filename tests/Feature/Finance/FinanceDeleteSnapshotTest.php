<?php

use App\Models\Finance\Category;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createFinanceUserForDeleteSnapshots(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('deletes a movement and restores it before two minutes', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForDeleteSnapshots();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'expense',
        'amount' => 123.45,
        'description' => 'Movimiento para deshacer',
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.movements.destroy', $movement))
        ->assertRedirect()
        ->assertSessionHas('success', 'Movimiento eliminado.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(Movement::whereKey($movement->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('success', 'Movimiento restaurado.');

    $restored = Movement::findOrFail($movement->id);

    expect($restored->description)->toBe('Movimiento para deshacer');
    expect((float) $restored->amount)->toBe(123.45);
    expect($restored->happened_on->toDateString())->toBe('2026-06-22');
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->not->toBeNull();

    Carbon::setTestNow();
});

it('does not restore a movement after the two minute window expires', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForDeleteSnapshots();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Movimiento expirado',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.movements.destroy', $movement))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Carbon::setTestNow('2026-06-22 10:03:00');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('error', 'El tiempo para deshacer ya expiró.');

    expect(Movement::whereKey($movement->id)->exists())->toBeFalse();
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});

it('deletes a planned payment and restores it before two minutes', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForDeleteSnapshots();
    $category = Category::where('user_id', $user->id)->where('name', 'Casa')->firstOrFail();

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'name' => 'Pago planeado para deshacer',
        'amount' => 250,
        'status' => 'pending',
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.planned.destroy', $payment))
        ->assertRedirect()
        ->assertSessionHas('success', 'Pago eliminado del flujo.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(PlannedPayment::whereKey($payment->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('success', 'Pago planeado restaurado.');

    $restored = PlannedPayment::findOrFail($payment->id);

    expect($restored->name)->toBe('Pago planeado para deshacer');
    expect((float) $restored->amount)->toBe(250.0);
    expect($restored->period_month->toDateString())->toBe('2026-06-01');

    Carbon::setTestNow();
});

it('prevents restoring the same delete token twice', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForDeleteSnapshots();

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'name' => 'Pago unico',
        'amount' => 100,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.planned.destroy', $payment))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Pago planeado restaurado.');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'Ese borrado ya fue restaurado.');

    expect(PlannedPayment::whereKey($payment->id)->count())->toBe(1);

    Carbon::setTestNow();
});

it('shows the undo button after deleting a movement', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForDeleteSnapshots();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'expense',
        'amount' => 80,
        'description' => 'Movimiento con boton',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->from(route('finance.movements.index', ['month' => '2026-06']))
        ->followingRedirects()
        ->delete(route('finance.movements.destroy', $movement))
        ->assertOk()
        ->assertSee('Movimiento eliminado.')
        ->assertSee('Deshacer')
        ->assertSee('Disponible por 2 minutos.');

    Carbon::setTestNow();
});

it('relinks planned payments when a deleted movement is restored', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForDeleteSnapshots();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-14',
        'movement_type' => 'expense',
        'amount' => 99,
        'description' => 'Amazon Shopping',
        'source' => 'manual',
    ]);

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-16',
        'name' => 'Amazon - Amazon Shopping',
        'amount' => 99,
        'paid_amount' => 99,
        'paid_on' => '2026-06-14',
        'status' => 'paid',
        'movement_id' => $movement->id,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.movements.destroy', $movement))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $payment->refresh();
    expect($payment->movement_id)->toBeNull();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Movimiento restaurado.');

    $payment->refresh();

    expect($payment->movement_id)->toBe($movement->id);

    Carbon::setTestNow();
});
