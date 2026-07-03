<?php

use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-02 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function plannedPaymentAutomaticUser(): User
{
    return User::factory()->create();
}

function plannedPaymentAutomaticPayment(User $user, array $attributes = []): PlannedPayment
{
    return PlannedPayment::create(array_merge([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Pago '.uniqid(),
        'amount' => 100,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $attributes));
}

it('creates a normal planned payment with automatic charge defaults disabled', function () {
    $user = plannedPaymentAutomaticUser();

    $this->actingAs($user)->post(route('finance.planned.store'), [
        'period_month' => '2026-07',
        'due_date' => '2026-07-10',
        'name' => 'Pago normal',
        'amount' => 250,
    ]);

    $payment = PlannedPayment::where('user_id', $user->id)->where('name', 'Pago normal')->firstOrFail();

    expect($payment->is_automatic_charge)->toBeFalse()
        ->and($payment->is_forced_charge_window)->toBeFalse()
        ->and($payment->charge_window_before_days)->toBe(0)
        ->and($payment->charge_window_after_days)->toBe(0);
});

it('creates a planned payment with automatic charge enabled', function () {
    $user = plannedPaymentAutomaticUser();

    $this->actingAs($user)->post(route('finance.planned.store'), [
        'period_month' => '2026-07',
        'due_date' => '2026-07-10',
        'name' => 'Netflix',
        'amount' => 199,
        'is_automatic_charge' => '1',
    ]);

    $payment = PlannedPayment::where('user_id', $user->id)->where('name', 'Netflix')->firstOrFail();

    expect($payment->is_automatic_charge)->toBeTrue()
        ->and($payment->is_forced_charge_window)->toBeFalse();
});

it('creates a planned payment with a forced charge window and stores before and after days', function () {
    $user = plannedPaymentAutomaticUser();

    $this->actingAs($user)->post(route('finance.planned.store'), [
        'period_month' => '2026-07',
        'due_date' => '2026-07-10',
        'name' => 'Google One',
        'amount' => 49,
        'is_automatic_charge' => '1',
        'is_forced_charge_window' => '1',
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $payment = PlannedPayment::where('user_id', $user->id)->where('name', 'Google One')->firstOrFail();

    expect($payment->is_forced_charge_window)->toBeTrue()
        ->and($payment->charge_window_before_days)->toBe(1)
        ->and($payment->charge_window_after_days)->toBe(1)
        ->and($payment->chargeWindowStart()?->toDateString())->toBe('2026-07-09')
        ->and($payment->chargeWindowEnd()?->toDateString())->toBe('2026-07-11');
});

it('forces automatic charge on when a planned payment uses a forced charge window', function () {
    $user = plannedPaymentAutomaticUser();

    $this->actingAs($user)->post(route('finance.planned.store'), [
        'period_month' => '2026-07',
        'due_date' => '2026-07-10',
        'name' => 'iCloud',
        'amount' => 79,
        'is_forced_charge_window' => '1',
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $payment = PlannedPayment::where('user_id', $user->id)->where('name', 'iCloud')->firstOrFail();

    expect($payment->is_automatic_charge)->toBeTrue()
        ->and($payment->is_forced_charge_window)->toBeTrue();
});

it('shows automatic charge and forced charge window labels on planned payment flow', function () {
    $user = plannedPaymentAutomaticUser();

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'name' => 'Google One',
        'amount' => 49,
        'status' => 'pending',
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-07']))
        ->assertOk()
        ->assertSee('Cobro autom', false)
        ->assertSee('Ventana: 2026-07-09 a 2026-07-11', false);
});

it('can mark multiple selected planned payments as forced automatic charges', function () {
    $user = plannedPaymentAutomaticUser();
    $google = plannedPaymentAutomaticPayment($user, ['name' => 'Google One']);
    $youtube = plannedPaymentAutomaticPayment($user, ['name' => 'YouTube Premium', 'due_date' => '2026-07-15']);

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'month' => '2026-07',
            'ids' => [$google->id, $youtube->id],
            'bulk_action' => 'set_forced_automatic',
            'charge_window_before_days' => 1,
            'charge_window_after_days' => 1,
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-07']))
        ->assertSessionHas('success', 'Se actualizaron 2 pagos planeados.');

    foreach ([$google->fresh(), $youtube->fresh()] as $payment) {
        expect($payment->is_automatic_charge)->toBeTrue()
            ->and($payment->is_forced_charge_window)->toBeTrue()
            ->and($payment->charge_window_before_days)->toBe(1)
            ->and($payment->charge_window_after_days)->toBe(1);
    }
});

it('can mark multiple planned payments as automatic charge only', function () {
    $user = plannedPaymentAutomaticUser();
    $amazon = plannedPaymentAutomaticPayment($user, ['name' => 'Amazon Music']);
    $uber = plannedPaymentAutomaticPayment($user, ['name' => 'Uber One']);

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'month' => '2026-07',
            'ids' => [$amazon->id, $uber->id],
            'bulk_action' => 'set_automatic_only',
            'charge_window_before_days' => 7,
            'charge_window_after_days' => 7,
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-07']));

    foreach ([$amazon->fresh(), $uber->fresh()] as $payment) {
        expect($payment->is_automatic_charge)->toBeTrue()
            ->and($payment->is_forced_charge_window)->toBeFalse()
            ->and($payment->charge_window_before_days)->toBe(0)
            ->and($payment->charge_window_after_days)->toBe(0);
    }
});

it('can clear automatic charge from multiple planned payments', function () {
    $user = plannedPaymentAutomaticUser();
    $netflix = plannedPaymentAutomaticPayment($user, [
        'name' => 'Netflix',
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 1,
        'charge_window_after_days' => 1,
    ]);
    $icloud = plannedPaymentAutomaticPayment($user, [
        'name' => 'iCloud',
        'is_automatic_charge' => true,
        'is_forced_charge_window' => true,
        'charge_window_before_days' => 2,
        'charge_window_after_days' => 2,
    ]);

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'month' => '2026-07',
            'ids' => [$netflix->id, $icloud->id],
            'bulk_action' => 'clear_automatic',
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-07']));

    foreach ([$netflix->fresh(), $icloud->fresh()] as $payment) {
        expect($payment->is_automatic_charge)->toBeFalse()
            ->and($payment->is_forced_charge_window)->toBeFalse()
            ->and($payment->charge_window_before_days)->toBe(0)
            ->and($payment->charge_window_after_days)->toBe(0);
    }
});

it('keeps automatic charge true when bulk action uses a forced charge window', function () {
    $user = plannedPaymentAutomaticUser();
    $payment = plannedPaymentAutomaticPayment($user, ['name' => 'Google One']);

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'ids' => [$payment->id],
            'bulk_action' => 'set_forced_automatic',
            'charge_window_before_days' => 1,
            'charge_window_after_days' => 1,
        ]);

    expect($payment->fresh()->is_automatic_charge)->toBeTrue()
        ->and($payment->fresh()->is_forced_charge_window)->toBeTrue();
});

it('does not modify planned payments from another user through bulk automatic charge', function () {
    $user = plannedPaymentAutomaticUser();
    $other = plannedPaymentAutomaticUser();
    $otherPayment = plannedPaymentAutomaticPayment($other, ['name' => 'Pago ajeno']);

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'ids' => [$otherPayment->id],
            'bulk_action' => 'set_forced_automatic',
            'charge_window_before_days' => 1,
            'charge_window_after_days' => 1,
        ])
        ->assertSessionHasErrors('ids.0');

    expect($otherPayment->fresh()->is_automatic_charge)->toBeFalse()
        ->and($otherPayment->fresh()->is_forced_charge_window)->toBeFalse();
});

it('does not modify planned payments that were not selected for bulk automatic charge', function () {
    $user = plannedPaymentAutomaticUser();
    $selected = plannedPaymentAutomaticPayment($user, ['name' => 'Seleccionado']);
    $notSelected = plannedPaymentAutomaticPayment($user, ['name' => 'No seleccionado']);

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'ids' => [$selected->id],
            'bulk_action' => 'set_forced_automatic',
            'charge_window_before_days' => 1,
            'charge_window_after_days' => 1,
        ]);

    expect($selected->fresh()->is_automatic_charge)->toBeTrue()
        ->and($notSelected->fresh()->is_automatic_charge)->toBeFalse()
        ->and($notSelected->fresh()->is_forced_charge_window)->toBeFalse();
});

it('bulk automatic charge does not change amount due date status or create movements', function () {
    $user = plannedPaymentAutomaticUser();
    $payment = plannedPaymentAutomaticPayment($user, [
        'name' => 'Luz',
        'due_date' => '2026-07-20',
        'amount' => 345.67,
        'status' => 'overdue',
    ]);
    $movementCount = Movement::count();

    $this->actingAs($user)
        ->post(route('finance.planned.bulk-automatic-charge'), [
            'ids' => [$payment->id],
            'bulk_action' => 'set_forced_automatic',
            'charge_window_before_days' => 1,
            'charge_window_after_days' => 1,
        ]);

    $payment->refresh();

    expect((float) $payment->amount)->toBe(345.67)
        ->and($payment->due_date->toDateString())->toBe('2026-07-20')
        ->and($payment->status)->toBe('overdue')
        ->and(Movement::count())->toBe($movementCount);
});

it('shows bulk automatic charge controls and payment checkboxes on planned payment flow', function () {
    $user = plannedPaymentAutomaticUser();

    plannedPaymentAutomaticPayment($user, ['name' => 'Google One']);

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-07']))
        ->assertOk()
        ->assertSee('Acciones masivas', false)
        ->assertSee('Seleccionar todos los pagos visibles', false)
        ->assertSee('data-bulk-planned-form', false)
        ->assertSee('data-bulk-planned-payment-checkbox', false)
        ->assertSee('Esta acción no paga nada ni crea movimientos.', false);
});
