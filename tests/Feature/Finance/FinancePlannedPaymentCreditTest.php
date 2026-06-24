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
