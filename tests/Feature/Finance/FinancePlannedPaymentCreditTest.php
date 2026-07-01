<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks a planned payment as covered by credit without creating a duplicate expense', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $card = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)
        ->where('type', 'expense')
        ->where('keywords', 'like', '%tarjeta%')
        ->firstOrFail();

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'name' => 'OPEN AI - GPT - Carlos',
        'amount' => 399,
        'status' => 'pending',
        'category_id' => $category->id,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-24',
        'name' => 'OPEN AI - GPT - Carlos',
        'total_amount' => 399,
        'months' => 1,
        'first_due_month' => '2026-07-01',
        'due_day' => 10,
        'account_id' => $card->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-10',
        'installment_number' => 1,
        'amount' => 399,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('finance.planned.credit-paid', $payment), [
            'paid_on' => '2026-06-24',
            'account_id' => $card->id,
            'credit_purchase_id' => $credit->id,
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-06']))
        ->assertSessionHas('success', 'Pago marcado como cubierto con credito. La deuda queda en la seccion de creditos.');

    $payment->refresh();

    expect($payment->status)->toBe('paid');
    expect((float) $payment->paid_amount)->toBe(399.0);
    expect($payment->paid_on->toDateString())->toBe('2026-06-24');
    expect($payment->is_credit)->toBeTrue();
    expect($payment->account_id)->toBe($card->id);
    expect($payment->credit_purchase_id)->toBe($credit->id);
    expect($payment->movement_id)->toBeNull();
    expect(Movement::where('user_id', $user->id)->where('source', 'planned_payment')->count())->toBe(0);

    $june = app(FinanceSummaryService::class)->monthSummary($user, '2026-06');
    expect($june['obligation_totals']['pending'])->toBe(0.0);
    expect($june['obligation_totals']['paid'])->toBe(399.0);
    expect($june['next_payments']->pluck('name')->all())->not->toContain('OPEN AI - GPT - Carlos');

    $july = app(FinanceSummaryService::class)->monthSummary($user, '2026-07');
    expect($july['obligation_totals']['pending'])->toBe(399.0);
    expect($july['obligation_totals']['credits'])->toBe(399.0);
    expect($july['next_payments']->firstWhere('source', 'credit')['credit_name'])->toBe('OPEN AI - GPT - Carlos');

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('OPEN AI - GPT - Carlos')
        ->assertSee('Pagado con credito')
        ->assertSee('Tarjeta: NU')
        ->assertSee('Credito: OPEN AI - GPT - Carlos');
});

it('shows this months credit installments grouped by creditor on planned flow', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);
    app(FinanceCatalogService::class)->ensureForUser($other);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $mpw = Account::where('user_id', $user->id)->where('name', 'MPW')->firstOrFail();
    $didi = Account::where('user_id', $user->id)->where('name', 'DIDI')->firstOrFail();
    $otherNu = Account::where('user_id', $other->id)->where('name', 'NU')->firstOrFail();

    $makeCredit = function (User $owner, Account $account, string $name, float $total): CreditPurchase {
        return CreditPurchase::create([
            'user_id' => $owner->id,
            'purchase_date' => '2026-07-01',
            'name' => $name,
            'total_amount' => $total,
            'months' => 3,
            'first_due_month' => '2026-07-01',
            'due_day' => 23,
            'account_id' => $account->id,
            'status' => 'active',
        ]);
    };

    $nuCredit = $makeCredit($user, $nu, 'Compra NU', 900);
    $mpwCredit = $makeCredit($user, $mpw, 'Compra MPW', 600);
    $didiCredit = $makeCredit($user, $didi, 'Compra DIDI', 300);
    $otherCredit = $makeCredit($other, $otherNu, 'Compra ajena', 999);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $nuCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-23',
        'installment_number' => 1,
        'amount' => 300,
        'paid_amount' => 50,
        'status' => 'pending',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $nuCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-24',
        'installment_number' => 2,
        'amount' => 300,
        'paid_amount' => 300,
        'status' => 'paid',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $mpwCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-25',
        'installment_number' => 1,
        'amount' => 200,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $didiCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-26',
        'installment_number' => 1,
        'amount' => 123.45,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $didiCredit->id,
        'period_month' => '2026-08-01',
        'due_date' => '2026-08-26',
        'installment_number' => 2,
        'amount' => 900,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    CreditInstallment::create([
        'user_id' => $other->id,
        'credit_purchase_id' => $otherCredit->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-23',
        'installment_number' => 1,
        'amount' => 999,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.planned.index', ['month' => '2026-07']))
        ->assertOk()
        ->assertSee('A quien se le debe este mes')
        ->assertSee('Le debes a')
        ->assertSee('Total creditos:')
        ->assertSee('$573.45')
        ->assertSee('$250.00')
        ->assertSee('$200.00')
        ->assertSee('$123.45')
        ->assertDontSee('$900.00')
        ->assertDontSee('$999.00');
});
