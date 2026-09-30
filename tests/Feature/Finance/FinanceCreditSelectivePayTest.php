<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\CreditFreePaymentService;
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

it('offers current and next month installments without exposing later months in the selector', function () {
    $user = User::factory()->create();
    Account::create([
        'user_id' => $user->id, 'name' => 'Efectivo', 'type' => 'cash',
        'opening_balance' => 5000, 'is_active' => true,
    ]);
    $nu = selPayAccount($user, 'NU');
    $nuCredit = selPayCredit($user, $nu, 'NU televisor');
    $current = selPayInstallment($user, $nuCredit, 1, 500, '2026-07-01', '2026-07-25');
    $next = selPayInstallment($user, $nuCredit, 2, 500, '2026-08-01', '2026-08-25');
    selPayInstallment($user, $nuCredit, 3, 500, '2026-09-01', '2026-09-25');

    // Un acreedor cuyo primer pago es dentro de dos meses queda fuera del selector.
    $mpw = selPayAccount($user, 'MPW');
    $mpwCredit = selPayCredit($user, $mpw, 'MPW futuro');
    selPayInstallment($user, $mpwCredit, 1, 800, '2026-09-01', '2026-09-27'); // mes futuro

    $response = $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Seleccionar y pagar', false)
        ->assertSee('Seleccionar y pagar · NU', false)
        ->assertSee('Este mes · julio 2026', false)
        ->assertSee('Adelantar próximo mes · agosto 2026', false)
        ->assertSee('data-pay-select-month', false)
        ->assertSee('Auto-seleccionar', false)
        ->assertSee('data-pay-select-auto', false)
        ->assertSee('Vas seleccionando:', false)
        ->assertSee('Te quedas con:', false)
        ->assertSee('data-available-cash="5000.00"', false)
        ->assertSee('installment_ids[]', false)
        ->assertSee('NU televisor', false)
        ->assertDontSee('Seleccionar y pagar · MPW', false);

    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'NU');
    expect(array_column($creditor['pending_installments'], 'id'))->toBe([$current->id, $next->id]);
});

it('defaults to the next month when there is nothing left to pay this month', function (string $today, string $period, string $label) {
    Carbon::setTestNow($today.' 09:00:00');
    $user = User::factory()->create();
    $nu = selPayAccount($user);
    $credit = selPayCredit($user, $nu);
    $installment = selPayInstallment($user, $credit, 1, 500, $period, Carbon::parse($period)->day(25)->toDateString());

    $response = $this->actingAs($user)->get(route('finance.credits.index'))->assertOk()
        ->assertSee('Seleccionar y pagar · NU', false)
        ->assertSee('value="'.Carbon::parse($period)->format('Y-m').'" selected', false)
        ->assertSee('Adelantar próximo mes · '.$label, false);

    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'NU');
    expect($creditor['current_due'])->toBe(0.0)
        ->and($creditor['next_due'])->toBe(500.0)
        ->and(array_column($creditor['pending_installments'], 'id'))->toBe([$installment->id]);
})->with([
    ['2026-09-30', '2026-10-01', 'octubre 2026'],
    ['2026-12-31', '2027-01-01', 'enero 2027'],
    ['2027-01-31', '2027-02-01', 'febrero 2027'],
]);

it('frees card limit with an advance dated today and settles the linked plan only once', function () {
    $user = User::factory()->create();
    $nu = selPayAccount($user);
    $nu->update(['credit_limit' => 16000]);
    $cash = Account::create([
        'user_id' => $user->id, 'name' => 'Efectivo', 'type' => 'cash',
        'opening_balance' => 5000, 'is_active' => true,
    ]);
    $credit = selPayCredit($user, $nu);
    $credit->update(['total_amount' => 15055.10, 'months' => 2, 'first_due_month' => '2026-08-01']);
    $next = selPayInstallment($user, $credit, 1, 1000, '2026-08-01', '2026-08-25');
    $later = selPayInstallment($user, $credit, 2, 14055.10, '2026-09-01', '2026-09-25');
    $planned = PlannedPayment::create([
        'user_id' => $user->id, 'period_month' => '2026-08-01', 'due_date' => '2026-08-25',
        'name' => 'Pago NU', 'amount' => 1000, 'paid_amount' => 0, 'status' => 'pending',
        'account_id' => $cash->id, 'credit_installment_id' => $next->id,
    ]);

    $response = $this->actingAs($user)->get(route('finance.credits.index'))->assertOk()
        ->assertSee('data-credit-available="944.90"', false)
        ->assertSee('Disponible en tarjeta después del pago:', false);
    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'NU');
    expect($creditor['available'])->toBe(944.9);

    $payload = ['installment_ids' => [$next->id], 'payment_account_id' => $cash->id];
    $this->post(route('finance.credits.installments.pay-selected'), $payload)->assertSessionHas('success');

    $movement = Movement::where('user_id', $user->id)->sole();
    expect($next->fresh()->paid_on->toDateString())->toBe('2026-07-10')
        ->and($next->fresh()->period_month->toDateString())->toBe('2026-08-01')
        ->and($later->fresh()->status)->toBe('pending')
        ->and((float) $movement->amount)->toBe(1000.0)
        ->and($movement->happened_on->toDateString())->toBe('2026-07-10')
        ->and($movement->account_id)->toBe($cash->id)
        ->and($planned->fresh()->status)->toBe('paid')
        ->and($planned->fresh()->movement_id)->toBe($movement->id)
        ->and($next->fresh()->movement_id)->toBe($movement->id);

    $response = $this->get(route('finance.credits.index'))->assertOk();
    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'NU');
    expect($creditor['pending'])->toBe(14055.1)
        ->and($creditor['available'])->toBe(1944.9)
        ->and($creditor['next_due'])->toBe(0.0)
        ->and($creditor['paid_this_month'])->toBe(1000.0)
        ->and($creditor['pending_installments'])->toBe([]);

    $this->post(route('finance.credits.installments.pay-selected'), $payload)->assertSessionHas('warning');
    expect(Movement::where('user_id', $user->id)->count())->toBe(1);
});

it('offers and pays only the effective remainder of next month after a free payment', function () {
    $user = User::factory()->create();
    $nu = selPayAccount($user);
    $credit = selPayCredit($user, $nu);
    $next = selPayInstallment($user, $credit, 1, 500, '2026-08-01', '2026-08-25');
    $later = selPayInstallment($user, $credit, 2, 1000, '2026-09-01', '2026-09-25');
    app(CreditFreePaymentService::class)->createFreePayment($credit, today(), 200);

    $response = $this->actingAs($user)->get(route('finance.credits.index'))->assertOk();
    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'NU');
    expect($creditor['pending_installments'][0]['amount'])->toBe(300.0);

    $this->post(route('finance.credits.installments.pay-selected'), ['installment_ids' => [$next->id]])
        ->assertSessionHas('success');
    expect((float) $next->fresh()->paid_amount)->toBe(300.0)
        ->and($later->fresh()->status)->toBe('pending')
        ->and((float) Movement::where('source', 'credit_installment')->sole()->amount)->toBe(300.0);

    $response = $this->get(route('finance.credits.index'))->assertOk();
    $creditor = collect($response->viewData('creditorSummaries'))->firstWhere('name', 'NU');
    expect($creditor['pending'])->toBe(1000.0)
        ->and($creditor['next_due'])->toBe(0.0);
});
