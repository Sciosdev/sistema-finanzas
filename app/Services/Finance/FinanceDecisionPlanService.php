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
        private readonly FinanceSurvivalBudgetService $survivalBudgetService
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
        return ExpectedIncome::query()
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
            ->filter()
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
        $credits = $this->creditPayoffRows($user, $start, $currentEnd, $horizonEnd);
        $pendingDebtBefore = $this->money(collect($credits)->sum('pending_balance'));
        $mandatoryCreditDueBeforeIncome = $this->money((float) ($currentWindow['installments_inside_window'] ?? 0));
        $livingMinimum = $this->money((float) ($moneyPlan['minimum_living_need'] ?? 0));
        $availableRaw = $this->money(
            (float) ($moneyPlan['starting_balance'] ?? 0)
            - (float) ($moneyPlan['urgent_payments_reserve'] ?? 0)
            - (float) ($moneyPlan['before_income_payments_reserve'] ?? 0)
            - $mandatoryCreditDueBeforeIncome
            - (float) ($moneyPlan['buffer_reserve'] ?? 0)
            - $livingMinimum
        );
        $available = $this->money(max(0, $availableRaw));

        $base = [
            'mode' => 'reduce_monthly_pressure',
            'available_for_debt_payoff_now' => $available,
            'available_for_debt_payoff_raw' => $availableRaw,
            'should_payoff_now' => false,
            'total_recommended_to_pay_now' => 0.0,
            'remaining_after_recommendation' => $available,
            'pending_debt_before' => $pendingDebtBefore,
            'pending_debt_after' => $pendingDebtBefore,
            'mandatory_credit_due_before_income' => $mandatoryCreditDueBeforeIncome,
            'living_money_minimum_until_next_income' => $livingMinimum,
            'recommended_actions' => [],
            'defer_actions' => [],
            'minimum_payment_actions' => $this->minimumCreditPaymentActions($credits),
            'credits' => $credits,
            'after_next_income_message' => $this->afterNextIncomeMessage($nextIncome, $credits),
            'message' => '',
        ];

        if ($pendingDebtBefore <= 0) {
            return array_merge($base, [
                'message' => 'No hay creditos activos pendientes para liquidar.',
            ]);
        }

        if ($available <= 0) {
            return array_merge($base, [
                'defer_actions' => $this->deferCreditActions($credits, [], $nextIncome),
                'message' => 'No liquides creditos todavia; conserva efectivo para pagos, colchon y vida diaria.',
            ]);
        }

        $recommendedActions = [];
        $remaining = $available;
        $appliedByCredit = [];

        foreach ($this->sortedLiquidationCandidates($credits) as $credit) {
            if ((float) $credit['pending_balance'] <= 0 || (float) $credit['pending_balance'] > $remaining) {
                continue;
            }

            $amount = $this->money((float) $credit['pending_balance']);
            $recommendedActions[] = [
                'action' => 'liquidate',
                'credit_id' => $credit['credit_id'],
                'credit_name' => $credit['credit_name'],
                'amount' => $amount,
                'reason' => $this->liquidationReason($credit),
            ];
            $appliedByCredit[$credit['credit_id']] = $this->money(($appliedByCredit[$credit['credit_id']] ?? 0) + $amount);
            $remaining = $this->money($remaining - $amount);
        }

        $partialTarget = $this->partialPayoffTarget($credits, $appliedByCredit);
        if ($remaining > 0 && $partialTarget) {
            $pendingAfterLiquidations = $this->money((float) $partialTarget['pending_balance'] - (float) ($appliedByCredit[$partialTarget['credit_id']] ?? 0));
            $amount = $this->money(min($remaining, $pendingAfterLiquidations));

            if ($amount > 0) {
                $recommendedActions[] = [
                    'action' => 'extra_payment',
                    'credit_id' => $partialTarget['credit_id'],
                    'credit_name' => $partialTarget['credit_name'],
                    'amount' => $amount,
                    'reason' => 'Usa el sobrante como abono extra al credito con pago proximo mas cercano sin tocar colchon ni vida diaria.',
                ];
                $appliedByCredit[$partialTarget['credit_id']] = $this->money(($appliedByCredit[$partialTarget['credit_id']] ?? 0) + $amount);
                $remaining = $this->money($remaining - $amount);
            }
        }

        $totalRecommended = $this->money(array_sum(array_column($recommendedActions, 'amount')));
        $pendingDebtAfter = $this->money(max(0, $pendingDebtBefore - $totalRecommended));

        return array_merge($base, [
            'should_payoff_now' => $totalRecommended > 0,
            'total_recommended_to_pay_now' => $totalRecommended,
            'remaining_after_recommendation' => $remaining,
            'pending_debt_after' => $pendingDebtAfter,
            'recommended_actions' => $recommendedActions,
            'defer_actions' => $this->deferCreditActions($credits, $appliedByCredit, $nextIncome),
            'message' => $this->creditPayoffMessage($recommendedActions, $totalRecommended, $pendingDebtAfter),
        ]);
    }

    private function creditPayoffRows(User $user, Carbon $start, Carbon $currentEnd, Carbon $horizonEnd): array
    {
        return CreditPurchase::query()
            ->with(['account', 'installments'])
            ->where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->orderBy('name')
            ->get()
            ->map(function (CreditPurchase $credit) use ($start, $currentEnd, $horizonEnd) {
                $pendingInstallments = $credit->installments
                    ->filter(function (CreditInstallment $installment) {
                        return $installment->status !== 'paid'
                            && $this->installmentResidual($installment) > 0;
                    })
                    ->values();

                if ($pendingInstallments->isEmpty()) {
                    return null;
                }

                $pendingBalance = $this->money($pendingInstallments->sum(fn (CreditInstallment $installment) => $this->installmentResidual($installment)));
                $nextDueDate = $pendingInstallments
                    ->map(fn (CreditInstallment $installment) => $this->installmentEffectiveDate($installment))
                    ->filter()
                    ->sortBy(fn (Carbon $date) => $date->timestamp)
                    ->first();
                $overdueAmount = $this->money($pendingInstallments
                    ->filter(function (CreditInstallment $installment) use ($start) {
                        $due = $this->installmentEffectiveDate($installment);

                        return $installment->status === 'overdue' || ($due && $due->lt($start));
                    })
                    ->sum(fn (CreditInstallment $installment) => $this->installmentResidual($installment)));
                $dueBeforeIncome = $this->money($pendingInstallments
                    ->filter(function (CreditInstallment $installment) use ($currentEnd) {
                        $due = $this->installmentEffectiveDate($installment);

                        return $due !== null && $due->lte($currentEnd);
                    })
                    ->sum(fn (CreditInstallment $installment) => $this->installmentResidual($installment)));
                $dueInHorizon = $this->money($pendingInstallments
                    ->filter(function (CreditInstallment $installment) use ($horizonEnd) {
                        $due = $this->installmentEffectiveDate($installment);

                        return $due !== null && $due->lte($horizonEnd);
                    })
                    ->sum(fn (CreditInstallment $installment) => $this->installmentResidual($installment)));

                return [
                    'credit_id' => $credit->id,
                    'credit_name' => $credit->name,
                    'account_name' => $credit->account?->name,
                    'pending_balance' => $pendingBalance,
                    'next_due_date' => $nextDueDate?->toDateString(),
                    'overdue_amount' => $overdueAmount,
                    'due_before_income' => $dueBeforeIncome,
                    'due_in_horizon' => $dueInHorizon,
                    'installments_pending_count' => $pendingInstallments->count(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function sortedLiquidationCandidates(array $credits): array
    {
        $sorted = $credits;

        usort($sorted, function (array $a, array $b) {
            return $this->compareCreditPriority($a, $b, true);
        });

        return $sorted;
    }

    private function partialPayoffTarget(array $credits, array $appliedByCredit): ?array
    {
        $remainingCredits = array_values(array_filter($credits, function (array $credit) use ($appliedByCredit) {
            return $this->money((float) $credit['pending_balance'] - (float) ($appliedByCredit[$credit['credit_id']] ?? 0)) > 0;
        }));

        if ($remainingCredits === []) {
            return null;
        }

        usort($remainingCredits, function (array $a, array $b) {
            return $this->compareCreditPriority($a, $b, false);
        });

        return $remainingCredits[0];
    }

    private function compareCreditPriority(array $a, array $b, bool $preferLiquidationFit): int
    {
        $aOverdue = (float) $a['overdue_amount'] > 0 ? 1 : 0;
        $bOverdue = (float) $b['overdue_amount'] > 0 ? 1 : 0;
        if ($aOverdue !== $bOverdue) {
            return $bOverdue <=> $aOverdue;
        }

        $aPressure = max((float) $a['due_before_income'], (float) $a['due_in_horizon']);
        $bPressure = max((float) $b['due_before_income'], (float) $b['due_in_horizon']);
        if ($aPressure !== $bPressure) {
            return $bPressure <=> $aPressure;
        }

        $aDate = $this->sortDate($a['next_due_date'] ?? null);
        $bDate = $this->sortDate($b['next_due_date'] ?? null);
        if ($aDate !== $bDate) {
            return $aDate <=> $bDate;
        }

        if ($preferLiquidationFit && (float) $a['pending_balance'] !== (float) $b['pending_balance']) {
            return (float) $a['pending_balance'] <=> (float) $b['pending_balance'];
        }

        if (! $preferLiquidationFit && (float) $a['pending_balance'] !== (float) $b['pending_balance']) {
            return (float) $b['pending_balance'] <=> (float) $a['pending_balance'];
        }

        return (int) $a['credit_id'] <=> (int) $b['credit_id'];
    }

    private function minimumCreditPaymentActions(array $credits): array
    {
        return collect($credits)
            ->filter(fn (array $credit) => (float) $credit['due_before_income'] > 0)
            ->map(fn (array $credit) => [
                'credit_id' => $credit['credit_id'],
                'credit_name' => $credit['credit_name'],
                'amount' => $this->money((float) $credit['due_before_income']),
                'next_due_date' => $credit['next_due_date'],
                'reason' => 'Cubre la mensualidad minima antes del siguiente ingreso.',
            ])
            ->values()
            ->all();
    }

    private function deferCreditActions(array $credits, array $appliedByCredit, ?array $nextIncome): array
    {
        return collect($credits)
            ->map(function (array $credit) use ($appliedByCredit, $nextIncome) {
                $pendingAfter = $this->money((float) $credit['pending_balance'] - (float) ($appliedByCredit[$credit['credit_id']] ?? 0));

                if ($pendingAfter <= 0) {
                    return null;
                }

                return [
                    'credit_id' => $credit['credit_id'],
                    'credit_name' => $credit['credit_name'],
                    'pending_balance' => $pendingAfter,
                    'suggested_moment' => $nextIncome ? 'Despues del proximo ingreso' : 'Mas adelante',
                    'reason' => 'Conviene esperar para no bajar demasiado el efectivo actual.',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function liquidationReason(array $credit): string
    {
        if ((float) $credit['overdue_amount'] > 0) {
            return 'Tiene mensualidades vencidas y liquidarlo elimina esa presion.';
        }

        if ((float) $credit['due_before_income'] > 0) {
            return 'Se puede liquidar completo y reduce presion antes del proximo ingreso.';
        }

        if ((float) $credit['pending_balance'] <= 300) {
            return 'Se puede liquidar completo y elimina una deuda pequena.';
        }

        return 'Se puede liquidar completo sin romper colchon ni vida diaria.';
    }

    private function creditPayoffMessage(array $recommendedActions, float $totalRecommended, float $pendingDebtAfter): string
    {
        if ($totalRecommended <= 0) {
            return 'No liquides creditos todavia; primero conserva efectivo para vivir.';
        }

        $liquidations = collect($recommendedActions)->where('action', 'liquidate')->values();
        $extras = collect($recommendedActions)->where('action', 'extra_payment')->values();

        if ($liquidations->isNotEmpty() && $extras->isEmpty()) {
            $names = $liquidations->pluck('credit_name')->implode(' y ');

            return 'Puedes usar '.$this->formatMoney($totalRecommended).' para liquidar '.$names.' sin romper tu colchon.';
        }

        if ($liquidations->isNotEmpty() && $extras->isNotEmpty()) {
            $names = $liquidations->pluck('credit_name')->implode(' y ');
            $extra = $extras->first();

            return 'Puedes usar '.$this->formatMoney($totalRecommended).' para liquidar '.$names.' y abonar a '.$extra['credit_name'].' sin romper tu colchon.';
        }

        return 'Puedes abonar '.$this->formatMoney($totalRecommended).' a deuda sin romper tu colchon; quedarian '.$this->formatMoney($pendingDebtAfter).' pendientes.';
    }

    private function afterNextIncomeMessage(?array $nextIncome, array $credits): ?string
    {
        if (! $nextIncome || $credits === []) {
            return null;
        }

        return 'Despues del ingreso del '.$nextIncome['date']->format('d/m/Y').' podrias revisar los creditos restantes si el plan sigue estable.';
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
