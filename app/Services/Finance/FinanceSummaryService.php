<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditInstallment;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\RentalContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceSummaryService
{
    public function monthSummary(User $user, ?string $month = null): array
    {
        [$start, $end] = $this->monthRange($month);

        $movementQuery = Movement::query()
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()]);

        $income = $this->sumMoney((clone $movementQuery)->where('movement_type', 'income')->sum('amount'));
        $yields = $this->sumMoney((clone $movementQuery)->where('movement_type', 'yield')->sum('amount'));
        $expenses = $this->sumMoney((clone $movementQuery)->where('movement_type', 'expense')->sum('amount'));
        $expectedLeftover = $this->money($income + $yields - $expenses);
        $paymentObligations = $this->monthObligations($user, $start, $end);
        $obligationTotals = $this->obligationTotals($paymentObligations);
        $pendingPayments = $obligationTotals['pending'];

        $latestCut = DailyCut::with('balances.account')
            ->where('user_id', $user->id)
            ->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('cut_date')
            ->first();

        $realTotal = $latestCut ? (float) $latestCut->real_total : 0.0;
        $difference = $latestCut ? $this->money($expectedLeftover - $realTotal) : null;
        $amountMissing = $latestCut ? $this->money($realTotal - $pendingPayments) : null;

        $rentIncome = $this->sumMoney((clone $movementQuery)->where('is_rent', true)->whereIn('movement_type', ['income', 'yield'])->sum('amount'));
        $sanJuanExpenses = $this->sumMoney((clone $movementQuery)->where('is_san_juan', true)->where('movement_type', 'expense')->sum('amount'));
        $unknownExpenses = $this->sumMoney((clone $movementQuery)->where('is_unknown', true)->where('movement_type', 'expense')->sum('amount'));

        $movements = (clone $movementQuery)
            ->with(['account', 'category', 'person'])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
        $incomeMovements = (clone $movementQuery)
            ->with(['account', 'category', 'person'])
            ->whereIn('movement_type', ['income', 'yield'])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $expenseMovements = (clone $movementQuery)
            ->with(['account', 'category', 'person'])
            ->where('movement_type', 'expense')
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $allExpenses = (clone $movementQuery)
            ->with('category')
            ->where('movement_type', 'expense')
            ->get();
        $gasoline = $this->gasolineExpenses($allExpenses);

        $nextExpectedIncomes = $this->nextExpectedIncomes($user, $start, $end);
        $pendingExpectedIncome = $this->money($nextExpectedIncomes->sum('amount_due'));
        $overdueExpectedIncome = $this->money($nextExpectedIncomes
            ->where('status', 'overdue')
            ->sum('amount_due'));

        return [
            'month' => $start,
            'month_value' => $start->format('Y-m'),
            'period_label' => $start->translatedFormat('F Y'),
            'income' => $income,
            'yields' => $yields,
            'total_income' => $this->money($income + $yields),
            'expenses' => $expenses,
            'expected_leftover' => $expectedLeftover,
            'pending_payments' => $pendingPayments,
            'latest_cut' => $latestCut,
            'real_total' => $realTotal,
            'difference' => $difference,
            'amount_missing' => $amountMissing,
            'rent_income' => $rentIncome,
            'san_juan_expenses' => $sanJuanExpenses,
            'san_juan_utility' => $this->money($rentIncome - $sanJuanExpenses),
            'unknown_expenses' => $unknownExpenses,
            'gasoline_expenses' => $gasoline['total'],
            'car_gasoline_expenses' => $gasoline['car'],
            'motorcycle_gasoline_expenses' => $gasoline['motorcycle'],
            'recent_movements' => $movements,
            'income_movements' => $incomeMovements,
            'expense_movements' => $expenseMovements,
            'expenses_by_category' => $this->expensesByCategory($allExpenses),
            'weekly_expenses' => $this->weeklyExpenses($allExpenses),
            'overdue_payments' => $this->overduePayments($user),
            'payment_obligations' => $paymentObligations,
            'obligation_totals' => $obligationTotals,
            'next_payments' => $this->nextPayments($paymentObligations),
            'skipped_obligations' => $paymentObligations->where('is_skipped', true)->values(),
            'next_expected_incomes' => $nextExpectedIncomes,
            'pending_expected_income' => $pendingExpectedIncome,
            'overdue_expected_income' => $overdueExpectedIncome,
            'projected_total_income' => $this->money($income + $yields + $pendingExpectedIncome),
            'important_expense_concepts' => $this->importantExpenseConcepts($allExpenses),
            'spending_opportunities' => $this->spendingOpportunities($allExpenses, $expenses),
            'daily_income_chart' => $this->dailyIncomeChart($user, $start, $end),
            'monthly_income_chart' => $this->monthlyIncomeChart($user, $start),
        ];
    }

    public function expectedThroughDate(User $user, Carbon $date): float
    {
        $start = $date->copy()->startOfMonth();

        $query = Movement::query()
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $date->toDateString()]);

        $income = $this->sumMoney((clone $query)->where('movement_type', 'income')->sum('amount'));
        $yields = $this->sumMoney((clone $query)->where('movement_type', 'yield')->sum('amount'));
        $expenses = $this->sumMoney((clone $query)->where('movement_type', 'expense')->sum('amount'));

        return $this->money($income + $yields - $expenses);
    }

    public function pendingForMonth(User $user, Carbon $start, Carbon $end): float
    {
        return $this->obligationTotals($this->monthObligations($user, $start, $end))['pending'];
    }

    public function monthObligations(User $user, Carbon $start, Carbon $end): Collection
    {
        $planned = PlannedPayment::with(['account', 'category', 'person', 'movement'])
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (PlannedPayment $payment) => $this->plannedObligation($payment));

        $credits = CreditInstallment::with(['creditPurchase.account', 'creditPurchase.category'])
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (CreditInstallment $installment) => $this->creditObligation($installment));

        return $planned
            ->merge($credits)
            ->sort(function (array $left, array $right) {
                $dateCompare = ($left['due_date']?->timestamp ?? PHP_INT_MAX) <=> ($right['due_date']?->timestamp ?? PHP_INT_MAX);

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return $left['name'] <=> $right['name'];
            })
            ->values();
    }

    public function obligationTotals(Collection $obligations): array
    {
        $pending = $obligations
            ->where('is_pending', true)
            ->sum(fn (array $obligation) => (float) $obligation['amount_due']);

        $paid = $obligations
            ->sum(fn (array $obligation) => (float) $obligation['paid_amount']);

        $overdue = $obligations
            ->where('is_pending', true)
            ->where('is_overdue', true)
            ->sum(fn (array $obligation) => (float) $obligation['amount_due']);

        $planned = $obligations
            ->where('source', 'planned')
            ->reject(fn (array $obligation) => $obligation['is_skipped'])
            ->sum(fn (array $obligation) => (float) $obligation['amount']);

        $credits = $obligations
            ->where('source', 'credit')
            ->reject(fn (array $obligation) => $obligation['is_skipped'])
            ->sum(fn (array $obligation) => (float) $obligation['amount']);

        $skipped = $obligations
            ->where('is_skipped', true)
            ->sum(fn (array $obligation) => (float) $obligation['amount']);

        return [
            'total' => $this->money($planned + $credits),
            'pending' => $this->money($pending),
            'paid' => $this->money($paid),
            'overdue' => $this->money($overdue),
            'planned' => $this->money($planned),
            'credits' => $this->money($credits),
            'skipped' => $this->money($skipped),
        ];
    }

    public function monthRange(?string $month = null): array
    {
        $date = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    private function expensesByCategory(Collection $expenses): Collection
    {
        return $expenses
            ->groupBy(fn (Movement $movement) => $movement->category?->name ?? 'Sin categoría')
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'amount' => $this->money($rows->sum(fn (Movement $movement) => (float) $movement->amount)),
            ])
            ->sortByDesc('amount')
            ->values();
    }

    private function weeklyExpenses(Collection $expenses): Collection
    {
        return $expenses
            ->groupBy(fn (Movement $movement) => 'Semana ' . $movement->happened_on->weekOfMonth)
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'amount' => $this->money($rows->sum(fn (Movement $movement) => (float) $movement->amount)),
            ])
            ->values();
    }

    private function gasolineExpenses(Collection $expenses): array
    {
        $gasoline = $expenses->filter(function (Movement $movement) {
            $category = Str::lower($movement->category?->name ?? '');
            $group = Str::lower($movement->category?->group ?? '');
            $description = Str::lower($movement->description ?? '');

            return $group === 'gasolina'
                || Str::contains($category, 'gasolina')
                || Str::contains($description, 'gasolina');
        });

        $motorcycle = $gasoline->filter(function (Movement $movement) {
            $category = Str::lower($movement->category?->name ?? '');
            $description = Str::lower($movement->description ?? '');

            return Str::contains($category, 'moto')
                || Str::contains($description, 'moto');
        });

        $motorcycleAmount = $this->money($motorcycle->sum(fn (Movement $movement) => (float) $movement->amount));
        $total = $this->money($gasoline->sum(fn (Movement $movement) => (float) $movement->amount));

        return [
            'total' => $total,
            'car' => $this->money($total - $motorcycleAmount),
            'motorcycle' => $motorcycleAmount,
        ];
    }

    private function overduePayments(User $user): Collection
    {
        return PlannedPayment::with(['category', 'person'])
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereDate('due_date', '<', today()->toDateString())
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }

    private function nextPayments(Collection $obligations): Collection
    {
        return $obligations
            ->where('is_pending', true)
            ->values();
    }

    private function plannedObligation(PlannedPayment $payment): array
    {
        $amount = (float) $payment->amount;
        $paidAmount = (float) $payment->paid_amount;
        $isSkipped = $payment->status === 'skipped';
        $isPaid = $payment->status === 'paid';
        $amountDue = $isSkipped || $isPaid ? 0.0 : max(0, $amount - $paidAmount);
        $isPending = in_array($payment->status, ['pending', 'overdue'], true) && $amountDue > 0;
        $isOverdue = $isPending && (
            $payment->status === 'overdue'
            || ($payment->due_date && $payment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
        );
        $status = $isOverdue ? 'overdue' : $payment->status;

        return [
            'source' => 'planned',
            'id' => $payment->id,
            'due_date' => $payment->due_date,
            'name' => $payment->name,
            'detail' => $payment->category?->name ?? 'Pago planeado',
            'account' => $payment->account?->name,
            'category' => $payment->category?->name,
            'person' => $payment->person?->name,
            'status' => $status,
            'stored_status' => $payment->status,
            'amount' => $this->money($amount),
            'paid_amount' => $this->money($paidAmount),
            'amount_due' => $this->money($amountDue),
            'kind' => 'Pago planeado',
            'origin' => 'Pago planeado',
            'origin_detail' => $this->originDetail($status, (bool) $payment->movement_id),
            'is_pending' => $isPending,
            'is_overdue' => $isOverdue,
            'is_paid' => $isPaid,
            'is_linked' => (bool) $payment->movement_id,
            'is_skipped' => $isSkipped,
        ];
    }

    private function creditObligation(CreditInstallment $installment): array
    {
        $credit = $installment->creditPurchase;
        $amount = (float) $installment->amount;
        $paidAmount = (float) $installment->paid_amount;
        $isSkipped = $installment->status === 'skipped';
        $isPaid = $installment->status === 'paid';
        $amountDue = $isSkipped || $isPaid ? 0.0 : max(0, $amount - $paidAmount);
        $isPending = in_array($installment->status, ['pending', 'overdue'], true) && $amountDue > 0;
        $isOverdue = $isPending && (
            $installment->status === 'overdue'
            || ($installment->due_date && $installment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
        );
        $status = $isOverdue ? 'overdue' : $installment->status;
        $months = $credit?->months ?? '-';

        return [
            'source' => 'credit',
            'id' => $installment->id,
            'due_date' => $installment->due_date,
            'name' => 'Crédito: ' . ($credit?->name ?? 'Sin nombre'),
            'detail' => 'Mensualidad ' . $installment->installment_number . ' / ' . $months,
            'credit_name' => $credit?->name ?? 'Sin nombre',
            'installment_number' => $installment->installment_number,
            'installment_total' => $months,
            'account' => $credit?->account?->name,
            'category' => $credit?->category?->name,
            'person' => null,
            'status' => $status,
            'stored_status' => $installment->status,
            'amount' => $this->money($amount),
            'paid_amount' => $this->money($paidAmount),
            'amount_due' => $this->money($amountDue),
            'kind' => 'Crédito',
            'origin' => 'Crédito',
            'origin_detail' => $this->originDetail($status, (bool) $installment->movement_id),
            'is_pending' => $isPending,
            'is_overdue' => $isOverdue,
            'is_paid' => $isPaid,
            'is_linked' => (bool) $installment->movement_id,
            'is_skipped' => $isSkipped,
        ];
    }

    private function originDetail(string $status, bool $linked): string
    {
        if ($status === 'skipped') {
            return 'No pagado / pendiente de decisión';
        }

        if ($status === 'paid') {
            return $linked ? 'Pagado/vinculado' : 'Pagado/registrado';
        }

        if ($status === 'overdue') {
            return 'Vencido pendiente';
        }

        return 'Pendiente';
    }

    private function nextExpectedIncomes(User $user, Carbon $start, Carbon $end): Collection
    {
        $expectedIncomes = ExpectedIncome::with(['category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'overdue'])
            ->get();

        $manualRentPersonIds = ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->where('is_rent', true)
            ->whereNotNull('person_id')
            ->pluck('person_id')
            ->all();

        $manualRows = $expectedIncomes
            ->map(function (ExpectedIncome $income) {
                $amountDue = $this->money(max(0, (float) $income->amount - (float) $income->received_amount));

                if ($amountDue <= 0) {
                    return null;
                }

                return [
                    'id' => $income->id,
                    'due_date' => $income->due_date,
                    'name' => $income->person?->name ?? $income->name,
                    'concept' => $income->person ? $income->name : ($income->category?->name ?? 'Ingreso esperado'),
                    'status' => $income->status,
                    'amount_due' => $amountDue,
                    'account_id' => $income->account_id,
                    'source' => 'manual',
                ];
            })
            ->filter()
            ->values()
            ->toBase();

        $rentMovements = Movement::with('person')
            ->where('user_id', $user->id)
            ->where('movement_type', 'income')
            ->where('is_rent', true)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->get();

        $rentalRows = RentalContract::with('person')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expected_amount', '>', 0)
            ->where(function ($query) use ($end) {
                $query->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $end->toDateString());
            })
            ->where(function ($query) use ($start) {
                $query->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $start->toDateString());
            })
            ->get()
            ->reject(fn (RentalContract $contract) => in_array($contract->person_id, $manualRentPersonIds, true))
            ->map(function (RentalContract $contract) use ($start, $rentMovements) {
                $personName = $contract->person?->name ?? 'Renta';
                $needle = Str::lower($personName);
                $paidAmount = $rentMovements
                    ->filter(fn (Movement $movement) => $movement->person_id === $contract->person_id
                        || Str::contains(Str::lower($movement->description), $needle))
                    ->sum(fn (Movement $movement) => (float) $movement->amount);
                $amountDue = $this->money(max(0, (float) $contract->expected_amount - (float) $paidAmount));

                if ($amountDue <= 0) {
                    return null;
                }

                $dueDay = $contract->due_day ?: 1;
                $dueDate = $start->copy()->day(min((int) $dueDay, $start->daysInMonth));

                return [
                    'contract_id' => $contract->id,
                    'due_date' => $dueDate,
                    'name' => $personName,
                    'concept' => $contract->room ? 'Renta cuarto ' . $contract->room : 'Renta San Juan',
                    'status' => $dueDate->lt(today()->startOfDay()) ? 'overdue' : 'pending',
                    'amount_due' => $amountDue,
                    'source' => 'rental-contract',
                ];
            })
            ->filter()
            ->values()
            ->toBase();

        return $manualRows
            ->merge($rentalRows)
            ->sort(function (array $left, array $right) {
                $dateCompare = ($left['due_date']?->timestamp ?? PHP_INT_MAX) <=> ($right['due_date']?->timestamp ?? PHP_INT_MAX);

                return $dateCompare !== 0 ? $dateCompare : $left['name'] <=> $right['name'];
            })
            ->values();
    }

    private function dailyIncomeChart(User $user, Carbon $start, Carbon $end): array
    {
        $dailyTotals = Movement::query()
            ->where('user_id', $user->id)
            ->whereIn('movement_type', ['income', 'yield'])
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('happened_on, SUM(amount) as total')
            ->groupBy('happened_on')
            ->pluck('total', 'happened_on');

        $labels = [];
        $values = [];
        $running = 0.0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $running += (float) ($dailyTotals[$date->toDateString()] ?? 0);
            $labels[] = $date->format('d');
            $values[] = $this->money($running);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'max' => max(1, (float) max($values ?: [0])),
        ];
    }

    private function monthlyIncomeChart(User $user, Carbon $month): array
    {
        $yearStart = $month->copy()->startOfYear();
        $yearEnd = $month->copy()->endOfMonth();

        $monthlyTotals = Movement::query()
            ->where('user_id', $user->id)
            ->whereIn('movement_type', ['income', 'yield'])
            ->whereBetween('happened_on', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->get()
            ->groupBy(fn (Movement $movement) => $movement->happened_on->format('Y-m'))
            ->map(fn (Collection $rows) => $this->money($rows->sum(fn (Movement $movement) => (float) $movement->amount)));

        $labels = [];
        $values = [];
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        for ($date = $yearStart->copy(); $date->lte($yearEnd); $date->addMonth()) {
            $labels[] = $monthNames[((int) $date->format('n')) - 1];
            $values[] = $this->money((float) ($monthlyTotals[$date->format('Y-m')] ?? 0));
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'max' => max(1, (float) max($values ?: [0])),
        ];
    }

    private function sumMoney(mixed $value): float
    {
        return $this->money((float) $value);
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }

    private function importantExpenseConcepts(Collection $expenses): Collection
    {
        $concepts = collect([
            ['name' => 'Saldo / Telefonía', 'color' => '#06b6d4', 'keywords' => ['saldo', 'telcel', 'weex', 'recarga', 'telefono', 'telefonia']],
            ['name' => 'Comida', 'color' => '#f97316', 'keywords' => ['comida', 'taqueria', 'uber eats', 'uber comida', 'didi comida', 'rappi', 'oxxo', 'starbucks', 'restaurante']],
            ['name' => 'Transporte', 'color' => '#0ea5e9', 'keywords' => ['transporte', 'uber carro', 'didi carro', 'caseta', 'pase', 'taxi']],
            ['name' => 'Gasolina', 'color' => '#ef4444', 'keywords' => ['gasolina', 'costco gasolina', 'gasolina moto', 'gasolina carro']],
            ['name' => 'Servicios', 'color' => '#6366f1', 'keywords' => ['japam', 'luz', 'agua', 'internet', 'totalplay', 'telmex', 'google one', 'youtube', 'amazon music', 'servicio']],
            ['name' => 'Ropa', 'color' => '#ec4899', 'keywords' => ['ropa', 'zapato', 'playera', 'pantalon', 'shein', 'zara', 'tenis']],
            ['name' => 'Casa', 'color' => '#64748b', 'keywords' => ['casa', 'limpieza', 'cloro', 'jabon', 'escoba', 'artemias', 'mandado']],
            ['name' => 'Créditos / tarjetas', 'color' => '#7c3aed', 'keywords' => ['credito', 'creditos', 'tarjeta', 'nu credito', 'didi credito', 'mpw credito', 'mercado libre']],
            ['name' => 'San Juan', 'color' => '#dc3545', 'keywords' => ['san juan', 'snj', 'japam', 'jorge']],
        ]);

        return $concepts
            ->map(function (array $concept) use ($expenses) {
                $rows = $expenses->filter(function (Movement $movement) use ($concept) {
                    if ($concept['name'] === 'San Juan' && $movement->is_san_juan) {
                        return true;
                    }

                    return $this->matchesAny($this->movementSearchText($movement), $concept['keywords']);
                });

                return [
                    'name' => $concept['name'],
                    'color' => $concept['color'],
                    'amount' => $this->money($rows->sum(fn (Movement $movement) => (float) $movement->amount)),
                    'count' => $rows->count(),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->sortByDesc('amount')
            ->values();
    }

    private function spendingOpportunities(Collection $expenses, float $totalExpenses): Collection
    {
        if ($totalExpenses <= 0) {
            return collect();
        }

        return $this->importantExpenseConcepts($expenses)
            ->take(5)
            ->map(function (array $row) use ($totalExpenses) {
                $suggestedCut = $this->money(((float) $row['amount']) * 0.10);

                return $row + [
                    'percentage' => round((((float) $row['amount']) / $totalExpenses) * 100, 1),
                    'suggestion' => $suggestedCut > 0
                        ? 'Si bajas 10% este concepto podrías liberar ' . '$' . number_format($suggestedCut, 2) . ' este mes.'
                        : 'Sigue vigilando este concepto para mantener controlado el egreso.',
                ];
            })
            ->values();
    }

    private function movementSearchText(Movement $movement): string
    {
        return Str::lower(implode(' ', array_filter([
            $movement->description,
            $movement->notes,
            $movement->category?->name,
            $movement->category?->group,
            $movement->category?->keywords,
            $movement->person?->name,
        ])));
    }

    private function matchesAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (Str::contains($text, Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
