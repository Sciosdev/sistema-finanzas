<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Flujo proyectado diario (MVP del Planificador). Proyecta a 7/15/30 días con
 * dos pistas: "saldo seguro" (solo egresos, sin ingresos futuros) y "saldo
 * proyectado" (egresos + ingresos esperados).
 *
 * Solo lectura: el saldo inicial se toma de la misma conciliación que usan los
 * cortes (FinanceCutSuggestionService::expectedBalances) sobre el mismo
 * universo de cuentas (activas del usuario, sin excluir por credit_limit).
 * No crea movimientos ni cambia estados de pagos, ingresos o mensualidades.
 */
class FinanceProjectionService
{
    public const HORIZONS = [7, 15, 30];

    private const STALE_BASELINE_DAYS = 7;

    private const RISK_RANK = ['ok' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];

    public function __construct(private readonly FinanceCutSuggestionService $cutSuggestions)
    {
    }

    public function project(User $user, int $horizonDays): array
    {
        if (! in_array($horizonDays, self::HORIZONS, true)) {
            throw new InvalidArgumentException('Horizonte inválido: solo se permiten 7, 15 o 30 días.');
        }

        $start = today()->startOfDay();
        $end = $start->copy()->addDays($horizonDays - 1);

        $settings = PlannerSetting::where('user_id', $user->id)->first();
        $buffer = $this->money((float) ($settings?->minimum_buffer ?? 0));
        $countOverdueIncome = (bool) ($settings?->count_overdue_income ?? false);

        // Mismo universo que la pantalla de cortes (accountsFor): cuentas
        // activas del usuario. No se excluye por tipo ni por credit_limit.
        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $expected = $this->cutSuggestions->expectedBalances($user, $accounts, $start);

        $startingBalance = 0.0;
        $startingByAccount = [];
        foreach ($accounts as $account) {
            $balance = $this->money((float) ($expected[$account->id]['expected'] ?? 0));
            $startingBalance = $this->money($startingBalance + $balance);
            $startingByAccount[$account->id] = [
                'name' => $account->name,
                'color' => $account->color,
                'balance' => $balance,
            ];
        }

        $lastCut = DailyCut::where('user_id', $user->id)
            ->orderByDesc('cut_date')
            ->first();
        $baselineAge = $lastCut ? (int) abs($lastCut->cut_date->copy()->startOfDay()->diffInDays($start)) : null;

        $warnings = [];
        if (! $lastCut) {
            $warnings[] = 'no_baseline_cut';
        } elseif ($baselineAge > self::STALE_BASELINE_DAYS) {
            $warnings[] = 'stale_baseline';
        }

        $buckets = [];
        for ($offset = 0; $offset < $horizonDays; $offset++) {
            $buckets[$start->copy()->addDays($offset)->toDateString()] = [
                'incomes' => [],
                'payments' => [],
                'installments' => [],
            ];
        }
        $dayOneKey = $start->toDateString();

        $overdueIncomeItems = $this->assignIncomes($user, $buckets, $start, $end, $dayOneKey, $countOverdueIncome);
        $this->assignPlannedPayments($user, $buckets, $start, $end, $dayOneKey);
        $this->assignInstallments($user, $buckets, $start, $end, $dayOneKey);

        if ($end->format('Y-m') !== $start->format('Y-m')) {
            $nextStart = $start->copy()->startOfMonth()->addMonth();
            $hasNextFlow = PlannedPayment::where('user_id', $user->id)
                ->whereBetween('period_month', [$nextStart->toDateString(), $nextStart->copy()->endOfMonth()->toDateString()])
                ->exists();

            if (! $hasNextFlow) {
                $warnings[] = 'next_month_flow_empty';
            }
        }

        $days = [];
        $openingSafe = $startingBalance;
        $openingProjected = $startingBalance;
        $totals = ['incomes' => 0.0, 'payments' => 0.0, 'installments' => 0.0];
        $overduePaymentsTotal = 0.0;
        $overduePaymentsCount = 0;
        $minSafe = null;
        $minProjected = null;
        $firstRiskyDate = null;
        $maxRisk = 'ok';

        foreach ($buckets as $dateKey => $bucket) {
            $date = Carbon::parse($dateKey);
            $incomeTotal = $this->money(array_sum(array_column($bucket['incomes'], 'amount')));
            $paymentTotal = $this->money(array_sum(array_column($bucket['payments'], 'amount')));
            $installmentTotal = $this->money(array_sum(array_column($bucket['installments'], 'amount')));

            $closingSafe = $this->money($openingSafe - $paymentTotal - $installmentTotal);
            $closingProjected = $this->money($openingProjected + $incomeTotal - $paymentTotal - $installmentTotal);
            $risk = $this->riskFor($closingSafe, $closingProjected, $buffer);

            $days[] = [
                'date' => $dateKey,
                'weekday_label' => ucfirst($date->translatedFormat('D d/m')),
                'opening_safe' => $openingSafe,
                'opening_projected' => $openingProjected,
                'incomes' => $bucket['incomes'],
                'payments' => $bucket['payments'],
                'installments' => $bucket['installments'],
                'income_total' => $incomeTotal,
                'payment_total' => $paymentTotal,
                'installment_total' => $installmentTotal,
                'closing_safe' => $closingSafe,
                'closing_projected' => $closingProjected,
                'buffer_gap_safe' => $this->money($closingSafe - $buffer),
                'buffer_gap_projected' => $this->money($closingProjected - $buffer),
                'risk' => $risk,
            ];

            $totals['incomes'] = $this->money($totals['incomes'] + $incomeTotal);
            $totals['payments'] = $this->money($totals['payments'] + $paymentTotal);
            $totals['installments'] = $this->money($totals['installments'] + $installmentTotal);

            foreach (array_merge($bucket['payments'], $bucket['installments']) as $event) {
                if ($event['is_overdue']) {
                    $overduePaymentsTotal = $this->money($overduePaymentsTotal + $event['amount']);
                    $overduePaymentsCount++;
                }
            }

            if ($minSafe === null || $closingSafe < $minSafe['balance']) {
                $minSafe = ['balance' => $closingSafe, 'date' => $dateKey];
            }
            if ($minProjected === null || $closingProjected < $minProjected['balance']) {
                $minProjected = ['balance' => $closingProjected, 'date' => $dateKey];
            }
            if ($risk !== 'ok' && $firstRiskyDate === null) {
                $firstRiskyDate = $dateKey;
            }
            if (self::RISK_RANK[$risk] > self::RISK_RANK[$maxRisk]) {
                $maxRisk = $risk;
            }

            $openingSafe = $closingSafe;
            $openingProjected = $closingProjected;
        }

        return [
            'meta' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'horizon_days' => $horizonDays,
                'buffer' => $buffer,
                'count_overdue_income' => $countOverdueIncome,
                'baseline_cut_date' => $lastCut?->cut_date->toDateString(),
                'baseline_age_days' => $baselineAge,
                'starting_balance' => $startingBalance,
                'starting_by_account' => $startingByAccount,
            ],
            'days' => $days,
            'summary' => [
                'min_safe_balance' => $minSafe['balance'] ?? $startingBalance,
                'min_safe_date' => $minSafe['date'] ?? $start->toDateString(),
                'min_projected_balance' => $minProjected['balance'] ?? $startingBalance,
                'min_projected_date' => $minProjected['date'] ?? $start->toDateString(),
                'first_risky_date' => $firstRiskyDate,
                'max_risk' => $maxRisk,
                'total_incomes' => $totals['incomes'],
                'total_payments' => $totals['payments'],
                'total_installments' => $totals['installments'],
                'overdue_income_total' => $this->money(array_sum(array_column($overdueIncomeItems, 'amount'))),
                'overdue_income_items' => $overdueIncomeItems,
                'overdue_payments_total' => $overduePaymentsTotal,
                'overdue_payments_count' => $overduePaymentsCount,
                'end_balance_safe' => $openingSafe,
                'end_balance_projected' => $openingProjected,
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * Ingresos esperados (solo finance_expected_incomes; las rentas por
     * contrato quedan para fase 2). Vencidos: por default no entran a ninguna
     * pista y se reportan aparte; con count_overdue_income entran al día 1
     * solo en la pista proyectada (los buckets de ingresos únicamente
     * alimentan el saldo proyectado).
     *
     * @return array<int, array{id: int, name: string, amount: float, due_date: ?string}>
     */
    private function assignIncomes(User $user, array &$buckets, Carbon $start, Carbon $end, string $dayOneKey, bool $countOverdueIncome): array
    {
        $overdueItems = [];

        $incomes = ExpectedIncome::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($incomes as $income) {
            $residual = $this->money(max(0, (float) $income->amount - (float) $income->received_amount));

            if ($residual <= 0) {
                continue;
            }

            // Sin fecha: se asume el último día de su mes (el ingreso llega lo
            // más tarde posible — regla conservadora).
            $assigned = $income->due_date?->copy()->startOfDay()
                ?? $income->period_month->copy()->endOfMonth()->startOfDay();

            if ($assigned->lt($start)) {
                if ($countOverdueIncome) {
                    $buckets[$dayOneKey]['incomes'][] = [
                        'id' => $income->id,
                        'name' => $income->name,
                        'amount' => $residual,
                        'is_overdue' => true,
                    ];
                } else {
                    $overdueItems[] = [
                        'id' => $income->id,
                        'name' => $income->name,
                        'amount' => $residual,
                        'due_date' => $income->due_date?->toDateString(),
                    ];
                }

                continue;
            }

            if ($assigned->gt($end)) {
                continue;
            }

            $buckets[$assigned->toDateString()]['incomes'][] = [
                'id' => $income->id,
                'name' => $income->name,
                'amount' => $residual,
                'is_overdue' => false,
            ];
        }

        return $overdueItems;
    }

    /**
     * Pagos planeados pendientes. Solo el residual (amount − paid_amount):
     * la parte pagada ya bajó el saldo inicial vía su movimiento. Los "paid"
     * (incluye pagados con crédito) y "skipped" no entran; sus mensualidades,
     * si las hay, entran por assignInstallments. Vencidos caen al día 1.
     */
    private function assignPlannedPayments(User $user, array &$buckets, Carbon $start, Carbon $end, string $dayOneKey): void
    {
        $payments = PlannedPayment::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $currentMonth = $start->copy()->startOfMonth();

        foreach ($payments as $payment) {
            $residual = $this->money(max(0, (float) $payment->amount - (float) $payment->paid_amount));

            if ($residual <= 0) {
                continue;
            }

            $due = $payment->due_date?->copy()->startOfDay();
            $isOverdue = $payment->status === 'overdue' || ($due && $due->lt($start));

            if ($due === null) {
                // Sin fecha: el egreso pega lo antes posible (conservador).
                // Mes en curso o pasado → día 1; mes futuro → día 1 de ese mes.
                $period = $payment->period_month->copy()->startOfMonth();
                $assigned = $period->lte($currentMonth) ? $start->copy() : $period;
                $isOverdue = $isOverdue || $period->lt($currentMonth);
            } elseif ($due->lt($start)) {
                $assigned = $start->copy();
            } else {
                $assigned = $due;
            }

            if ($assigned->gt($end)) {
                continue;
            }

            $buckets[$assigned->toDateString()]['payments'][] = [
                'id' => $payment->id,
                'name' => $payment->name,
                'amount' => $residual,
                'is_overdue' => $isOverdue,
                'has_due_date' => $due !== null,
            ];
        }
    }

    /**
     * Mensualidades de crédito pendientes. Sin due_date se usa el payment_day
     * de la cuenta del crédito dentro de su mes (o el día 15 como convención).
     * Vencidas caen al día 1.
     */
    private function assignInstallments(User $user, array &$buckets, Carbon $start, Carbon $end, string $dayOneKey): void
    {
        $installments = CreditInstallment::with('creditPurchase.account')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($installments as $installment) {
            $residual = $this->money(max(0, (float) $installment->amount - (float) $installment->paid_amount));

            if ($residual <= 0) {
                continue;
            }

            $credit = $installment->creditPurchase;
            $due = $installment->due_date?->copy()->startOfDay();

            if ($due === null) {
                $period = $installment->period_month->copy()->startOfMonth();
                $day = (int) ($credit?->account?->payment_day ?: 15);
                $due = $period->copy()->day(min($day, $period->daysInMonth));
            }

            $isOverdue = $installment->status === 'overdue' || $due->lt($start);
            $assigned = $due->lt($start) ? $start->copy() : $due;

            if ($assigned->gt($end)) {
                continue;
            }

            $buckets[$assigned->toDateString()]['installments'][] = [
                'id' => $installment->id,
                'credit_name' => $credit?->name ?? 'Crédito',
                'installment_label' => $installment->installment_number . ' / ' . ($credit?->months ?? '-'),
                'amount' => $residual,
                'is_overdue' => $isOverdue,
            ];
        }
    }

    /**
     * El proyectado siempre es ≥ que el seguro (los ingresos solo suman), por
     * eso el orden de evaluación crítico → alto → medio → ok es suficiente.
     * Los ≥ son inclusivos: saldo exactamente igual al colchón es "ok".
     */
    private function riskFor(float $closingSafe, float $closingProjected, float $buffer): string
    {
        if ($closingProjected < 0) {
            return 'critical';
        }

        if ($closingProjected < $buffer) {
            return 'high';
        }

        if ($closingSafe < $buffer) {
            return 'medium';
        }

        return 'ok';
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
