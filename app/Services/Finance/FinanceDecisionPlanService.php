<?php

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceDecisionPlanService
{
    private const HISTORY_DAYS = 60;

    private const DEFAULT_BASIC_DAILY_SPEND = 150.0;

    private const EMERGENCY_FLOOR = 500.0;

    private const BASIC_KEYWORDS = [
        'comida',
        'tienda',
        'despensa',
        'supermercado',
        'gasolina',
        'transporte',
        'caseta',
        'casetas',
        'farmacia',
    ];

    private const DEBT_KEYWORDS = [
        'deuda',
        'credito',
        'creditos',
        'tarjeta',
        'prestamo',
        'mensualidad',
    ];

    private const DEFAULT_CATEGORY_BUCKETS = [
        ['name' => 'Comida / tienda / despensa', 'weight_percent' => 45.0],
        ['name' => 'Gasolina / transporte', 'weight_percent' => 25.0],
        ['name' => 'Casetas', 'weight_percent' => 10.0],
        ['name' => 'Libre / otros', 'weight_percent' => 15.0],
        ['name' => 'Imprevistos', 'weight_percent' => 5.0],
    ];

    public function __construct(
        private readonly FinanceProjectionService $projectionService,
        private readonly FinancePaymentRecommendationService $recommendationService,
        private readonly FinanceSurvivalBudgetService $survivalBudgetService,
        private readonly FinanceRentalContractIncomeService $rentalIncomes
    ) {}

    public function build(User $user, int $horizonDays = 30): array
    {
        $horizonDays = $this->validHorizon($horizonDays);
        $projection = $this->projectionService->project($user, $horizonDays);
        $paymentRecommendations = $this->recommendationService->recommend($user, $horizonDays, $projection);
        $survivalBudget = $this->survivalBudgetService->build($user, $horizonDays);

        $start = today()->startOfDay();
        $horizonEnd = Carbon::parse($projection['meta']['end_date'])->startOfDay();
        $incomes = $this->expectedIncomesWithinHorizon($user, $start, $horizonEnd);
        $nextIncome = $incomes->first();
        $followingIncome = $incomes->skip(1)->first();
        $warnings = [];

        $buffer = $this->recommendedBuffer($user, $projection, $start);
        $events = $this->eventsFromProjection($user, $projection, $start, $horizonEnd);

        if ($nextIncome) {
            $currentEnd = $this->endBeforeIncome($start, $nextIncome['date']);
        } else {
            $currentEnd = $this->earliest($start->copy()->addDays(14), $horizonEnd);
            $warnings[] = 'no_next_income_within_horizon';
        }

        $currentWindow = $this->window(
            $projection,
            $events,
            $start,
            $currentEnd,
            $horizonEnd,
            $buffer['buffer_used']
        );
        $currentWindow = array_merge($currentWindow, [
            'next_income_date' => $nextIncome ? $nextIncome['date']->toDateString() : null,
            'next_income_name' => $nextIncome['name'] ?? null,
            'next_income_amount' => $nextIncome['amount'] ?? 0.0,
            'has_next_income' => (bool) $nextIncome,
        ]);

        $afterNextIncomeWindow = null;
        if ($nextIncome) {
            $afterStart = $nextIncome['date']->copy();
            $afterEnd = $followingIncome
                ? $this->endBeforeIncome($afterStart, $followingIncome['date'])
                : $horizonEnd->copy();

            if ($afterStart->lte($horizonEnd)) {
                $afterNextIncomeWindow = array_merge(
                    $this->window($projection, $events, $afterStart, $afterEnd, $horizonEnd, $buffer['buffer_used']),
                    [
                        'next_income_date' => $followingIncome ? $followingIncome['date']->toDateString() : null,
                        'next_income_name' => $followingIncome['name'] ?? null,
                        'next_income_amount' => $followingIncome['amount'] ?? 0.0,
                        'has_next_income' => (bool) $followingIncome,
                    ]
                );
            }
        }

        $moneyPlan = $this->moneyPlan(
            $projection,
            $events,
            $currentWindow,
            $buffer,
            $start,
            $horizonEnd,
            $nextIncome
        );
        $savingsGuidance = $this->savingsGuidance($moneyPlan, $buffer, $currentWindow);
        $creditPayoffStrategy = $this->creditPayoffStrategy($user, $moneyPlan, $currentWindow, $nextIncome, $start, $horizonEnd);

        $actions = $this->actions($events, $moneyPlan, $savingsGuidance, $currentWindow, $start, $nextIncome);
        $categoryBudget = $this->categoryBudget($user, $survivalBudget, $start, $currentEnd, $moneyPlan['living_money'], $currentWindow['days_count']);
        $timelineMessages = $this->timelineMessages($moneyPlan, $buffer, $savingsGuidance, $currentWindow, $nextIncome, $actions);
        $status = $this->headlineStatus($moneyPlan, $currentWindow, $warnings);

        return [
            'headline' => [
                'message' => $this->headlineMessage($moneyPlan['starting_balance'], $currentEnd, $nextIncome),
                'status' => $status,
            ],
            'buffer' => $buffer,
            'current_window' => $currentWindow,
            'after_next_income_window' => $afterNextIncomeWindow,
            'windows' => array_values(array_filter([$currentWindow, $afterNextIncomeWindow])),
            'money_plan' => $moneyPlan,
            'savings_guidance' => $savingsGuidance,
            'credit_payoff_strategy' => $creditPayoffStrategy,
            'actions' => $actions,
            'category_budget' => $categoryBudget,
            'timeline_messages' => $timelineMessages,
            'warnings' => $warnings,
            'meta' => [
                'horizon_days' => $horizonDays,
                'start_date' => $start->toDateString(),
                'end_date' => $horizonEnd->toDateString(),
                'payment_recommendation_summary' => [
                    'safe_today' => $this->money((float) ($paymentRecommendations['available']['safe_today'] ?? 0)),
                    'projected_today' => $this->money((float) ($paymentRecommendations['available']['projected_today'] ?? 0)),
                ],
            ],
        ];
    }

    private function validHorizon(int $horizonDays): int
    {
        return in_array($horizonDays, FinanceProjectionService::HORIZONS, true) ? $horizonDays : 30;
    }

    private function expectedIncomesWithinHorizon(User $user, Carbon $start, Carbon $horizonEnd): Collection
    {
        $manual = ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $horizonEnd->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->map(function (ExpectedIncome $income) {
                $residual = $this->money(max(0, (float) $income->amount - (float) $income->received_amount));

                if ($residual <= 0) {
                    return null;
                }

                return [
                    'id' => $income->id,
                    'date' => $income->due_date->copy()->startOfDay(),
                    'name' => $income->name,
                    'amount' => $residual,
                ];
            })
            ->filter();

        // Rentas por contrato (no viven en finance_expected_incomes) dentro del horizonte.
        $rental = collect($this->rentalIncomes->eventsBetween($user, $start, $horizonEnd))
            ->filter(fn (array $event) => $event['amount'] > 0 && $event['date']->betweenIncluded($start, $horizonEnd))
            ->map(fn (array $event) => [
                'id' => null,
                'date' => $event['date']->copy()->startOfDay(),
                'name' => $event['name'],
                'amount' => $this->money((float) $event['amount']),
            ]);

        return $manual
            ->concat($rental)
            ->sortBy(fn (array $item) => $item['date']->timestamp)
            ->values();
    }

    private function recommendedBuffer(User $user, array $projection, Carbon $start): array
    {
        $settings = PlannerSetting::where('user_id', $user->id)->first();
        $historicalBasicDailySpend = $this->historicalBasicDailySpend($user, $start);
        $nextSevenDaysObligations = $this->nextSevenDaysObligations($projection, $start);
        $obligationGuard = $this->money($nextSevenDaysObligations * 0.10);
        $threeDayBasicBuffer = $this->money($historicalBasicDailySpend * 3);
        $sevenDayBasicBuffer = $this->money($historicalBasicDailySpend * 7);
        $recommendedMin = $this->money(max(self::EMERGENCY_FLOOR, $threeDayBasicBuffer, $obligationGuard));
        $recommendedIdeal = $this->money(max($recommendedMin, $sevenDayBasicBuffer));

        return [
            'manual_buffer_reference' => $settings ? $this->money((float) $settings->minimum_buffer) : null,
            'historical_basic_daily_spend' => $historicalBasicDailySpend,
            'next_7_days_obligations' => $nextSevenDaysObligations,
            'recommended_min_buffer' => $recommendedMin,
            'recommended_ideal_buffer' => $recommendedIdeal,
            'buffer_used' => $recommendedMin,
            'buffer_reason' => 'Calculado con gasto basico historico, piso de emergencia y obligaciones proximas.',
        ];
    }

    private function historicalBasicDailySpend(User $user, Carbon $start): float
    {
        $historyStart = $start->copy()->subDays(self::HISTORY_DAYS);
        $historyEnd = $start->copy()->subDay();

        $total = Movement::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereNotNull('category_id')
            ->whereDate('happened_on', '>=', $historyStart->toDateString())
            ->whereDate('happened_on', '<=', $historyEnd->toDateString())
            ->where(function ($query) {
                $query->where('is_san_juan', false)->orWhereNull('is_san_juan');
            })
            ->where(function ($query) {
                $query->where('is_rent', false)->orWhereNull('is_rent');
            })
            ->get()
            ->filter(fn (Movement $movement) => $this->isBasicCategory($movement->category))
            ->sum(fn (Movement $movement) => (float) $movement->amount);

        if ($total <= 0) {
            return self::DEFAULT_BASIC_DAILY_SPEND;
        }

        return $this->money($total / self::HISTORY_DAYS);
    }

    private function nextSevenDaysObligations(array $projection, Carbon $start): float
    {
        $end = $start->copy()->addDays(6);
        $total = 0.0;

        foreach ($projection['days'] ?? [] as $day) {
            $date = Carbon::parse($day['date'])->startOfDay();

            if ($date->lt($start) || $date->gt($end)) {
                continue;
            }

            $total = $this->money($total + (float) ($day['payment_total'] ?? 0) + (float) ($day['installment_total'] ?? 0));
        }

        return $total;
    }

    private function eventsFromProjection(User $user, array $projection, Carbon $start, Carbon $horizonEnd): array
    {
        $events = [];

        foreach ($projection['days'] ?? [] as $day) {
            $date = Carbon::parse($day['date'])->startOfDay();

            if ($date->lt($start) || $date->gt($horizonEnd)) {
                continue;
            }

            foreach ($day['payments'] ?? [] as $payment) {
                $events[] = [
                    'type' => 'payment',
                    'id' => $payment['id'] ?? null,
                    'name' => (string) ($payment['name'] ?? 'Pago planeado'),
                    'amount' => $this->money((float) ($payment['amount'] ?? 0)),
                    'date' => $date->toDateString(),
                    'date_carbon' => $date->copy(),
                    'is_overdue' => (bool) ($payment['is_overdue'] ?? false),
                    'has_due_date' => (bool) ($payment['has_due_date'] ?? true),
                    'is_automatic_charge' => (bool) ($payment['is_automatic_charge'] ?? false),
                    'is_forced_charge_window' => (bool) ($payment['is_forced_charge_window'] ?? false),
                    'charge_window_start' => $payment['charge_window_start'] ?? null,
                    'charge_window_end' => $payment['charge_window_end'] ?? null,
                    'effective_due_date' => $payment['effective_due_date'] ?? $date->toDateString(),
                    'original_due_date' => $payment['original_due_date'] ?? null,
                    'status' => null,
                ];
            }

            foreach ($day['installments'] ?? [] as $installment) {
                $label = $installment['installment_label'] ?? null;
                $name = (string) ($installment['credit_name'] ?? 'Credito');

                $events[] = [
                    'type' => 'installment',
                    'id' => $installment['id'] ?? null,
                    'name' => $label ? $name.' ('.$label.')' : $name,
                    'amount' => $this->money((float) ($installment['amount'] ?? 0)),
                    'date' => $date->toDateString(),
                    'date_carbon' => $date->copy(),
                    'is_overdue' => (bool) ($installment['is_overdue'] ?? false),
                    'has_due_date' => true,
                    'original_due_date' => null,
                    'status' => null,
                ];
            }
        }

        return $this->annotateEventsFromModels($user, $events);
    }

    private function annotateEventsFromModels(User $user, array $events): array
    {
        $paymentIds = collect($events)->where('type', 'payment')->pluck('id')->filter()->values();
        $installmentIds = collect($events)->where('type', 'installment')->pluck('id')->filter()->values();
        $payments = $paymentIds->isEmpty()
            ? collect()
            : PlannedPayment::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $paymentIds)
                ->get()
                ->keyBy('id');
        $installments = $installmentIds->isEmpty()
            ? collect()
            : CreditInstallment::query()
                ->with('creditPurchase')
                ->where('user_id', $user->id)
                ->whereIn('id', $installmentIds)
                ->get()
                ->keyBy('id');

        return array_map(function (array $event) use ($payments, $installments) {
            if ($event['type'] === 'payment' && $event['id']) {
                $payment = $payments->get($event['id']);
                $event['original_due_date'] = $payment?->due_date?->toDateString();
                $event['status'] = $payment?->status;
                $event['is_automatic_charge'] = (bool) $payment?->is_automatic_charge;
                $event['is_forced_charge_window'] = (bool) $payment?->is_forced_charge_window;
                $event['charge_window_start'] = $payment?->chargeWindowStart()?->toDateString();
                $event['charge_window_end'] = $payment?->chargeWindowEnd()?->toDateString();
                $event['effective_due_date'] = $event['effective_due_date'] ?? $event['date'];
            }

            if ($event['type'] === 'installment' && $event['id']) {
                $installment = $installments->get($event['id']);
                $event['original_due_date'] = $installment?->due_date?->toDateString();
                $event['status'] = $installment?->status;
                $event['credit_purchase_id'] = $installment?->credit_purchase_id;
                $event['credit_name'] = $installment?->creditPurchase?->name;
            }

            return $event;
        }, $events);
    }

    private function window(
        array $projection,
        array $events,
        Carbon $start,
        Carbon $end,
        Carbon $horizonEnd,
        float $bufferUsed
    ): array {
        $days = $this->projectionDaysBetween($projection, $start, $end);
        $daysCount = max(1, ((int) $start->diffInDays($end)) + 1);
        $startingBalance = $this->windowStartingBalance($projection, $start);
        $incomeInside = $this->money(array_sum(array_column($days, 'income_total')));
        $paymentsInside = $this->money(array_sum(array_column($days, 'payment_total')));
        $installmentsInside = $this->money(array_sum(array_column($days, 'installment_total')));
        $obligationsInside = $this->money($paymentsInside + $installmentsInside);
        $overdueObligations = $this->money(collect($events)
            ->filter(fn (array $event) => $event['date_carbon']->betweenIncluded($start, $end) && $event['is_overdue'])
            ->sum('amount'));
        $reserveForFuture = $this->money(collect($events)
            ->filter(fn (array $event) => $event['date_carbon']->gt($end) && $event['date_carbon']->lte($horizonEnd))
            ->sum('amount'));

        $availableRaw = $this->money(
            $startingBalance
            + $incomeInside
            - $paymentsInside
            - $installmentsInside
            - $bufferUsed
            - $reserveForFuture
        );
        $availableToLive = $this->money(max(0, $availableRaw));
        $shortfall = $this->money(max(0, -$availableRaw));

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_count' => $daysCount,
            'starting_balance' => $startingBalance,
            'income_inside_window' => $incomeInside,
            'payments_inside_window' => $paymentsInside,
            'installments_inside_window' => $installmentsInside,
            'obligations_inside_window' => $obligationsInside,
            'overdue_obligations' => $overdueObligations,
            'buffer_used' => $bufferUsed,
            'reserve_for_future_obligations' => $reserveForFuture,
            'available_to_live_raw' => $availableRaw,
            'available_to_live' => $availableToLive,
            'daily_living_allowance' => $this->money($availableToLive / $daysCount),
            'shortfall' => $shortfall,
            'risk_level' => $this->riskLevel($days, $bufferUsed, $availableRaw),
        ];
    }

    private function projectionDaysBetween(array $projection, Carbon $start, Carbon $end): array
    {
        return array_values(array_filter($projection['days'] ?? [], function (array $day) use ($start, $end) {
            $date = Carbon::parse($day['date'])->startOfDay();

            return $date->betweenIncluded($start, $end);
        }));
    }

    private function windowStartingBalance(array $projection, Carbon $start): float
    {
        $projectionStart = Carbon::parse($projection['meta']['start_date'])->startOfDay();

        if ($start->isSameDay($projectionStart)) {
            return $this->money((float) ($projection['meta']['starting_balance'] ?? 0));
        }

        foreach ($projection['days'] ?? [] as $day) {
            if (($day['date'] ?? null) === $start->toDateString()) {
                return $this->money((float) ($day['opening_projected'] ?? 0));
            }
        }

        return 0.0;
    }

    private function moneyPlan(
        array $projection,
        array $events,
        array $currentWindow,
        array $buffer,
        Carbon $start,
        Carbon $horizonEnd,
        ?array $nextIncome
    ): array {
        $nextIncomeDate = $nextIncome['date'] ?? null;
        $currentEnd = Carbon::parse($currentWindow['end_date'])->startOfDay();
        $urgentPayments = collect($events)->filter(fn (array $event) => $event['type'] === 'payment' && $this->isUrgent($event, $start));
        $beforeIncomePayments = collect($events)->filter(function (array $event) use ($start, $currentEnd, $nextIncomeDate) {
            if ($event['type'] !== 'payment' || $this->isUrgent($event, $start)) {
                return false;
            }

            if ($nextIncomeDate) {
                return $event['date_carbon']->lt($nextIncomeDate);
            }

            return $event['date_carbon']->lte($currentEnd);
        });
        $futurePayments = collect($events)->filter(function (array $event) use ($beforeIncomePayments, $urgentPayments, $horizonEnd) {
            if ($event['type'] !== 'payment') {
                return false;
            }

            if ($urgentPayments->contains(fn (array $payment) => $payment['id'] === $event['id'] && $payment['date'] === $event['date'])) {
                return false;
            }

            if ($beforeIncomePayments->contains(fn (array $payment) => $payment['id'] === $event['id'] && $payment['date'] === $event['date'])) {
                return false;
            }

            return $event['date_carbon']->lte($horizonEnd);
        });
        $creditReserve = collect($events)->where('type', 'installment')->sum('amount');

        $livingNeed = $this->money((float) $buffer['historical_basic_daily_spend'] * (int) $currentWindow['days_count']);
        $idealGap = $this->money(max(0, (float) $buffer['recommended_ideal_buffer'] - (float) $buffer['buffer_used']));
        $savingsPossible = $this->savingsPossible($currentWindow, $livingNeed, $idealGap, (bool) $nextIncome);
        $livingNeedShortfall = $this->money(max(0, $livingNeed - (float) $currentWindow['available_to_live']));
        $projectionCashNeed = $this->projectionCashNeed($projection, (float) $buffer['buffer_used']);
        $shortfall = $this->money(max((float) $currentWindow['shortfall'], $livingNeedShortfall, $projectionCashNeed));

        return [
            'starting_balance' => $this->money((float) ($projection['meta']['starting_balance'] ?? 0)),
            'urgent_payments_reserve' => $this->money($urgentPayments->sum('amount')),
            'before_income_payments_reserve' => $this->money($beforeIncomePayments->sum('amount')),
            'future_payments_reserve' => $this->money($futurePayments->sum('amount')),
            'credit_reserve' => $this->money($creditReserve),
            'buffer_reserve' => $this->money((float) $buffer['buffer_used']),
            'living_money' => $this->money((float) $currentWindow['available_to_live']),
            'daily_living_allowance' => $this->money((float) $currentWindow['daily_living_allowance']),
            'savings_possible' => $savingsPossible,
            'shortfall' => $shortfall,
            'minimum_living_need' => $livingNeed,
            'ideal_buffer_gap' => $idealGap,
        ];
    }

    private function savingsGuidance(array $moneyPlan, array $buffer, array $currentWindow): array
    {
        $normalBuffer = $this->money((float) ($buffer['recommended_min_buffer'] ?? 0));
        $idealBuffer = $this->money((float) ($buffer['recommended_ideal_buffer'] ?? 0));
        $bufferUsed = $this->money((float) ($buffer['buffer_used'] ?? 0));
        $livingMinimum = $this->money((float) ($moneyPlan['minimum_living_need'] ?? 0));
        $cashAfterPaymentsAndLiving = $this->money(
            (float) ($moneyPlan['starting_balance'] ?? 0)
            - (float) ($moneyPlan['urgent_payments_reserve'] ?? 0)
            - (float) ($moneyPlan['before_income_payments_reserve'] ?? 0)
            - (float) ($currentWindow['installments_inside_window'] ?? 0)
            - $livingMinimum
        );
        $currentBufferGap = $this->money(max(0, $normalBuffer - $cashAfterPaymentsAndLiving));
        $idealBufferGap = $this->money(max(0, $idealBuffer - $normalBuffer));
        $freeSavingsAvailable = $this->money(max(0, (float) ($moneyPlan['savings_possible'] ?? 0)));
        $riskLevel = (string) ($currentWindow['risk_level'] ?? 'ok');
        $shouldSave = $freeSavingsAvailable > 0
            && $currentBufferGap <= 0
            && ! in_array($riskLevel, ['high', 'critical'], true);

        if ($currentBufferGap > 0) {
            $message = 'Primero completa tu colchón recomendado normal de '.$this->formatMoney($normalBuffer).' antes de ahorrar.';
            $freeSavingsAvailable = 0.0;
            $shouldSave = false;
        } elseif (! $shouldSave && $idealBufferGap > 0 && ! in_array($riskLevel, ['high', 'critical'], true)) {
            $message = 'Ya cubres tu colchón normal. El siguiente objetivo es acercarte al colchón ideal de '.$this->formatMoney($idealBuffer).'.';
        } elseif ($shouldSave) {
            $message = 'Puedes ahorrar '.$this->formatMoney($freeSavingsAvailable).' sin afectar pagos, vida diaria ni colchón.';
        } else {
            $message = 'No se recomienda ahorrar todavía; primero confirma pagos, vida diaria y colchón recomendado.';
        }

        return [
            'recommended_normal_buffer' => $normalBuffer,
            'recommended_ideal_buffer' => $idealBuffer,
            'buffer_used' => $bufferUsed,
            'current_buffer_gap' => $currentBufferGap,
            'ideal_buffer_gap' => $idealBufferGap,
            'free_savings_available' => min($freeSavingsAvailable, $this->money((float) ($moneyPlan['savings_possible'] ?? 0))),
            'should_save' => $shouldSave,
            'message' => $message,
        ];
    }

    private function creditPayoffStrategy(
        User $user,
        array $moneyPlan,
        array $currentWindow,
        ?array $nextIncome,
        Carbon $start,
        Carbon $horizonEnd
    ): array {
        $currentEnd = Carbon::parse($currentWindow['end_date'])->startOfDay();
        $accountGroups = $this->creditAccountGroups($user, $start, $currentEnd, $horizonEnd);

        $currentPeriodDue = $this->money(collect($accountGroups)->sum('current_month_balance'));
        $horizonDue = $this->money(collect($accountGroups)->sum('horizon_balance'));
        $futureReference = $this->money(collect($accountGroups)->sum('future_balance_reference'));
        $totalPendingReference = $this->money(collect($accountGroups)->sum('total_pending_reference'));

        $livingMinimum = $this->money((float) ($moneyPlan['minimum_living_need'] ?? 0));
        // Efectivo libre para créditos: NO se descuentan las mensualidades del
        // horizonte aquí; esas se cubren explícitamente con recommended_to_pay_now.
        // Así evitamos tratar la deuda futura (fuera del horizonte) como algo que
        // hay que liquidar hoy.
        $availableRaw = $this->money(
            (float) ($moneyPlan['starting_balance'] ?? 0)
            - (float) ($moneyPlan['urgent_payments_reserve'] ?? 0)
            - (float) ($moneyPlan['before_income_payments_reserve'] ?? 0)
            - (float) ($moneyPlan['buffer_reserve'] ?? 0)
            - $livingMinimum
        );
        $available = $this->money(max(0, $availableRaw));

        $base = [
            'mode' => 'period_credit_strategy',
            'current_period_credit_due' => $currentPeriodDue,
            'horizon_credit_due' => $horizonDue,
            'future_credit_balance_reference' => $futureReference,
            'total_pending_reference' => $totalPendingReference,
            'available_for_credit_payoff_now' => $available,
            'available_for_credit_payoff_raw' => $availableRaw,
            'recommended_to_pay_now' => 0.0,
            'optional_extra_payment' => 0.0,
            'remaining_after_recommendation' => $available,
            'living_money_minimum_until_next_income' => $livingMinimum,
            'account_groups' => $accountGroups,
            'recommended_actions' => [],
            'defer_actions' => $this->deferFutureBalanceActions($accountGroups, $nextIncome),
            'minimum_payment_actions' => $this->minimumCreditPaymentActions($accountGroups),
            'after_next_income_message' => $this->afterNextIncomeMessage($nextIncome, $accountGroups),
            'message' => '',
        ];

        if ($totalPendingReference <= 0) {
            return array_merge($base, [
                'message' => 'No hay créditos activos pendientes.',
            ]);
        }

        if ($horizonDue <= 0) {
            return array_merge($base, [
                'message' => 'No hay mensualidades de crédito dentro de este horizonte. Tu deuda futura es '
                    .$this->formatMoney($futureReference).' y se muestra solo como referencia, no como obligación inmediata.',
            ]);
        }

        if ($available <= 0) {
            return array_merge($base, [
                'message' => 'Este horizonte requiere cubrir '.$this->formatMoney($horizonDue)
                    .' de créditos, pero por ahora conserva efectivo para pagos, colchón y vida diaria.',
            ]);
        }

        // Recomendación principal: cubrir solo las mensualidades del horizonte,
        // cuenta por cuenta y en orden de presión, hasta donde alcance el efectivo.
        $recommendedActions = [];
        $remaining = $available;

        foreach ($this->sortedAccountGroups($accountGroups) as $group) {
            $horizonBalance = (float) $group['horizon_balance'];

            if ($horizonBalance <= 0 || $remaining <= 0) {
                continue;
            }

            $amount = $this->money(min($remaining, $horizonBalance));

            if ($amount <= 0) {
                continue;
            }

            $coversFull = $amount >= $horizonBalance;
            $recommendedActions[] = [
                'action' => 'pay_current_horizon_account',
                'amount' => $amount,
                'covers_full_horizon' => $coversFull,
            ] + $this->accountGroupActionMetadata($group, $this->horizonCoverReason($group, $coversFull, $amount));
            $remaining = $this->money($remaining - $amount);
        }

        $recommendedToPayNow = $this->money(array_sum(array_column($recommendedActions, 'amount')));

        // Abono extra opcional: solo si sobra dinero tras cubrir el horizonte y hay
        // deuda futura a la cual adelantar. Nunca se presenta como obligación.
        $optionalExtra = 0.0;
        $extraTarget = $this->optionalExtraTarget($accountGroups);

        if ($remaining > 0 && $extraTarget && (float) $extraTarget['future_balance_reference'] > 0) {
            $optionalExtra = $this->money(min($remaining, (float) $extraTarget['future_balance_reference']));

            if ($optionalExtra > 0) {
                $recommendedActions[] = [
                    'action' => 'optional_extra_payment_account',
                    'amount' => $optionalExtra,
                    'is_optional' => true,
                ] + $this->accountGroupActionMetadata($extraTarget, $this->optionalExtraReason($extraTarget, $optionalExtra));
                $remaining = $this->money($remaining - $optionalExtra);
            }
        }

        return array_merge($base, [
            'recommended_to_pay_now' => $recommendedToPayNow,
            'optional_extra_payment' => $optionalExtra,
            'remaining_after_recommendation' => $remaining,
            'recommended_actions' => $recommendedActions,
            'message' => $this->creditPayoffMessage($recommendedActions, $horizonDue, $futureReference, $optionalExtra),
        ]);
    }

    /**
     * Agrupa las mensualidades pendientes por cuenta/tarjeta separando lo que cae
     * dentro del horizonte (obligación del periodo) de la deuda futura (referencia).
     */
    private function creditAccountGroups(User $user, Carbon $start, Carbon $currentEnd, Carbon $horizonEnd): array
    {
        $currentMonth = today()->startOfDay();

        return CreditPurchase::query()
            ->with(['account', 'installments'])
            ->where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->orderBy('name')
            ->get()
            ->flatMap(function (CreditPurchase $credit) {
                return $credit->installments
                    ->filter(fn (CreditInstallment $installment) => $installment->status !== 'paid'
                        && $this->installmentResidual($installment) > 0)
                    ->map(function (CreditInstallment $installment) use ($credit) {
                        $due = $this->installmentEffectiveDate($installment);

                        return [
                            'credit_id' => $credit->id,
                            'credit_name' => $credit->name,
                            'account_id' => $credit->account_id,
                            'account_name' => $credit->account?->name,
                            'amount' => $this->installmentResidual($installment),
                            'due_date' => $due?->toDateString(),
                            'due_carbon' => $due,
                            'period_month' => $installment->period_month?->toDateString(),
                            'status' => $installment->status,
                        ];
                    });
            })
            ->groupBy(fn (array $item) => $item['account_id'] ?? 'no-account')
            ->map(function ($items) use ($start, $currentEnd, $horizonEnd, $currentMonth) {
                $items = collect($items);
                $first = $items->first();

                $inHorizon = $items
                    ->filter(fn (array $i) => $i['due_carbon'] !== null && $i['due_carbon']->lte($horizonEnd))
                    ->sortBy(fn (array $i) => $i['due_carbon']->timestamp)
                    ->values();
                $future = $items
                    ->filter(fn (array $i) => $i['due_carbon'] === null || $i['due_carbon']->gt($horizonEnd))
                    ->sortBy(fn (array $i) => $i['due_carbon']?->timestamp ?? PHP_INT_MAX)
                    ->values();
                $currentMonthItems = $items->filter(fn (array $i) => $i['period_month'] !== null
                    && Carbon::parse($i['period_month'])->isSameMonth($currentMonth));
                $overdue = $items->filter(fn (array $i) => $i['status'] === 'overdue'
                    || ($i['due_carbon'] !== null && $i['due_carbon']->lt($start)));
                $dueBeforeIncome = $items->filter(fn (array $i) => $i['due_carbon'] !== null
                    && $i['due_carbon']->lte($currentEnd));

                $nextDueDate = $items
                    ->pluck('due_carbon')
                    ->filter()
                    ->sortBy(fn (Carbon $date) => $date->timestamp)
                    ->first();

                return [
                    'account_id' => $first['account_id'] ?? null,
                    'account_name' => $first['account_name'] ?: 'Sin cuenta',
                    'current_month_balance' => $this->money($currentMonthItems->sum('amount')),
                    'horizon_balance' => $this->money($inHorizon->sum('amount')),
                    'future_balance_reference' => $this->money($future->sum('amount')),
                    'total_pending_reference' => $this->money($items->sum('amount')),
                    'overdue_amount' => $this->money($overdue->sum('amount')),
                    'due_before_income' => $this->money($dueBeforeIncome->sum('amount')),
                    'next_due_date' => $nextDueDate?->toDateString(),
                    'credits_count' => $items->pluck('credit_id')->unique()->count(),
                    'installments_pending_count' => $items->count(),
                    'items_in_horizon' => $inHorizon->map(fn (array $i) => $this->creditItemView($i))->all(),
                    'future_items_reference' => $future->map(fn (array $i) => $this->creditItemView($i))->all(),
                ];
            })
            ->sortBy(fn (array $group) => $group['account_name'])
            ->values()
            ->all();
    }

    private function creditItemView(array $item): array
    {
        return [
            'credit_id' => $item['credit_id'],
            'credit_name' => $item['credit_name'],
            'amount' => $this->money((float) $item['amount']),
            'due_date' => $item['due_date'],
            'period_month' => $item['period_month'],
        ];
    }

    private function sortedAccountGroups(array $groups): array
    {
        $sorted = $groups;

        usort($sorted, fn (array $a, array $b) => $this->compareAccountPriority($a, $b));

        return $sorted;
    }

    private function optionalExtraTarget(array $groups): ?array
    {
        $candidates = array_values(array_filter(
            $groups,
            fn (array $group) => (float) $group['future_balance_reference'] > 0
        ));

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b) => $this->compareAccountPriority($a, $b));

        return $candidates[0];
    }

    private function compareAccountPriority(array $a, array $b): int
    {
        $aOverdue = (float) ($a['overdue_amount'] ?? 0) > 0 ? 1 : 0;
        $bOverdue = (float) ($b['overdue_amount'] ?? 0) > 0 ? 1 : 0;
        if ($aOverdue !== $bOverdue) {
            return $bOverdue <=> $aOverdue;
        }

        $aPressure = max((float) $a['due_before_income'], (float) $a['horizon_balance']);
        $bPressure = max((float) $b['due_before_income'], (float) $b['horizon_balance']);
        if ($aPressure !== $bPressure) {
            return $bPressure <=> $aPressure;
        }

        $aDate = $this->sortDate($a['next_due_date'] ?? null);
        $bDate = $this->sortDate($b['next_due_date'] ?? null);
        if ($aDate !== $bDate) {
            return $aDate <=> $bDate;
        }

        return strcmp($this->accountGroupName($a), $this->accountGroupName($b));
    }

    private function minimumCreditPaymentActions(array $groups): array
    {
        return collect($groups)
            ->filter(fn (array $group) => (float) $group['due_before_income'] > 0)
            ->map(fn (array $group) => [
                'action' => 'minimum_payment_account',
                'amount' => $this->money((float) $group['due_before_income']),
            ] + $this->accountGroupActionMetadata($group, 'Cubre solo la mensualidad mínima antes del siguiente ingreso.'))
            ->values()
            ->all();
    }

    private function deferFutureBalanceActions(array $groups, ?array $nextIncome): array
    {
        return collect($groups)
            ->filter(fn (array $group) => (float) $group['future_balance_reference'] > 0)
            ->map(fn (array $group) => [
                'action' => 'defer_future_balance',
                'amount' => $this->money((float) $group['future_balance_reference']),
                'suggested_moment' => $nextIncome ? 'Después del próximo ingreso' : 'Más adelante',
            ] + $this->accountGroupActionMetadata(
                $group,
                'La deuda futura de '.$this->accountGroupName($group).' ('.$this->formatMoney((float) $group['future_balance_reference'])
                    .') corresponde a mensualidades fuera del horizonte; se muestra solo como referencia, no como obligación de hoy.'
            ))
            ->values()
            ->all();
    }

    private function accountGroupName(array $group): string
    {
        return (string) ($group['account_name'] ?: 'Sin cuenta');
    }

    private function accountGroupActionMetadata(array $group, string $explanation): array
    {
        return [
            'account_id' => $group['account_id'] ?? null,
            'account_name' => $this->accountGroupName($group),
            'next_due_date' => $group['next_due_date'] ?? null,
            'current_month_balance' => $this->money((float) ($group['current_month_balance'] ?? 0)),
            'horizon_balance' => $this->money((float) ($group['horizon_balance'] ?? 0)),
            'future_balance_reference' => $this->money((float) ($group['future_balance_reference'] ?? 0)),
            'total_pending_reference' => $this->money((float) ($group['total_pending_reference'] ?? 0)),
            'overdue_amount' => $this->money((float) ($group['overdue_amount'] ?? 0)),
            'due_before_income' => $this->money((float) ($group['due_before_income'] ?? 0)),
            'pressure_label' => $this->creditPressureLabel($group),
            'credits_count' => (int) ($group['credits_count'] ?? 0),
            'installments_pending_count' => (int) ($group['installments_pending_count'] ?? 0),
            'items_in_horizon' => $group['items_in_horizon'] ?? [],
            'future_items_reference' => $group['future_items_reference'] ?? [],
            'explanation' => $explanation,
            'reason' => $explanation,
        ];
    }

    private function creditPressureLabel(array $group): string
    {
        if ((float) ($group['overdue_amount'] ?? 0) > 0) {
            return 'Vencido';
        }

        if ((float) ($group['due_before_income'] ?? 0) > 0) {
            return 'Antes del próximo ingreso';
        }

        if ((float) ($group['horizon_balance'] ?? 0) > 0) {
            return 'Dentro del horizonte';
        }

        if ((float) ($group['future_balance_reference'] ?? 0) > 0) {
            return 'Deuda futura';
        }

        return 'Deuda pendiente';
    }

    private function horizonCoverReason(array $group, bool $coversFull, float $amount): string
    {
        $accountName = $this->accountGroupName($group);
        $horizonBalance = $this->money((float) ($group['horizon_balance'] ?? 0));

        if ($coversFull) {
            return 'Cubre las mensualidades de '.$accountName.' que caen en este horizonte ('
                .$this->formatMoney($horizonBalance).'). No incluye deuda de meses futuros.';
        }

        return 'Abona '.$this->formatMoney($amount).' a las mensualidades de '.$accountName
            .' del horizonte; aún quedará saldo del periodo por '.$this->formatMoney($this->money($horizonBalance - $amount)).'.';
    }

    private function optionalExtraReason(array $group, float $amount): string
    {
        $accountName = $this->accountGroupName($group);
        $futureReference = $this->money((float) ($group['future_balance_reference'] ?? 0));

        return 'Opcional: puedes adelantar '.$this->formatMoney($amount).' a '.$accountName
            .' como abono extra a su deuda futura ('.$this->formatMoney($futureReference).') sin romper tu colchón. No es obligación del periodo.';
    }

    private function creditPayoffMessage(array $recommendedActions, float $horizonDue, float $futureReference, float $optionalExtra): string
    {
        $horizonActions = collect($recommendedActions)->where('action', 'pay_current_horizon_account')->values();
        $names = $horizonActions->pluck('account_name')->all();
        $parts = [];

        if ($names !== []) {
            $parts[] = 'Para este horizonte necesitas cubrir '.$this->formatMoney($horizonDue).' en '.$this->humanList($names).'.';
        } else {
            $parts[] = 'Para este horizonte necesitas cubrir '.$this->formatMoney($horizonDue).' de créditos.';
        }

        if ($futureReference > 0) {
            $parts[] = 'La deuda futura es '.$this->formatMoney($futureReference).' y se muestra solo como referencia, no como obligación inmediata.';
        }

        if ($optionalExtra > 0) {
            $extra = collect($recommendedActions)->firstWhere('action', 'optional_extra_payment_account');
            $parts[] = 'Si sobra, puedes adelantar '.$this->formatMoney($optionalExtra).' a '
                .($extra['account_name'] ?? 'una cuenta').' como abono extra opcional sin romper tu colchón.';
        }

        return implode(' ', $parts);
    }

    private function humanList(array $items): string
    {
        $items = array_values(array_filter(array_map(fn ($item) => (string) $item, $items)));

        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' y '.$last;
    }

    private function afterNextIncomeMessage(?array $nextIncome, array $accountGroups): ?string
    {
        if (! $nextIncome || $accountGroups === []) {
            return null;
        }

        return 'Después del ingreso del '.$nextIncome['date']->format('d/m/Y')
            .' revisa si conviene adelantar más a tus créditos si el plan sigue estable.';
    }

    private function installmentResidual(CreditInstallment $installment): float
    {
        return $this->money(max(0, (float) $installment->amount - (float) $installment->paid_amount));
    }

    private function installmentEffectiveDate(CreditInstallment $installment): ?Carbon
    {
        return $installment->due_date?->copy()->startOfDay()
            ?? $installment->period_month?->copy()->startOfMonth();
    }

    private function sortDate(?string $date): int
    {
        return $date ? Carbon::parse($date)->timestamp : PHP_INT_MAX;
    }

    private function actions(array $events, array $moneyPlan, array $savingsGuidance, array $currentWindow, Carbon $start, ?array $nextIncome): array
    {
        $currentEnd = Carbon::parse($currentWindow['end_date'])->startOfDay();
        $nextIncomeDate = $nextIncome['date'] ?? null;
        $actions = [
            'pay_today' => [],
            'pay_before_income' => [],
            'reserve' => [],
            'wait' => [],
            'after_next_income' => [],
            'need_money' => [],
            'save' => [],
        ];

        foreach ($events as $event) {
            $forcedChargeState = $this->forcedChargeWindowState($event, $start);

            if ($forcedChargeState === 'before') {
                $actions['reserve'][] = $this->actionItem($event, $this->forcedChargeBeforeWindowReason($event));

                if ($nextIncomeDate && $event['date_carbon']->gte($nextIncomeDate)) {
                    $actions['wait'][] = $this->actionItem($event, 'Vence/cobra después del siguiente ingreso; no lo adelantes, solo resérvalo cuando corresponda.');
                    $actions['after_next_income'][] = $this->actionItem($event, 'Cuando entre el siguiente ingreso, revisa la reserva para este cobro automático.');
                }

                continue;
            }

            if ($forcedChargeState === 'in_window') {
                $actions['pay_today'][] = $this->actionItem($event, $this->forcedChargeInWindowReason($event));
                continue;
            }

            if ($forcedChargeState === 'after') {
                $actions['pay_today'][] = $this->actionItem($event, 'La ventana de cobro ya pasó. Revisa si se cobró o si quedó pendiente.');
                continue;
            }

            if ($this->isUrgent($event, $start)) {
                $actions['pay_today'][] = $this->actionItem($event, 'Vencido o vence hoy; pagalo primero.');
                continue;
            }

            if ($event['date_carbon']->lte($currentEnd)) {
                if ($this->canPayEarlyWithoutBreakingPlan($currentWindow)) {
                    $actions['pay_today'][] = $this->actionItem($event, 'Vence antes del siguiente ingreso y el plan puede cubrirlo sin romper vida diaria ni colchon.');
                }

                $actions['pay_before_income'][] = $this->actionItem($event, 'Debe quedar cubierto antes del siguiente ingreso.');
                $actions['reserve'][] = $this->actionItem($event, 'Separa este dinero para no gastarlo antes de su fecha.');
                continue;
            }

            $actions['reserve'][] = $this->actionItem($event, 'Esta dentro del horizonte; conviene separarlo o tenerlo identificado.');

            if ($nextIncomeDate && $event['date_carbon']->gte($nextIncomeDate)) {
                $actions['wait'][] = $this->actionItem($event, 'Vence despues del siguiente ingreso; pagarlo hoy puede apretar tu efectivo.');
                $actions['after_next_income'][] = $this->actionItem($event, 'Cuando entre el siguiente ingreso, revisa y cubre este pago.');
            }
        }

        if ((float) $moneyPlan['shortfall'] > 0 || (float) $currentWindow['available_to_live'] <= 0) {
            $actions['need_money'][] = [
                'type' => 'cash',
                'id' => null,
                'name' => 'Faltante para sostener el plan',
                'amount' => $this->money((float) $moneyPlan['shortfall']),
                'date' => $currentWindow['end_date'],
                'reason' => 'No alcanza para cubrir pagos, colchon recomendado y vida diaria minima.',
            ];
        }

        if ((bool) ($savingsGuidance['should_save'] ?? false)) {
            $actions['save'][] = [
                'type' => 'saving',
                'id' => null,
                'name' => 'Ahorro libre',
                'amount' => $this->money((float) ($savingsGuidance['free_savings_available'] ?? 0)),
                'date' => $currentWindow['end_date'],
                'reason' => $savingsGuidance['message'] ?: 'Sobra despues de pagos, colchon recomendado, colchon ideal y gasto diario basico.',
            ];
        }

        return $actions;
    }

    private function canPayEarlyWithoutBreakingPlan(array $currentWindow): bool
    {
        return (float) $currentWindow['shortfall'] <= 0
            && (float) $currentWindow['available_to_live'] > 0
            && ! in_array($currentWindow['risk_level'], ['high', 'critical'], true);
    }

    private function forcedChargeWindowState(array $event, Carbon $date): ?string
    {
        if (! (bool) ($event['is_forced_charge_window'] ?? false)) {
            return null;
        }

        if (empty($event['charge_window_start']) || empty($event['charge_window_end'])) {
            return null;
        }

        $windowStart = Carbon::parse($event['charge_window_start'])->startOfDay();
        $windowEnd = Carbon::parse($event['charge_window_end'])->startOfDay();
        $date = $date->copy()->startOfDay();

        if ($date->lt($windowStart)) {
            return 'before';
        }

        if ($date->betweenIncluded($windowStart, $windowEnd)) {
            return 'in_window';
        }

        return 'after';
    }

    private function forcedChargeBeforeWindowReason(array $event): string
    {
        return 'No lo pagues todavía; se cobrará automáticamente entre '
            .$this->formatDate((string) $event['charge_window_start'])
            .' y '
            .$this->formatDate((string) $event['charge_window_end'])
            .'. Reserva '
            .$this->formatMoney((float) $event['amount'])
            .'.';
    }

    private function forcedChargeInWindowReason(array $event): string
    {
        return 'Este cobro automático puede caer entre '
            .$this->formatDate((string) $event['charge_window_start'])
            .' y '
            .$this->formatDate((string) $event['charge_window_end'])
            .'. Ten disponible '
            .$this->formatMoney((float) $event['amount'])
            .'.';
    }

    private function actionItem(array $event, string $reason): array
    {
        return [
            'type' => $event['type'],
            'id' => $event['id'],
            'name' => $event['name'],
            'amount' => $this->money((float) $event['amount']),
            'date' => $event['date'],
            'due_date' => $event['original_due_date'] ?? $event['date'],
            'reason' => $reason,
            'is_overdue' => (bool) $event['is_overdue'],
            'is_automatic_charge' => (bool) ($event['is_automatic_charge'] ?? false),
            'is_forced_charge_window' => (bool) ($event['is_forced_charge_window'] ?? false),
            'charge_window_start' => $event['charge_window_start'] ?? null,
            'charge_window_end' => $event['charge_window_end'] ?? null,
            'effective_due_date' => $event['effective_due_date'] ?? $event['date'],
            'automatic_charge_state' => $this->forcedChargeWindowState($event, today()->startOfDay()),
        ];
    }

    private function categoryBudget(User $user, array $survivalBudget, Carbon $start, Carbon $end, float $livingMoney, int $daysCount): array
    {
        $sourceRows = count($survivalBudget['categories'] ?? []) > 0
            ? $survivalBudget['categories']
            : self::DEFAULT_CATEGORY_BUCKETS;
        $totalWeight = collect($sourceRows)->sum(fn (array $row) => (float) ($row['weight_percent'] ?? 0));

        if ($totalWeight <= 0) {
            $totalWeight = max(1, count($sourceRows));
        }

        return collect($sourceRows)
            ->map(function (array $row) use ($user, $start, $end, $livingMoney, $daysCount, $totalWeight) {
                $weight = (float) ($row['weight_percent'] ?? (100 / max(1, $totalWeight)));
                $categoryId = $row['category_id'] ?? null;
                $categoryName = (string) ($row['category_name'] ?? $row['name'] ?? 'Otros');
                $budgetTotal = $this->money($livingMoney * ($weight / $totalWeight));
                $alreadySpent = $categoryId ? $this->alreadySpentInWindow($user, (int) $categoryId, $start, $end) : 0.0;
                $remaining = $this->money($budgetTotal - $alreadySpent);
                $dailyAllowance = $daysCount > 0 ? $this->money($budgetTotal / $daysCount) : 0.0;
                $recommendedToday = $daysCount > 0 ? $this->money(max(0, $remaining) / $daysCount) : 0.0;

                return [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'weight_percent' => round($weight, 2),
                    'budget_total' => $budgetTotal,
                    'daily_allowance' => $dailyAllowance,
                    'days_remaining' => $daysCount,
                    'already_spent_in_window' => $alreadySpent,
                    'remaining_for_category' => $remaining,
                    'recommended_today' => $recommendedToday,
                    'historical_spent' => $this->money((float) ($row['historical_spent'] ?? 0)),
                    'average_daily_spend' => $this->money((float) ($row['average_daily_spend'] ?? 0)),
                    'message' => $livingMoney > 0
                        ? $categoryName.': intenta no gastar mas de '.$this->formatMoney($recommendedToday).' diarios.'
                        : $categoryName.': no hay dinero libre recomendado para gastar.',
                ];
            })
            ->sortByDesc('budget_total')
            ->values()
            ->all();
    }

    private function timelineMessages(array $moneyPlan, array $buffer, array $savingsGuidance, array $currentWindow, ?array $nextIncome, array $actions): array
    {
        $messages = [];
        $payBeforeTotal = $this->money(
            (float) $moneyPlan['urgent_payments_reserve']
            + (float) $moneyPlan['before_income_payments_reserve']
            + (float) $currentWindow['installments_inside_window']
        );

        if ($nextIncome) {
            $messages[] = 'Primero guarda '.$this->formatMoney($payBeforeTotal).' para pagos antes de tu proximo ingreso del '.$nextIncome['date']->format('d/m/Y').'.';
        } else {
            $messages[] = 'No hay ingreso dentro del horizonte; trabaja con una ventana hasta el '.$this->formatDate($currentWindow['end_date']).'.';
        }

        $messages[] = 'El sistema recomienda conservar '.$this->formatMoney((float) $buffer['buffer_used']).' como colchon minimo.';

        if ((float) $moneyPlan['living_money'] > 0) {
            $messages[] = 'Puedes vivir con '.$this->formatMoney((float) $moneyPlan['daily_living_allowance']).' diarios hasta tu siguiente ingreso.';
        } else {
            $messages[] = 'No hay dinero libre para vivir sin romper pagos o colchon.';
        }

        $waitItem = $actions['wait'][0] ?? null;
        if ($waitItem) {
            $messages[] = 'No pagues todavia '.$waitItem['name'].'; vence despues del siguiente ingreso.';
        }

        $afterIncomeItem = $actions['after_next_income'][0] ?? null;
        if ($nextIncome && $afterIncomeItem) {
            $messages[] = 'Cuando entre tu ingreso del '.$nextIncome['date']->format('d/m/Y').', paga primero '.$afterIncomeItem['name'].'.';
        }

        if ((float) $moneyPlan['shortfall'] > 0) {
            $messages[] = 'Necesitas conseguir '.$this->formatMoney((float) $moneyPlan['shortfall']).' para sostener el plan.';
        }

        if (($savingsGuidance['message'] ?? '') !== '') {
            $messages[] = $savingsGuidance['message'];
        }

        return $messages;
    }

    private function headlineStatus(array $moneyPlan, array $currentWindow, array $warnings): string
    {
        if ((float) $moneyPlan['shortfall'] > 0 || $currentWindow['risk_level'] === 'critical') {
            return 'critical';
        }

        if ($warnings !== [] || in_array($currentWindow['risk_level'], ['medium', 'high'], true)) {
            return 'warning';
        }

        return 'ok';
    }

    private function headlineMessage(float $startingBalance, Carbon $currentEnd, ?array $nextIncome): string
    {
        if ($nextIncome) {
            return 'Tienes '.$this->formatMoney($startingBalance).'. Debes sobrevivir hasta el '.$nextIncome['date']->format('d/m/Y').'.';
        }

        return 'Tienes '.$this->formatMoney($startingBalance).'. No hay ingreso dentro del horizonte; planea hasta el '.$currentEnd->format('d/m/Y').'.';
    }

    private function riskLevel(array $days, float $bufferUsed, float $availableRaw): string
    {
        if ($availableRaw < 0) {
            return 'critical';
        }

        $risk = 'ok';
        foreach ($days as $day) {
            $closingProjected = $this->money((float) ($day['closing_projected'] ?? 0));
            $closingSafe = $this->money((float) ($day['closing_safe'] ?? 0));

            if ($closingProjected < 0) {
                return 'critical';
            }

            if ($closingProjected < $bufferUsed) {
                $risk = 'high';
            } elseif ($risk === 'ok' && $closingSafe < $bufferUsed) {
                $risk = 'medium';
            }
        }

        return $risk;
    }

    private function projectionCashNeed(array $projection, float $bufferUsed): float
    {
        $minProjected = $this->money((float) ($projection['summary']['min_projected_balance'] ?? 0));

        return $this->money(max(0, -$minProjected, $bufferUsed - $minProjected));
    }

    private function savingsPossible(array $currentWindow, float $livingNeed, float $idealGap, bool $hasNextIncome): float
    {
        if (! $hasNextIncome || (float) $currentWindow['shortfall'] > 0) {
            return 0.0;
        }

        if (in_array($currentWindow['risk_level'], ['high', 'critical'], true)) {
            return 0.0;
        }

        return $this->money(max(0, (float) $currentWindow['available_to_live'] - $livingNeed - $idealGap));
    }

    private function alreadySpentInWindow(User $user, int $categoryId, Carbon $start, Carbon $end): float
    {
        return $this->money((float) Movement::query()
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->where('category_id', $categoryId)
            ->whereDate('happened_on', '>=', $start->toDateString())
            ->whereDate('happened_on', '<=', $end->toDateString())
            ->where(function ($query) {
                $query->where('is_san_juan', false)->orWhereNull('is_san_juan');
            })
            ->where(function ($query) {
                $query->where('is_rent', false)->orWhereNull('is_rent');
            })
            ->sum('amount'));
    }

    private function isUrgent(array $event, Carbon $start): bool
    {
        return (bool) $event['is_overdue'] || $event['date_carbon']->isSameDay($start);
    }

    private function endBeforeIncome(Carbon $start, Carbon $incomeDate): Carbon
    {
        if ($incomeDate->isSameDay($start)) {
            return $start->copy();
        }

        return $incomeDate->copy()->subDay()->startOfDay();
    }

    private function earliest(Carbon $first, Carbon $second): Carbon
    {
        return $first->lte($second) ? $first->copy() : $second->copy();
    }

    private function isBasicCategory(?Category $category): bool
    {
        if (! $category || $this->isDebtCategory($category)) {
            return false;
        }

        return Str::contains($this->categorySearchText($category), self::BASIC_KEYWORDS);
    }

    private function isDebtCategory(?Category $category): bool
    {
        return Str::contains($this->categorySearchText($category), self::DEBT_KEYWORDS);
    }

    private function categorySearchText(?Category $category): string
    {
        if (! $category) {
            return '';
        }

        return Str::lower(Str::ascii(implode(' ', array_filter([
            $category->name,
            $category->group,
            $category->keywords,
        ]))));
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    private function formatMoney(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
