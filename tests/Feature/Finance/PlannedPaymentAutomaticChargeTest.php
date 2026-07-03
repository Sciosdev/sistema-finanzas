<?php

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
        ->assertSee('Cobro automático', false)
        ->assertSee('Ventana: 2026-07-09 a 2026-07-11', false);
});
