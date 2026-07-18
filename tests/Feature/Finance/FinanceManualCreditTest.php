<?php

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function manualCreditUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('creates a protected manual credit with exact dates and amounts', function () {
    $user = manualCreditUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update([
        'statement_day' => 15,
        'payment_day' => 27,
    ]);

    $schedule = [
        ['due_date' => '2026-09-25', 'amount' => '1471.49'],
        ['due_date' => '2026-10-26', 'amount' => '1471.49'],
        ['due_date' => '2026-11-25', 'amount' => '1471.49'],
        ['due_date' => '2026-12-28', 'amount' => '1471.50'],
        ['due_date' => '2027-01-25', 'amount' => '1471.48'],
        ['due_date' => '2027-02-25', 'amount' => '1471.50'],
        ['due_date' => '2027-03-25', 'amount' => '1471.49'],
        ['due_date' => '2027-04-25', 'amount' => '1471.50'],
        ['due_date' => '2027-05-25', 'amount' => '1471.48'],
    ];

    $this->actingAs($user)
        ->post(route('finance.credits.manual.store'), [
            'manual' => [
                'purchase_date' => '2026-07-17',
                'name' => 'Disposición de efectivo NU',
                'account_id' => $nu->id,
                'notes' => 'Calendario indicado por NU',
                'installments' => $schedule,
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $credit = CreditPurchase::where('user_id', $user->id)
        ->where('name', 'Disposición de efectivo NU')
        ->firstOrFail();
    $installments = $credit->installments()->orderBy('installment_number')->get();

    expect($credit->is_manual_schedule)->toBeTrue()
        ->and($credit->account_id)->toBe($nu->id)
        ->and((float) $credit->total_amount)->toBe(13243.42)
        ->and($credit->months)->toBe(9)
        ->and($credit->first_due_month->format('Y-m'))->toBe('2026-09')
        ->and($credit->due_day)->toBeNull()
        ->and($installments)->toHaveCount(9)
        ->and($installments->pluck('due_date')->map->format('Y-m-d')->all())->toBe(array_column($schedule, 'due_date'))
        ->and($installments->map(fn (CreditInstallment $installment) => (float) $installment->amount)->all())
        ->toBe(array_map('floatval', array_column($schedule, 'amount')));
});

it('sorts manual installments chronologically and derives their period months', function () {
    $user = manualCreditUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.credits.manual.store'), [
            'manual' => [
                'purchase_date' => '2026-07-17',
                'name' => 'Calendario desordenado',
                'account_id' => $nu->id,
                'installments' => [
                    ['due_date' => '2026-10-26', 'amount' => '200.00'],
                    ['due_date' => '2026-09-25', 'amount' => '100.00'],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $credit = CreditPurchase::where('name', 'Calendario desordenado')->firstOrFail();
    $installments = $credit->installments()->orderBy('installment_number')->get();

    expect($installments->pluck('due_date')->map->format('Y-m-d')->all())
        ->toBe(['2026-09-25', '2026-10-26'])
        ->and($installments->pluck('period_month')->map->format('Y-m')->all())
        ->toBe(['2026-09', '2026-10']);
});

it('does not recalculate dates of manual credits from the card cycle', function () {
    $user = manualCreditUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $nu->update([
        'statement_day' => 15,
        'payment_day' => 27,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-07-17',
        'name' => 'Manual protegido',
        'total_amount' => 200,
        'months' => 2,
        'first_due_month' => '2026-09-01',
        'due_day' => null,
        'is_manual_schedule' => true,
        'account_id' => $nu->id,
        'status' => 'active',
    ]);

    foreach ([
        ['period_month' => '2026-09-01', 'due_date' => '2026-09-25'],
        ['period_month' => '2026-10-01', 'due_date' => '2026-10-26'],
    ] as $index => $dates) {
        CreditInstallment::create($dates + [
            'user_id' => $user->id,
            'credit_purchase_id' => $credit->id,
            'installment_number' => $index + 1,
            'amount' => 100,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);
    }

    $this->actingAs($user)
        ->post(route('finance.credits.recalculate-dates'))
        ->assertRedirect();

    expect($credit->fresh()->first_due_month->format('Y-m'))->toBe('2026-09')
        ->and($credit->fresh()->due_day)->toBeNull()
        ->and($credit->installments()->orderBy('installment_number')->get()
            ->pluck('due_date')->map->format('Y-m-d')->all())
        ->toBe(['2026-09-25', '2026-10-26']);
});

it('updates manual credit metadata without regenerating its schedule', function () {
    $user = manualCreditUser();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $bbva = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-07-17',
        'name' => 'Manual original',
        'total_amount' => 300,
        'months' => 2,
        'first_due_month' => '2026-09-01',
        'due_day' => null,
        'is_manual_schedule' => true,
        'account_id' => $nu->id,
        'status' => 'active',
    ]);

    foreach ([
        ['due_date' => '2026-09-25', 'amount' => 100],
        ['due_date' => '2026-10-26', 'amount' => 200],
    ] as $index => $row) {
        CreditInstallment::create([
            'user_id' => $user->id,
            'credit_purchase_id' => $credit->id,
            'period_month' => substr($row['due_date'], 0, 7).'-01',
            'due_date' => $row['due_date'],
            'installment_number' => $index + 1,
            'amount' => $row['amount'],
            'paid_amount' => 0,
            'status' => 'pending',
        ]);
    }

    $this->actingAs($user)
        ->put(route('finance.credits.update', $credit), [
            'purchase_date' => '2026-07-18',
            'name' => 'Manual actualizado',
            'account_id' => $bbva->id,
            'notes' => 'Solo cambian los datos generales',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $credit->refresh();
    $installments = $credit->installments()->orderBy('installment_number')->get();

    expect($credit->name)->toBe('Manual actualizado')
        ->and($credit->account_id)->toBe($bbva->id)
        ->and($credit->is_manual_schedule)->toBeTrue()
        ->and((float) $credit->total_amount)->toBe(300.0)
        ->and($installments->pluck('due_date')->map->format('Y-m-d')->all())
        ->toBe(['2026-09-25', '2026-10-26'])
        ->and($installments->map(fn (CreditInstallment $installment) => (float) $installment->amount)->all())
        ->toBe([100.0, 200.0]);
});

it('renders the collapsible manual form after the credit list', function () {
    $user = manualCreditUser();

    CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-07-17',
        'name' => 'Crédito existente',
        'total_amount' => 100,
        'months' => 1,
        'first_due_month' => '2026-08-01',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('Carga manual de crédito')
        ->assertSee('data-manual-credit-form', false)
        ->assertSee(route('finance.credits.manual.store'), false);

    expect(strpos($response->getContent(), 'Carga manual de crédito'))
        ->toBeGreaterThan(strpos($response->getContent(), 'Crédito existente'));
});

it('rejects an account that belongs to another user', function () {
    $user = manualCreditUser();
    $other = manualCreditUser();
    $otherNu = Account::where('user_id', $other->id)->where('name', 'NU')->firstOrFail();

    $this->actingAs($user)
        ->from(route('finance.credits.index'))
        ->post(route('finance.credits.manual.store'), [
            'manual' => [
                'purchase_date' => '2026-07-17',
                'name' => 'Cuenta ajena',
                'account_id' => $otherNu->id,
                'installments' => [
                    ['due_date' => '2026-09-25', 'amount' => '100.00'],
                ],
            ],
        ])
        ->assertRedirect(route('finance.credits.index'))
        ->assertSessionHasErrors('manual.account_id');

    expect(CreditPurchase::where('user_id', $user->id)->where('name', 'Cuenta ajena')->exists())->toBeFalse();
});
