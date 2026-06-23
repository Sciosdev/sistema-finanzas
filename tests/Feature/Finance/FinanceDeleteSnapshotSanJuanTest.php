<?php

use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\RentalContract;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createFinanceUserForSanJuanDeleteSnapshots(): User
{
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('deletes a San Juan rental contract and restores it before two minutes', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();
    $person = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    $contract->update([
        'room' => '3',
        'expected_amount' => 2000,
        'due_day' => 28,
        'is_active' => true,
        'manual_override' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertRedirect()
        ->assertSessionHas('success', 'Renta eliminada de la plantilla.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    expect(RentalContract::whereKey($contract->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertRedirect()
        ->assertSessionHas('success', 'Renta restaurada en la plantilla.');

    $restored = RentalContract::findOrFail($contract->id);

    expect($restored->person_id)->toBe($person->id);
    expect($restored->room)->toBe('3');
    expect((float) $restored->expected_amount)->toBe(2000.0);
    expect($restored->due_day)->toBe(28);

    Carbon::setTestNow();
});

it('restores tenant active state when the deleted contract was the last one', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();
    $person = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    expect($person->is_tenant)->toBeTrue();
    expect($person->is_active)->toBeTrue();

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $person->refresh();
    expect($person->is_tenant)->toBeFalse();
    expect($person->is_active)->toBeFalse();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Renta restaurada en la plantilla.');

    $person->refresh();
    expect($person->is_tenant)->toBeTrue();
    expect($person->is_active)->toBeTrue();

    Carbon::setTestNow();
});

it('does not deactivate a tenant when another rental contract remains', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();
    $person = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    RentalContract::create([
        'user_id' => $user->id,
        'person_id' => $person->id,
        'room' => 'Extra',
        'expected_amount' => 500,
        'due_day' => 15,
        'is_active' => true,
        'manual_override' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertSessionHas('undo_delete');

    $person->refresh();
    expect($person->is_tenant)->toBeTrue();
    expect($person->is_active)->toBeTrue();

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Renta restaurada en la plantilla.');

    expect(RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->count())->toBe(2);

    Carbon::setTestNow();
});

it('does not restore a San Juan rental contract after the two minute window expires', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();
    $person = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Carbon::setTestNow('2026-06-22 10:03:00');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'El tiempo para deshacer ya expiró.');

    expect(RentalContract::whereKey($contract->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('prevents restoring the same San Juan rental contract token twice', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();
    $person = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Renta restaurada en la plantilla.');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'Ese borrado ya fue restaurado.');

    expect(RentalContract::whereKey($contract->id)->count())->toBe(1);

    Carbon::setTestNow();
});

it('fails safely when restoring a San Juan rental contract without its tenant', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();
    $person = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    Person::whereKey($person->id)->delete();

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('error', 'No se pudo restaurar porque el inquilino ya no existe.');

    expect(RentalContract::whereKey($contract->id)->exists())->toBeFalse();
    expect(DeleteSnapshot::where('token', $undo['token'])->firstOrFail()->restored_at)->toBeNull();

    Carbon::setTestNow();
});

it('keeps San Juan movements covered by the normal movement undo flow', function () {
    Carbon::setTestNow('2026-06-22 10:00:00');

    $user = createFinanceUserForSanJuanDeleteSnapshots();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-22',
        'movement_type' => 'expense',
        'amount' => 350,
        'description' => 'Limpieza Jorge',
        'is_san_juan' => true,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->delete(route('finance.movements.destroy', $movement))
        ->assertSessionHas('success', 'Movimiento eliminado.')
        ->assertSessionHas('undo_delete');

    $undo = session('undo_delete');

    $this->actingAs($user)
        ->post(route('finance.security.undo-delete', $undo['token']))
        ->assertSessionHas('success', 'Movimiento restaurado.');

    $restored = Movement::findOrFail($movement->id);

    expect($restored->is_san_juan)->toBeTrue();
    expect($restored->description)->toBe('Limpieza Jorge');

    Carbon::setTestNow();
});
