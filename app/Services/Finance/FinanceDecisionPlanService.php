<?php

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
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

        $actions = $this->actions($events, $moneyPlan, $currentWindow, $start, $nextIncome);
        $categoryBudget = $this->categoryBudget($user, $survivalBudget, $start, $currentEnd, $moneyPlan['living_money'], $currentWindow['days_count']);
        $timelineMessages = $this->timelineMessages($moneyPlan, $buffer, $currentWindow, $nextIncome, $actions);
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
                    'original_due_date' => null,
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

    private function actions(array $events, array $moneyPlan, array $currentWindow, Carbon $start, ?array $nextIncome): array
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

        if ((float) $moneyPlan['savings_possible'] > 0) {
            $actions['save'][] = [
                'type' => 'saving',
                'id' => null,
                'name' => 'Ahorro posible',
                'amount' => $this->money((float) $moneyPlan['savings_possible']),
                'date' => $currentWindow['end_date'],
                'reason' => 'Sobra despues de pagos, colchon recomendado y gasto diario basico.',
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

    private function timelineMessages(array $moneyPlan, array $buffer, array $currentWindow, ?array $nextIncome, array $actions): array
    {
        $messages = [];
        $payBeforeTotal = $this->money(
            (float) $moneyPlan['urgent_payments_reserve']
            + (float) $moneyPlan['before_income_payments_reserve']
            + (float) $currentWindow['installments_inside_window']
        );

        if ($nextIncome) {
            $messages[] = 'Antes del '.$nextIncome['date']->format('d/m/Y').' guarda '.$this->formatMoney($payBeforeTotal).' para pagos.';
        } else {
            $messages[] = 'No hay ingreso dentro del horizonte; trabaja con una ventana hasta el '.$this->formatDate($currentWindow['end_date']).'.';
        }

        $messages[] = 'El sistema recomienda guardar '.$this->formatMoney((float) $buffer['buffer_used']).' como colchon minimo.';

        if ((float) $moneyPlan['living_money'] > 0) {
            $messages[] = 'Puedes vivir con '.$this->formatMoney((float) $moneyPlan['daily_living_allowance']).' diarios hasta tu siguiente ingreso.';
        } else {
            $messages[] = 'No hay dinero libre para vivir sin romper pagos o colchon.';
        }

        $waitItem = $actions['wait'][0] ?? null;
        if ($waitItem) {
            $messages[] = 'No pagues '.$waitItem['name'].' todavia; vence despues del siguiente ingreso.';
        }

        $afterIncomeItem = $actions['after_next_income'][0] ?? null;
        if ($nextIncome && $afterIncomeItem) {
            $messages[] = 'Cuando entre el ingreso del '.$nextIncome['date']->format('d/m/Y').', paga primero '.$afterIncomeItem['name'].'.';
        }

        if ((float) $moneyPlan['shortfall'] > 0) {
            $messages[] = 'Necesitas conseguir '.$this->formatMoney((float) $moneyPlan['shortfall']).' para sostener el plan.';
        }

        if ((float) $moneyPlan['savings_possible'] > 0) {
            $messages[] = 'Puedes ahorrar '.$this->formatMoney((float) $moneyPlan['savings_possible']).' sin afectar pagos ni gasto basico.';
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
