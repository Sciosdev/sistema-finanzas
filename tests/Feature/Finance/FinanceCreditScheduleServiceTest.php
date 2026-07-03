<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use App\Services\Finance\FinanceCreditScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-03 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function csAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta '.uniqid(),
        'type' => 'cash',
        'opening_balance' => 0,
        'is_active' => true,
    ], $attributes));
}

function csIncome(User $user, string $name, float $amount, string $dueDate): ExpectedIncome
{
    return ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => Carbon::parse($dueDate)->startOfMonth()->toDateString(),
        'due_date' => $dueDate,
        'name' => $name,
        'amount' => $amount,
        'received_amount' => 0,
        'status' => 'pending',
    ]);
}

function csFlow(User $user, string $name, float $amount, string $dueDate, array $extra = []): PlannedPayment
{
    return PlannedPayment::create(array_merge([
        'user_id' => $user->id,
        'period_month' => Carbon::parse($dueDate)->startOfMonth()->toDateString(),
        'due_date' => $dueDate,
        'name' => $name,
        'amount' => $amount,
        'paid_amount' => 0,
        'status' => 'pending',
    ], $extra));
}

function csCredit(User $user, string $name, float $amount, string $dueDate): void
{
    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => $name,
        'total_amount' => $amount,
        'months' => 1,
        'first_due_month' => Carbon::parse($dueDate)->startOfMonth()->toDateString(),
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => Carbon::parse($dueDate)->startOfMonth()->toDateString(),
        'due_date' => $dueDate,
        'installment_number' => 1,
        'amount' => $amount,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);
}

function csSchedule(User $user): array
{
    return app(FinanceCreditScheduleService::class)->build($user);
}

it('pays what fits now and defers the rest to after the next income', function () {
    $user = User::factory()->create();
    csAccount($user, ['name' => 'Efectivo', 'opening_balance' => 12920.92]);
    $nuCard = csAccount($user, ['name' => 'NU', 'type' => 'card', 'payment_day' => 27]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 2000]);

    csIncome($user, 'Cuarto 5', 2000, '2026-07-04');
    csIncome($user, 'Cuarto 1', 1200, '2026-07-15');
    csIncome($user, 'Consultoria', 2000, '2026-07-15');
    csIncome($user, 'Scios', 5000, '2026-07-15');
    csIncome($user, 'Cuarto 5 ago', 2000, '2026-08-04');

    csCredit($user, 'MPW', 2988.27, '2026-07-23');
    csCredit($user, 'NU credito', 4349.48, '2026-07-27');
    csCredit($user, 'Onix', 5000, '2026-07-30');
    csCredit($user, 'DIDI', 151.65, '2026-07-27');

    csFlow($user, 'Camara', 69, '2026-07-09');
    csFlow($user, 'Amazon Music', 149, '2026-07-13');
    csFlow($user, 'Agua', 400.67, '2026-07-13');
    csFlow($user, 'Meli+', 299, '2026-07-13');
    csFlow($user, 'Mega Cable', 550, '2026-07-10', ['is_credit' => true, 'account_id' => $nuCard->id]);
    csFlow($user, 'Camaras', 175, '2026-07-10', ['is_credit' => true, 'account_id' => $nuCard->id]);

    $schedule = csSchedule($user);
    $payNowNames = collect($schedule['pay_now']['items'])->pluck('account_name')->all();

    // Hoy caben MPW, NU y DIDI sin bajar del colchón; Onix espera al ingreso del 15.
    expect($schedule['pay_now']['total'])->toBe(7489.40)
        ->and($payNowNames)->toContain('MPW')
        ->and($payNowNames)->toContain('NU credito')
        ->and($payNowNames)->toContain('DIDI')
        ->and($payNowNames)->not->toContain('Onix')
        ->and($schedule['after_income'])->toHaveCount(1)
        ->and($schedule['after_income'][0]['checkpoint_date'])->toBe('2026-07-15')
        ->and($schedule['after_income'][0]['total'])->toBe(5000.0)
        ->and($schedule['after_income'][0]['items'][0]['account_name'])->toBe('Onix')
        ->and($schedule['deferred']['total'])->toBe(0.0)
        ->and($schedule['cushion_dip']['used'])->toBeFalse();
});

it('defers only the part that does not fit when the month cannot cover a credit', function () {
    $user = User::factory()->create();
    csAccount($user, ['name' => 'Efectivo', 'opening_balance' => 3000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 500]);

    csIncome($user, 'Pago chico', 1000, '2026-07-10');
    csCredit($user, 'Tarjeta', 4500, '2026-07-20');

    $schedule = csSchedule($user);

    // Cubre 3500 (hasta el colchón) y difiere solo 1000 al mes siguiente.
    expect($schedule['deferred']['total'])->toBe(1000.0)
        ->and($schedule['deferred']['items'][0]['account_name'])->toBe('Tarjeta')
        ->and($schedule['deferred']['items'][0]['amount'])->toBe(1000.0)
        ->and($schedule['cushion_dip']['used'])->toBeFalse()
        ->and(collect($schedule['after_income'])->flatMap(fn ($g) => $g['items'])->firstWhere('is_partial', true)['amount'])->toBe(3500.0);
});

it('allows dipping the cushion when a later secure income replenishes it', function () {
    $user = User::factory()->create();
    csAccount($user, ['name' => 'Efectivo', 'opening_balance' => 3000]);
    PlannerSetting::create(['user_id' => $user->id, 'minimum_buffer' => 1000]);

    csIncome($user, 'Ingreso grande', 4000, '2026-07-20');
    csCredit($user, 'Tarjeta', 2500, '2026-07-10');

    $schedule = csSchedule($user);

    expect($schedule['cushion_dip']['used'])->toBeTrue()
        ->and($schedule['cushion_dip']['total'])->toBe(2500.0)
        ->and($schedule['cushion_dip']['reponible_by_date'])->toBe('2026-07-20')
        ->and($schedule['deferred']['total'])->toBe(0.0)
        ->and($schedule['pay_now']['items'][0]['is_cushion_dip'])->toBeTrue();
});

it('does not create movements or change states while building the credit schedule', function () {
    $user = User::factory()->create();
    csAccount($user, ['name' => 'Efectivo', 'opening_balance' => 5000]);
    csIncome($user, 'Ingreso', 3000, '2026-07-15');
    csCredit($user, 'Tarjeta', 1000, '2026-07-20');
    $movementCount = Movement::count();
    $installment = CreditInstallment::first();

    csSchedule($user);

    expect(Movement::count())->toBe($movementCount)
        ->and($installment->fresh()->status)->toBe('pending')
        ->and((float) $installment->fresh()->paid_amount)->toBe(0.0);
});
