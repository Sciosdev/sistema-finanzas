<?php

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceSurvivalBudgetService
{
    private const HISTORY_DAYS = 60;

    private const DEFAULT_BUCKETS = [
        ['name' => 'Comida / tienda / despensa', 'weight' => 45.0, 'keywords' => ['comida', 'tienda', 'supermercado', 'despensa']],
        ['name' => 'Gasolina / transporte', 'weight' => 25.0, 'keywords' => ['gasolina', 'transporte']],
        ['name' => 'Casetas', 'weight' => 10.0, 'keywords' => ['caseta', 'casetas']],
        ['name' => 'Libre / otros', 'weight' => 15.0, 'keywords' => ['libre', 'otros']],
        ['name' => 'Imprevistos', 'weight' => 5.0, 'keywords' => ['imprevisto', 'imprevistos', 'farmacia']],
    ];

    public function __construct(
        private readonly FinanceProjectionService $projectionService,
        private readonly FinancePaymentRecommendationService $recommendationService
    ) {}

    public function build(User $user, int $horizonDays = 30): array
    {
        if (! in_array($horizonDays, FinanceProjectionService::HORIZONS, true)) {
            $horizonDays = 30;
        }

        $projection = $this->projectionService->project($user, $horizonDays);
        $this->recommendationService->recommend($user, $horizonDays, $projection);

        $start = today()->startOfDay();
        $nextIncome = $this->nextIncome($user, $start);
        $end = $nextIncome
            ? $this->endBeforeIncome($start, $nextIncome['date'])
            : $start->copy()->addDays(14);
        $daysCount = max(1, ((int) $start->diffInDays($end)) + 1);

        $obligations = $this->obligationsFromProjection($projection, $start, $end);
        $startingBalance = $this->money((float) ($projection['meta']['starting_balance'] ?? 0));
        $buffer = $this->money((float) ($projection['meta']['buffer'] ?? 0));
        $rawSurvivalPool = $this->money($startingBalance - $obligations['total'] - $buffer);
        $survivalPool = $this->money(max(0, $rawSurvivalPool));
        $shortfall = $this->money(max(0, -$rawSurvivalPool));
        $dailyTotalAllowance = $this->money($survivalPool / $daysCount);

        $historicalRows = $this->historicalRows($user, $start);
        $hasHistory = $historicalRows->sum('historical_spent') > 0;
        $categoryBudgets = $hasHistory
            ? $this->historicalBudgetRows($user, $historicalRows, $start, $end, $survivalPool, $daysCount)
            : $this->defaultBudgetRows($user, $start, $end, $survivalPool, $daysCount);

        $totalAssigned = $this->money(array_sum(array_column($categoryBudgets, 'budget_total')));

        return [
            'window' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days_count' => $daysCount,
                'next_income_date' => $nextIncome ? $nextIncome['date']->toDateString() : null,
                'next_income_name' => $nextIncome['name'] ?? null,
                'next_income_amount' => $nextIncome['amount'] ?? 0.0,
                'window_reason' => $nextIncome
                    ? 'Hasta el siguiente ingreso esperado.'
                    : 'No hay ingreso esperado proximo; se uso ventana de 15 dias.',
                'has_next_income' => (bool) $nextIncome,
            ],
            'money' => [
                'starting_balance' => $startingBalance,
                'obligations_total' => $obligations['total'],
                'overdue_obligations_total' => $obligations['overdue'],
                'upcoming_obligations_total' => $obligations['upcoming'],
                'buffer' => $buffer,
                'raw_survival_pool' => $rawSurvivalPool,
                'survival_pool' => $survivalPool,
                'shortfall_for_survival' => $shortfall,
                'daily_total_allowance' => $dailyTotalAllowance,
            ],
            'categories' => $categoryBudgets,
            'summary' => [
                'total_categories' => count($categoryBudgets),
                'total_assigned' => $totalAssigned,
                'unassigned_amount' => $this->money($survivalPool - $totalAssigned),
                'has_historical_basis' => $hasHistory,
            ],
            'alerts' => [
                'missing_next_income' => ! $nextIncome,
                'insufficient_history' => ! $hasHistory,
                'obligations_exceed_balance' => $obligations['total'] > $startingBalance,
                'buffer_at_risk' => $rawSurvivalPool < 0,
            ],
            'messages' => $this->messages($end, $daysCount, $survivalPool, $shortfall, $dailyTotalAllowance, $obligations['total'], $startingBalance, (bool) $nextIncome, $hasHistory, $categoryBudgets),
        ];
    }

    private function nextIncome(User $user, Carbon $start): ?array
    {
        return ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->map(function (ExpectedIncome $income) {
                $residual = $this->money(max(0, (float) $income->amount - (float) $income->received_amount));

                if ($residual <= 0) {
                    return null;
                }

                return [
                    'date' => $income->due_date->copy()->startOfDay(),
                    'name' => $income->name,
                    'amount' => $residual,
                ];
            })
            ->filter()
            ->first();
    }

    private function endBeforeIncome(Carbon $start, Carbon $incomeDate): Carbon
    {
        if ($incomeDate->isSameDay($start)) {
            return $start->copy();
        }

        return $incomeDate->copy()->subDay()->startOfDay();
    }

    private function obligationsFromProjection(array $projection, Carbon $start, Carbon $end): array
    {
        $total = 0.0;
        $overdue = 0.0;

        foreach ($projection['days'] ?? [] as $day) {
            $date = Carbon::parse($day['date'])->startOfDay();

            if ($date->lt($start) || $date->gt($end)) {
                continue;
            }

            foreach (array_merge($day['payments'] ?? [], $day['installments'] ?? []) as $event) {
                $amount = $this->money((float) ($event['amount'] ?? 0));
                $total = $this->money($total + $amount);

                if ((bool) ($event['is_overdue'] ?? false)) {
                    $overdue = $this->money($overdue + $amount);
                }
            }
        }

        return [
            'total' => $total,
            'overdue' => $overdue,
            'upcoming' => $this->money($total - $overdue),
        ];
    }

    private function historicalRows(User $user, Carbon $start): Collection
    {
        $historyStart = $start->copy()->subDays(self::HISTORY_DAYS);
        $historyEnd = $start->copy()->subDay();

        return Movement::query()
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
            ->reject(fn (Movement $movement) => $this->isDebtCategory($movement->category))
            ->groupBy('category_id')
            ->map(function (Collection $movements) {
                $category = $movements->first()->category;
                $spent = $this->money($movements->sum(fn (Movement $movement) => (float) $movement->amount));

                return [
                    'category_id' => $category?->id,
                    'category_name' => $category?->name ?? 'Sin categoria',
                    'historical_spent' => $spent,
                    'average_daily_spend' => $this->money($spent / self::HISTORY_DAYS),
                ];
            })
            ->values();
    }

    private function historicalBudgetRows(User $user, Collection $historicalRows, Carbon $start, Carbon $end, float $survivalPool, int $daysCount): array
    {
        $historicalTotal = $this->money($historicalRows->sum('historical_spent'));

        return $historicalRows
            ->map(function (array $row) use ($user, $start, $end, $survivalPool, $daysCount, $historicalTotal) {
                $weight = $historicalTotal > 0 ? (((float) $row['historical_spent'] / $historicalTotal) * 100) : 0;

                return $this->budgetRow(
                    $user,
                    $row['category_id'],
                    $row['category_name'],
                    $weight,
                    $survivalPool,
                    $daysCount,
                    $start,
                    $end,
                    (float) $row['historical_spent'],
                    (float) $row['average_daily_spend']
                );
            })
            ->sortByDesc('budget_total')
            ->values()
            ->all();
    }

    private function defaultBudgetRows(User $user, Carbon $start, Carbon $end, float $survivalPool, int $daysCount): array
    {
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->get();

        return collect(self::DEFAULT_BUCKETS)
            ->map(function (array $bucket) use ($user, $start, $end, $survivalPool, $daysCount, $categories) {
                $category = $this->matchingCategory($categories, $bucket['keywords']);

                return $this->budgetRow(
                    $user,
                    $category?->id,
                    $category?->name ?? $bucket['name'],
                    (float) $bucket['weight'],
                    $survivalPool,
                    $daysCount,
                    $start,
                    $end,
                    0.0,
                    0.0
                );
            })
            ->values()
            ->all();
    }

    private function budgetRow(
        User $user,
        ?int $categoryId,
        string $categoryName,
        float $weightPercent,
        float $survivalPool,
        int $daysCount,
        Carbon $start,
        Carbon $end,
        float $historicalSpent,
        float $averageDailySpend
    ): array {
        $budgetTotal = $this->money($survivalPool * ($weightPercent / 100));
        $dailyAllowance = $daysCount > 0 ? $this->money($budgetTotal / $daysCount) : 0.0;
        $spentInWindow = $categoryId ? $this->alreadySpentInWindow($user, $categoryId, $start, $end) : 0.0;
        $remaining = $this->money($budgetTotal - $spentInWindow);
        $recommendedToday = $survivalPool > 0
            ? $this->money(max(0, $remaining) / $daysCount)
            : 0.0;

        return [
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'weight_percent' => round($weightPercent, 2),
            'historical_percent' => $historicalSpent > 0 ? round($weightPercent, 2) : 0.0,
            'budget_total' => $budgetTotal,
            'daily_allowance' => $dailyAllowance,
            'days_remaining' => $daysCount,
            'already_spent_in_window' => $spentInWindow,
            'remaining_for_category' => $remaining,
            'recommended_today' => $recommendedToday,
            'historical_spent' => $this->money($historicalSpent),
            'average_daily_spend' => $this->money($averageDailySpend),
            'message' => $survivalPool > 0
                ? $categoryName.': intenta no gastar mas de '.$this->formatMoney($recommendedToday).' diarios hasta tu proximo ingreso.'
                : $categoryName.': no hay dinero libre recomendado para gastar sin romper pagos o colchon.',
        ];
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

    private function matchingCategory(Collection $categories, array $keywords): ?Category
    {
        return $categories->first(function (Category $category) use ($keywords) {
            $text = $this->categorySearchText($category);

            foreach ($keywords as $keyword) {
                if (Str::contains($text, Str::lower(Str::ascii($keyword)))) {
                    return true;
                }
            }

            return false;
        });
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

    private function isDebtCategory(?Category $category): bool
    {
        return Str::contains($this->categorySearchText($category), [
            'deuda',
            'credito',
            'creditos',
            'tarjeta',
            'prestamo',
            'mensualidad',
        ]);
    }

    private function messages(
        Carbon $end,
        int $daysCount,
        float $survivalPool,
        float $shortfall,
        float $dailyTotalAllowance,
        float $obligationsTotal,
        float $startingBalance,
        bool $hasNextIncome,
        bool $hasHistory,
        array $categoryBudgets
    ): array {
        $messages = [
            'De hoy al '.$end->format('d/m/Y').' tienes que sobrevivir '.$daysCount.' dias.',
        ];

        if ($survivalPool <= 0) {
            $messages[] = 'No hay dinero libre para gastar sin romper pagos o colchon. Te faltan '.$this->formatMoney($shortfall).'.';
        } else {
            $messages[] = 'Despues de pagos y colchon, te quedan '.$this->formatMoney($survivalPool).' para vivir.';
            $messages[] = 'Tu gasto maximo sugerido por dia es '.$this->formatMoney($dailyTotalAllowance).'.';
        }

        if ($obligationsTotal > $startingBalance) {
            $messages[] = 'Tus pagos superan tu saldo disponible antes de considerar comida/gastos diarios.';
        }

        if (! $hasNextIncome) {
            $messages[] = 'No hay proximo ingreso capturado; la recomendacion usa 15 dias por default.';
        }

        if (! $hasHistory) {
            $messages[] = 'No hay historial suficiente; se uso una distribucion base de supervivencia.';
        }

        foreach (array_slice($categoryBudgets, 0, 3) as $row) {
            if ($row['recommended_today'] > 0) {
                $messages[] = 'No gastes mas de '.$this->formatMoney($row['recommended_today']).' diarios en '.$row['category_name'].'.';
            }
        }

        return $messages;
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
