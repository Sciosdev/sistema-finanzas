<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Construye un contexto financiero de solo lectura para un asesor externo.
 *
 * No recibe user_id, no crea modelos y no cambia estados. El controlador
 * resuelve siempre al usuario definido por FINANCE_OWNER_EMAIL.
 */
class FinanceAdvisorSnapshotService
{
    private const COMPLETED_TREND_MONTHS = 3;

    private const DISCRETIONARY_KEYWORDS = [
        'ropa',
        'calzado',
        'zapato',
        'tenis',
        'entretenimiento',
        'ocio',
        'restaurante',
        'comida fuera',
        'delivery',
        'streaming',
        'suscripcion',
        'regalo',
        'compras',
        'salida',
        'bar',
        'cine',
        'alcohol',
    ];

    public function __construct(
        private readonly FinanceProjectionService $projections,
        private readonly FinanceDecisionPlanService $decisions,
        private readonly FinanceWeeklyEnvelopeService $weeklyEnvelopes,
        private readonly FinanceSummaryService $summaries,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $token = trim((string) config('finance.advisor.api_token'));

        return [
            'configured' => mb_strlen($token) >= 32,
            'missing' => mb_strlen($token) >= 32 ? [] : ['FINANCE_ADVISOR_API_TOKEN'],
            'endpoint' => '/api/finance/advisor/snapshot',
            'history_days' => $this->boundedConfig('history_days', 90, 30, 365),
            'horizon_days' => $this->boundedConfig('horizon_days', 45, 7, 60),
            'transaction_limit' => $this->boundedConfig('transaction_limit', 60, 0, 100),
            'include_descriptions' => (bool) config('finance.advisor.include_descriptions', true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $today = today()->startOfDay();
        $historyDays = $this->boundedConfig('history_days', 90, 30, 365);
        $horizonDays = $this->boundedConfig('horizon_days', 45, 7, 60);
        $transactionLimit = $this->boundedConfig('transaction_limit', 60, 0, 100);
        $historyStart = $today->copy()->subDays($historyDays - 1);
        $horizonEnd = $today->copy()->addDays($horizonDays - 1);

        $projection = $this->projections->projectUntil($user, $horizonEnd);
        $decision = $this->decisions->build($user, 30);
        $weeklyEnvelope = $this->weeklyEnvelopes->build($user);
        $monthSummary = $this->summaries->monthSummary($user, $today->format('Y-m'));
        $trends = $this->spendingTrends($user, $today, $historyStart);
        $credits = $this->credits($user, $today);

        return [
            'schema_version' => '1.0',
            'generated_at' => now()->toIso8601String(),
            'application_version' => (string) config('finance.version'),
            'currency' => 'MXN',
            'timezone' => (string) config('app.timezone'),
            'read_only' => true,
            'scope' => [
                'owner_only' => true,
                'history_days' => $historyDays,
                'history_start' => $historyStart->toDateString(),
                'history_end' => $today->toDateString(),
                'horizon_days' => $horizonDays,
                'horizon_end' => $horizonEnd->toDateString(),
                'transaction_limit' => $transactionLimit,
                'descriptions_included' => (bool) config('finance.advisor.include_descriptions', true),
            ],
            'interpretation_notes' => [
                'Distingue movimientos reales de ingresos y egresos proyectados.',
                'Los ingresos esperados no deben tratarse como dinero seguro hasta recibirse.',
                'Prioriza obligaciones, mensualidades y colchón antes de recomendar gasto discrecional.',
                'Explica cada consejo con importes, fechas y categorías de este snapshot.',
                'No sugieras operaciones de escritura: esta integración es exclusivamente de lectura.',
            ],
            'accounts' => $this->accounts($user, $projection),
            'current_month' => $this->currentMonth($user, $monthSummary),
            'cash_flow_projection' => $this->projection($projection),
            'decision_plan' => $this->decisionPlan($decision),
            'weekly_envelope' => $this->weeklyEnvelope($weeklyEnvelope),
            'monthly_history' => $this->monthlyHistory($user, $historyStart, $today),
            'spending_trends' => $trends,
            'credits' => $credits,
            'recent_transactions' => $this->recentTransactions(
                $user,
                $historyStart,
                $today,
                $transactionLimit,
            ),
            'advisory_signals' => $this->signals(
                $monthSummary,
                $projection,
                $decision,
                $weeklyEnvelope,
                $trends,
                $credits,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentMonth(User $user, array $summary): array
    {
        return [
            'period' => (string) ($summary['month_value'] ?? ''),
            'income' => $this->money((float) ($summary['income'] ?? 0)),
            'yields' => $this->money((float) ($summary['yields'] ?? 0)),
            'total_income' => $this->money((float) ($summary['total_income'] ?? 0)),
            'expenses' => $this->money((float) ($summary['expenses'] ?? 0)),
            'net_cash_flow' => $this->money((float) ($summary['expected_leftover'] ?? 0)),
            'pending_payments' => $this->money((float) ($summary['pending_payments'] ?? 0)),
            'pending_expected_income' => $this->money((float) ($summary['pending_expected_income'] ?? 0)),
            'projected_total_income' => $this->money((float) ($summary['projected_total_income'] ?? 0)),
            'unknown_expenses' => $this->money((float) ($summary['unknown_expenses'] ?? 0)),
            'obligation_totals' => $this->stripInternalIds($summary['obligation_totals'] ?? []),
            'next_payments' => $this->stripInternalIds(
                collect($summary['next_payments'] ?? [])->take(20)->values()->all()
            ),
            'next_expected_incomes' => $this->stripInternalIds(
                collect($summary['next_expected_incomes'] ?? [])->take(20)->values()->all()
            ),
            'important_expense_concepts' => $this->stripInternalIds(
                collect($summary['important_expense_concepts'] ?? [])->values()->all()
            ),
            'spending_opportunities' => $this->stripInternalIds(
                collect($summary['spending_opportunities'] ?? [])->values()->all()
            ),
            'credit_line' => $this->summaries->creditLineSummary($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accounts(User $user, array $projection): array
    {
        $starting = (array) data_get($projection, 'meta.starting_by_account', []);
        $rows = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function (Account $account) use ($starting) {
                $projected = $starting[$account->id] ?? [];

                return [
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => $this->money((float) ($projected['balance'] ?? 0)),
                    'credit_limit' => $account->credit_limit === null
                        ? null
                        : $this->money((float) $account->credit_limit),
                    'is_credit_cycle_configured' => $account->hasCreditCycle(),
                ];
            })
            ->values()
            ->all();

        return [
            'total_balance' => $this->money((float) data_get($projection, 'meta.starting_balance', 0)),
            'items' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function projection(array $projection): array
    {
        $eventDays = collect($projection['days'] ?? [])
            ->filter(function (array $day) {
                return ($day['incomes'] ?? []) !== []
                    || ($day['payments'] ?? []) !== []
                    || ($day['installments'] ?? []) !== []
                    || ($day['card_charges'] ?? []) !== []
                    || ($day['risk'] ?? 'ok') !== 'ok';
            })
            ->map(function (array $day) {
                return [
                    'date' => (string) ($day['date'] ?? ''),
                    'risk' => (string) ($day['risk'] ?? 'ok'),
                    'income_total' => $this->money((float) ($day['income_total'] ?? 0)),
                    'payment_total' => $this->money((float) ($day['payment_total'] ?? 0)),
                    'installment_total' => $this->money((float) ($day['installment_total'] ?? 0)),
                    'card_charge_total' => $this->money((float) ($day['card_charge_total'] ?? 0)),
                    'closing_safe' => $this->money((float) ($day['closing_safe'] ?? 0)),
                    'closing_projected' => $this->money((float) ($day['closing_projected'] ?? 0)),
                    'incomes' => $this->stripInternalIds($day['incomes'] ?? []),
                    'payments' => $this->stripInternalIds($day['payments'] ?? []),
                    'installments' => $this->stripInternalIds($day['installments'] ?? []),
                    'card_charges' => $this->stripInternalIds($day['card_charges'] ?? []),
                ];
            })
            ->values()
            ->all();

        return [
            'start_date' => (string) data_get($projection, 'meta.start_date', ''),
            'end_date' => (string) data_get($projection, 'meta.end_date', ''),
            'starting_balance' => $this->money((float) data_get($projection, 'meta.starting_balance', 0)),
            'configured_buffer' => $this->money((float) data_get($projection, 'meta.buffer', 0)),
            'baseline_cut_date' => data_get($projection, 'meta.baseline_cut_date'),
            'baseline_age_days' => data_get($projection, 'meta.baseline_age_days'),
            'summary' => $this->stripInternalIds($projection['summary'] ?? []),
            'warnings' => array_values($projection['warnings'] ?? []),
            'event_days' => $eventDays,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decisionPlan(array $decision): array
    {
        return $this->stripInternalIds([
            'headline' => $decision['headline'] ?? [],
            'buffer' => $decision['buffer'] ?? [],
            'current_window' => $decision['current_window'] ?? [],
            'after_next_income_window' => $decision['after_next_income_window'] ?? null,
            'money_plan' => $decision['money_plan'] ?? [],
            'savings_guidance' => $decision['savings_guidance'] ?? [],
            'credit_payoff_strategy' => $decision['credit_payoff_strategy'] ?? [],
            'actions' => $decision['actions'] ?? [],
            'category_budget' => $decision['category_budget'] ?? [],
            'timeline_messages' => $decision['timeline_messages'] ?? [],
            'warnings' => $decision['warnings'] ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function weeklyEnvelope(array $weekly): array
    {
        return $this->stripInternalIds([
            'meta' => $weekly['meta'] ?? [],
            'current_week' => $weekly['current_week'] ?? null,
            'category_weights' => $weekly['category_weights'] ?? [],
            'pattern_advice' => $weekly['pattern_advice'] ?? [],
            'messages' => $weekly['messages'] ?? [],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function monthlyHistory(User $user, Carbon $historyStart, Carbon $today): array
    {
        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->whereDate('happened_on', '>=', $historyStart->toDateString())
            ->whereDate('happened_on', '<=', $today->toDateString())
            ->orderBy('happened_on')
            ->get(['happened_on', 'movement_type', 'amount']);

        $months = [];
        $cursor = $historyStart->copy()->startOfMonth();
        $lastMonth = $today->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $key = $cursor->format('Y-m');
            $rows = $movements->filter(
                fn (Movement $movement) => $movement->happened_on->format('Y-m') === $key
            );
            $income = $this->sumType($rows, 'income');
            $yields = $this->sumType($rows, 'yield');
            $expenses = $this->sumType($rows, 'expense');

            $months[] = [
                'month' => $key,
                'income' => $income,
                'yields' => $yields,
                'expenses' => $expenses,
                'net_cash_flow' => $this->money($income + $yields - $expenses),
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function spendingTrends(User $user, Carbon $today, Carbon $historyStart): array
    {
        $currentStart = $today->copy()->startOfMonth();
        $trendStart = $currentStart->copy()->subMonthsNoOverflow(self::COMPLETED_TREND_MONTHS);
        $queryStart = $historyStart->lt($trendStart) ? $historyStart->copy() : $trendStart;
        $previousStart = $currentStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousSamePeriodEnd = $previousStart->copy()->day(
            min($today->day, $previousStart->daysInMonth)
        );

        $movements = Movement::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereDate('happened_on', '>=', $queryStart->toDateString())
            ->whereDate('happened_on', '<=', $today->toDateString())
            ->get();

        return $movements
            ->groupBy(fn (Movement $movement) => $movement->category_id
                ? 'category:'.$movement->category_id
                : 'uncategorized')
            ->map(function (Collection $rows) use (
                $today,
                $historyStart,
                $currentStart,
                $previousStart,
                $previousSamePeriodEnd
            ) {
                $category = $rows->first()->category;
                $currentRows = $this->between($rows, $currentStart, $today);
                $previousRows = $this->between($rows, $previousStart, $previousSamePeriodEnd);
                $current = $this->sumAmounts($currentRows);
                $previousSamePeriod = $this->sumAmounts($previousRows);
                $completedAmounts = collect(range(1, self::COMPLETED_TREND_MONTHS))
                    ->map(function (int $monthsAgo) use ($rows, $currentStart) {
                        $start = $currentStart->copy()->subMonthsNoOverflow($monthsAgo)->startOfMonth();

                        return $this->sumAmounts($this->between($rows, $start, $start->copy()->endOfMonth()));
                    });
                $completedAverage = $this->money($completedAmounts->avg() ?? 0);
                $projected = $this->money(
                    ($current / max(1, $today->day)) * $today->daysInMonth
                );
                $searchText = Str::lower(Str::ascii(implode(' ', array_filter([
                    $category?->name,
                    $category?->group,
                    $category?->keywords,
                ]))));

                return [
                    'category' => $category?->name ?? 'Sin categoría',
                    'group' => $category?->group,
                    'is_discretionary' => Str::contains($searchText, self::DISCRETIONARY_KEYWORDS),
                    'current_month_amount' => $current,
                    'current_month_transactions' => $currentRows->count(),
                    'projected_month_amount' => $projected,
                    'previous_month_same_period_amount' => $previousSamePeriod,
                    'change_vs_previous_same_period_percent' => $this->percentageChange(
                        $current,
                        $previousSamePeriod
                    ),
                    'average_previous_three_months' => $completedAverage,
                    'change_vs_three_month_average_percent' => $this->percentageChange(
                        $projected,
                        $completedAverage
                    ),
                    'history_period_amount' => $this->sumAmounts(
                        $rows->filter(
                            fn (Movement $movement) => $movement->happened_on->gte($historyStart)
                        )
                    ),
                ];
            })
            ->sort(function (array $left, array $right) {
                $current = $right['current_month_amount'] <=> $left['current_month_amount'];

                return $current !== 0
                    ? $current
                    : $right['history_period_amount'] <=> $left['history_period_amount'];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function credits(User $user, Carbon $today): array
    {
        return CreditPurchase::query()
            ->with(['account', 'category', 'installments', 'freePayments'])
            ->where('user_id', $user->id)
            ->orderByDesc('purchase_date')
            ->get()
            ->map(function (CreditPurchase $credit) use ($today) {
                $installmentPaid = (float) $credit->installments->sum('paid_amount');
                $freePaid = (float) $credit->freePayments->sum('amount_applied');
                $totalPaid = $this->money($installmentPaid + $freePaid);
                $balanceDue = $this->money(max(0, (float) $credit->total_amount - $totalPaid));
                $pending = $credit->installments
                    ->filter(fn (CreditInstallment $installment) => ! in_array(
                        $installment->status,
                        ['paid', 'skipped'],
                        true
                    ) && $this->installmentResidual($installment) > 0)
                    ->sortBy(function (CreditInstallment $installment) {
                        $date = $installment->due_date ?? $installment->period_month;

                        return $date?->timestamp ?? PHP_INT_MAX;
                    })
                    ->values();
                $next = $pending->first();
                $overdue = $pending->sum(function (CreditInstallment $installment) use ($today) {
                    $due = $installment->due_date ?? $installment->period_month;

                    return $installment->status === 'overdue' || ($due && $due->lt($today))
                        ? $this->installmentResidual($installment)
                        : 0;
                });

                return [
                    'name' => $credit->name,
                    'account' => $credit->account?->name,
                    'category' => $credit->category?->name,
                    'status' => $credit->status,
                    'purchase_date' => $credit->purchase_date?->toDateString(),
                    'total_amount' => $this->money((float) $credit->total_amount),
                    'total_paid' => $totalPaid,
                    'balance_due' => $balanceDue,
                    'months' => (int) $credit->months,
                    'manual_schedule' => (bool) $credit->is_manual_schedule,
                    'pending_installments' => $pending->count(),
                    'overdue_amount' => $this->money((float) $overdue),
                    'next_due_date' => ($next?->due_date ?? $next?->period_month)?->toDateString(),
                    'next_due_amount' => $next
                        ? $this->installmentResidual($next)
                        : 0.0,
                ];
            })
            ->filter(fn (array $credit) => $credit['status'] === 'active' || $credit['balance_due'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(
        User $user,
        Carbon $historyStart,
        Carbon $today,
        int $limit,
    ): array {
        if ($limit === 0) {
            return [];
        }

        $includeDescriptions = (bool) config('finance.advisor.include_descriptions', true);

        return Movement::query()
            ->with(['account', 'category'])
            ->where('user_id', $user->id)
            ->whereDate('happened_on', '>=', $historyStart->toDateString())
            ->whereDate('happened_on', '<=', $today->toDateString())
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Movement $movement) => [
                'date' => $movement->happened_on->toDateString(),
                'type' => $movement->movement_type,
                'amount' => $this->money((float) $movement->amount),
                'description' => $includeDescriptions
                    ? mb_substr(trim((string) $movement->description), 0, 240)
                    : null,
                'category' => $movement->category?->name,
                'category_group' => $movement->category?->group,
                'account' => $movement->account?->name,
                'source' => $movement->source,
                'is_unknown' => (bool) $movement->is_unknown,
                'is_san_juan' => (bool) $movement->is_san_juan,
                'is_rent' => (bool) $movement->is_rent,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $trends
     * @param  array<int, array<string, mixed>>  $credits
     * @return array<int, array<string, mixed>>
     */
    private function signals(
        array $monthSummary,
        array $projection,
        array $decision,
        array $weekly,
        array $trends,
        array $credits,
    ): array {
        $signals = [];
        $maxRisk = (string) data_get($projection, 'summary.max_risk', 'ok');
        $shortfall = $this->money((float) data_get($decision, 'money_plan.shortfall', 0));
        $overdue = $this->money((float) data_get($monthSummary, 'obligation_totals.overdue', 0));
        $unknown = $this->money((float) ($monthSummary['unknown_expenses'] ?? 0));
        $currentWeek = $weekly['current_week'] ?? null;

        if ($maxRisk !== 'ok') {
            $signals[] = [
                'severity' => in_array($maxRisk, ['high', 'critical'], true) ? 'critical' : 'warning',
                'type' => 'cash_flow_risk',
                'message' => 'La proyección alcanza un riesgo '.$maxRisk.' dentro del horizonte.',
                'amount' => $this->money((float) data_get($projection, 'summary.min_projected_balance', 0)),
                'date' => data_get($projection, 'summary.first_risky_date'),
            ];
        }

        if ($shortfall > 0) {
            $signals[] = [
                'severity' => 'critical',
                'type' => 'survival_shortfall',
                'message' => 'Falta dinero para sostener pagos, colchón y gasto básico.',
                'amount' => $shortfall,
            ];
        }

        if ($overdue > 0) {
            $signals[] = [
                'severity' => 'critical',
                'type' => 'overdue_obligations',
                'message' => 'Existen obligaciones vencidas pendientes.',
                'amount' => $overdue,
            ];
        }

        if ($unknown > 0) {
            $signals[] = [
                'severity' => 'warning',
                'type' => 'unknown_expenses',
                'message' => 'Hay gastos sin identificar que conviene clasificar antes de tomar decisiones.',
                'amount' => $unknown,
            ];
        }

        if (is_array($currentWeek) && (bool) ($currentWeek['tradeoff_active'] ?? false)) {
            $signals[] = [
                'severity' => 'critical',
                'type' => 'weekly_limit_reached',
                'message' => 'El tope semanal ya se consumió; evita más gasto discrecional.',
                'amount' => $this->money((float) ($currentWeek['spent_total'] ?? 0)),
                'limit' => $this->money((float) ($currentWeek['week_cap'] ?? 0)),
            ];
        }

        foreach (($currentWeek['categories'] ?? []) as $category) {
            if (! (bool) ($category['over_envelope'] ?? false)) {
                continue;
            }

            $signals[] = [
                'severity' => 'warning',
                'type' => 'weekly_category_over_envelope',
                'message' => ($category['category_name'] ?? 'Una categoría').' superó su sobre semanal.',
                'category' => $category['category_name'] ?? null,
                'amount' => $this->money((float) ($category['spent'] ?? 0)),
                'limit' => $this->money((float) ($category['envelope'] ?? 0)),
            ];
        }

        foreach ($trends as $trend) {
            $average = (float) ($trend['average_previous_three_months'] ?? 0);
            $projected = (float) ($trend['projected_month_amount'] ?? 0);

            if ($average <= 0 || $projected < $average * 1.25 || $projected - $average < 300) {
                continue;
            }

            $signals[] = [
                'severity' => ($trend['is_discretionary'] ?? false) ? 'warning' : 'info',
                'type' => 'category_acceleration',
                'message' => ($trend['category'] ?? 'Una categoría')
                    .' va por encima de su promedio mensual reciente.',
                'category' => $trend['category'] ?? null,
                'amount' => $this->money((float) ($trend['current_month_amount'] ?? 0)),
                'projected_month_amount' => $this->money($projected),
                'average_previous_three_months' => $this->money($average),
                'is_discretionary' => (bool) ($trend['is_discretionary'] ?? false),
            ];
        }

        $creditOverdue = $this->money(collect($credits)->sum('overdue_amount'));
        if ($creditOverdue > 0) {
            $signals[] = [
                'severity' => 'critical',
                'type' => 'overdue_credit',
                'message' => 'Hay mensualidades de crédito vencidas.',
                'amount' => $creditOverdue,
            ];
        }

        $rank = ['critical' => 0, 'warning' => 1, 'info' => 2];

        return collect($signals)
            ->sortBy(fn (array $signal) => $rank[$signal['severity']] ?? 3)
            ->take(20)
            ->values()
            ->all();
    }

    private function boundedConfig(string $key, int $default, int $minimum, int $maximum): int
    {
        return min($maximum, max($minimum, (int) config('finance.advisor.'.$key, $default)));
    }

    private function sumType(Collection $movements, string $type): float
    {
        return $this->money($movements
            ->where('movement_type', $type)
            ->sum(fn (Movement $movement) => (float) $movement->amount));
    }

    private function sumAmounts(Collection $movements): float
    {
        return $this->money($movements->sum(fn (Movement $movement) => (float) $movement->amount));
    }

    private function between(Collection $movements, Carbon $start, Carbon $end): Collection
    {
        return $movements
            ->filter(fn (Movement $movement) => $movement->happened_on->betweenIncluded($start, $end))
            ->values();
    }

    private function percentageChange(float $current, float $baseline): ?float
    {
        if ($baseline <= 0) {
            return null;
        }

        return round((($current - $baseline) / $baseline) * 100, 1);
    }

    private function installmentResidual(CreditInstallment $installment): float
    {
        return $this->money(max(0, (float) $installment->amount - (float) $installment->paid_amount));
    }

    private function stripInternalIds(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (! is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && ($key === 'id' || str_ends_with($key, '_id'))) {
                continue;
            }

            $clean[$key] = $this->stripInternalIds($item);
        }

        return $clean;
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
