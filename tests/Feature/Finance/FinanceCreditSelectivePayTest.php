<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-10 09:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function selPayAccount(User $user, string $name = 'NU'): Account
{
    return Account::create([
        'user_id' => $user->id,
        'name' => $name,
        'type' => 'card',
        'opening_balance' => 0,
        'is_active' => true,
    ]);
}

function selPayCredit(User $user, Account $account, string $name = 'NU compra', int $months = 3): CreditPurchase
{
    return CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-20',
        'name' => $name,
        'total_amount' => 1500,
        'months' => $months,
        'first_due_month' => '2026-07-01',
        'due_day' => 25,
        'account_id' => $account->id,
        'status' => 'active',
    ]);
}

function selPayInstallment(User $user, CreditPurchase $credit, int $number, float $amount, string $period = '2026-07-01', string $due = '2026-07-25'): CreditInstallment
{
    return CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => $period,
        'due_date' => $due,
        'installment_number' => $number,
        'amount' => $amount,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);
}

it('pays only the selected installments and creates a movement for each', function () {
    $user = User::factory()->create();
    $nu = selPayAccount($user, 'NU');
    $credit = selPayCredit($user, $nu);
    $inst1 = selPayInstallment($user, $credit, 1, 500, '2026-07-01', '2026-07-25');
    $inst2 = selPayInstallment($user, $credit, 2, 500, '2026-08-01', '2026-08-25');
    $inst3 = selPayInstallment($user, $credit, 3, 500, '2026-09-01', '2026-09-25');

    $movementsBefore = Movement::count();

    $this->actingAs($user)
        ->post(route('finance.credits.installments.pay-selected'), [
            'installment_ids' => [$inst1->id, $inst3->id],
        ])
        ->assertRedirect();

    expect($inst1->fresh()->status)->toBe('paid')
        ->and($inst3->fresh()->status)->toBe('paid')
        // La no seleccionada NO se toca.
        ->and($inst2->fresh()->status)->toBe('pending')
        ->and($inst1->fresh()->movement_id)->not->toBeNull()
        ->and($inst3->fresh()->movement_id)->not->toBeNull()
        ->and(Movement::count())->toBe($movementsBefore + 2)
        ->and(round(Movement::where('source', 'credit_installment')->sum('amount'), 2))->toBe(1000.0);
});

it('ignores installments from another user in the selection', function () {
    $user = User::factory()->create();
    $nu = selPayAccount($user, 'NU');
    $credit = selPayCredit($user, $nu);
    $own = selPayInstallment($user, $credit, 1, 500);

    $other = User::factory()->create();
    $otherNu = selPayAccount($other, 'NU');
    $otherCredit = selPayCredit($other, $otherNu, 'NU ajena');
    $otherInst = selPayInstallment($other, $otherCredit, 1, 999);

    $movementsBefore = Movement::count();

    $this->actingAs($user)
        ->post(route('finance.credits.installments.pay-selected'), [
            'installment_ids' => [$own->id, $otherInst->id],
        ]);

    expect($own->fresh()->status)->toBe('paid')
        ->and($otherInst->fresh()->status)->toBe('pending')
        ->and((float) $otherInst->fresh()->paid_amount)->toBe(0.0)
        ->and(Movement::count())->toBe($movementsBefore + 1);
});

it('shows the select-and-pay modal only for current month installments', function () {
    $user = User::factory()->create();
    Account::create([
        'user_id' => $user->id, 'name' => 'Efectivo', 'type' => 'cash',
        'opening_balance' => 5000, 'is_active' => true,
    ]);
    $nu = selPayAccount($user, 'NU');
    $nuCredit = selPayCredit($user, $nu, 'NU televisor');
    selPayInstallment($user, $nuCredit, 1, 500, '2026-07-01', '2026-07-25'); // este mes

    // Otro acreedor con SOLO mensualidad futura: no debe tener selector de este mes.
    $mpw = selPayAccount($user, 'MPW');
    $mpwCredit = selPayCredit($user, $mpw, 'MPW futuro');
    selPayInstallment($user, $mpwCredit, 1, 800, '2026-09-01', '2026-09-27'); // mes futuro

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Seleccionar y pagar', false)
        ->assertSee('Pagar selección de este mes · NU', false)
        ->assertSee('Auto-seleccionar', false)
        ->assertSee('data-pay-select-auto', false)
        ->assertSee('Vas seleccionando:', false)
        ->assertSee('Te quedas con:', false)
        ->assertSee('data-available-cash="5000.00"', false)
        ->assertSee('installment_ids[]', false)
        ->assertSee('NU televisor', false)
        // El acreedor con solo mensualidad futura no tiene modal de selección de este mes.
        ->assertDontSee('Pagar selección de este mes · MPW', false);
});
