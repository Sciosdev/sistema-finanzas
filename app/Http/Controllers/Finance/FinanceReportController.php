<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Category;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\RentalContract;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceCsvExportService;
use App\Services\Finance\FinanceSummaryService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FinanceReportController extends Controller
{
    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceCsvExportService $csvExports,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
        ]);

        $monthValue = $data['month'] ?? now()->format('Y-m');
        [$monthStart, $monthEnd] = $this->summaryService->monthRange($monthValue);
        $year = (int) ($data['year'] ?? $monthStart->year);
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();
        $selectedCategory = isset($data['category_id'])
            ? Category::where('user_id', $user->id)->where('type', 'expense')->find($data['category_id'])
            : null;
        $monthMovements = Movement::query()
            ->with(['category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $monthExpenseMovements = $monthMovements->where('movement_type', 'expense')->values();
        $expenseCategoryRows = $this->expenseCategoryRows($monthExpenseMovements);
        $monthlyRows = $this->periodRows($user, $this->monthlyPeriods($yearStart));
        $obligations = $this->summaryService->monthObligations($user, $monthStart, $monthEnd);

        return view('finance.reports.index', [
            'monthValue' => $monthStart->format('Y-m'),
            'yearValue' => $year,
            'selectedCategory' => $selectedCategory,
            'monthTotals' => $this->totalsFromMovements($monthMovements),
            'yearTotals' => $this->totalsForRange($user, $yearStart, $yearEnd),
            'expenseCategoryRows' => $expenseCategoryRows,
            'expenseConceptRows' => $this->expenseConceptRows($monthExpenseMovements, $selectedCategory),
            'importantConceptRows' => $this->importantConceptRows($monthExpenseMovements),
            'spendingOpportunityRows' => $this->spendingOpportunityRows($monthExpenseMovements),
            'dailyRows' => $this->periodRows($user, $this->dailyPeriods($monthStart, $monthEnd)),
            'weeklyRows' => $this->periodRows($user, $this->weeklyPeriods($monthStart, $monthEnd)),
            'fortnightRows' => $this->periodRows($user, $this->fortnightPeriods($monthStart, $monthEnd)),
            'monthlyRows' => $monthlyRows,
            'yearlyRows' => $this->periodRows($user, $this->yearlyPeriods($user, $year)),
            'reportChartData' => $this->reportChartData($user, $monthStart, $monthEnd, $monthMovements, $expenseCategoryRows, $monthlyRows, $obligations),
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
        ]);

        $monthValue = $data['month'] ?? now()->format('Y-m');
        [$monthStart, $monthEnd] = $this->summaryService->monthRange($monthValue);
        $selectedCategory = isset($data['category_id'])
            ? Category::where('user_id', $user->id)->find($data['category_id'])
            : null;

        $movements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ->orderBy('happened_on')
            ->orderBy('id')
            ->get();

        $metadata = [
            'Reporte' => 'Reporte financiero',
            'Mes' => $monthStart->format('Y-m'),
            'Categoría' => $selectedCategory?->name ?? 'Todas',
        ];
        $format = $request->query('format') === 'xlsx' ? 'xlsx' : 'csv';
        $result = $format === 'xlsx'
            ? $this->csvExports->exportMovementsXlsx('reporte-financiero-' . $monthStart->format('Y-m'), $movements, $metadata)
            : $this->csvExports->exportMovements('reporte-financiero-' . $monthStart->format('Y-m'), $movements, $metadata);

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No se pudo exportar el reporte.');
        }

        return response()->download($result['absolute_path'], $result['name'], [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8',
        ]);
    }

    private function totalsForRange(User $user, Carbon $start, Carbon $end): array
    {
        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->get();

        return $this->totalsFromMovements($movements);
    }

    private function expenseMovementsForRange(User $user, Carbon $start, Carbon $end): Collection
    {
        return Movement::query()
            ->with(['category', 'person'])
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->get();
    }

    private function reportChartData(User $user, Carbon $monthStart, Carbon $monthEnd, Collection $monthMovements, Collection $expenseCategoryRows, Collection $monthlyRows, Collection $obligations): array
    {
        $colors = [
            'income' => '#22c55e',
            'yield' => '#38bdf8',
            'expense' => '#ef4444',
            'planned' => '#3b82f6',
            'credit' => '#8b5cf6',
            'pending' => '#f59e0b',
            'overdue' => '#dc2626',
            'paid' => '#22c55e',
            'net' => '#60a5fa',
        ];
        $realDistributionRows = collect([
            [
                'name' => 'Ingresos',
                'amount' => $this->sumByMovementType($monthMovements, ['income']),
                'color' => $colors['income'],
            ],
            [
                'name' => 'Rendimientos',
                'amount' => $this->sumByMovementType($monthMovements, ['yield']),
                'color' => $colors['yield'],
            ],
            [
                'name' => 'Egresos',
                'amount' => $this->sumByMovementType($monthMovements, ['expense']),
                'color' => $colors['expense'],
            ],
        ])->filter(fn (array $row) => $row['amount'] > 0)->values();
        $obligationTotals = $this->summaryService->obligationTotals($obligations);
        $expectedIncomeStatus = $this->expectedIncomeStatusForMonth($user, $monthStart, $monthEnd);
        $obligationPendingClean = max(0, (float) $obligationTotals['pending'] - (float) $obligationTotals['overdue']);

        return [
            'colors' => $colors,
            'realDistribution' => [
                'title' => 'Distribucion real del mes',
                'rows' => $realDistributionRows,
            ],
            'expenseCategories' => [
                'title' => 'Egresos por categoria',
                'rows' => $expenseCategoryRows->take(10)->values(),
            ],
            'obligationMix' => [
                'title' => 'Obligaciones del mes',
                'rows' => collect([
                    ['name' => 'Flujo planeado', 'amount' => (float) $obligationTotals['planned'], 'color' => $colors['planned']],
                    ['name' => 'Creditos', 'amount' => (float) $obligationTotals['credits'], 'color' => $colors['credit']],
                ])->filter(fn (array $row) => $row['amount'] > 0)->values(),
            ],
            'topIncome' => [
                'title' => 'Top ingresos',
                'rows' => $this->topIncomeRows($monthMovements)->take(8)->values(),
            ],
            'topExpenses' => [
                'title' => 'Top egresos',
                'rows' => $expenseCategoryRows->take(8)->values(),
            ],
            'yearPerspective' => [
                'title' => 'Ano en perspectiva',
                'labels' => $monthlyRows->pluck('label')->values(),
                'series' => [
                    ['name' => 'Ingresos', 'data' => $monthlyRows->pluck('income')->map(fn ($value) => (float) $value)->values()],
                    ['name' => 'Egresos', 'data' => $monthlyRows->pluck('expenses')->map(fn ($value) => (float) $value)->values()],
                    ['name' => 'Utilidad', 'data' => $monthlyRows->pluck('net')->map(fn ($value) => (float) $value)->values()],
                ],
                'colors' => [$colors['income'], $colors['expense'], $colors['net']],
            ],
            'coverage' => [
                'title' => 'Cobertura del mes',
                'labels' => ['Ingresos esperados', 'Obligaciones'],
                'series' => [
                    ['name' => 'Recibido/Pagado', 'data' => [(float) $expectedIncomeStatus['received'], (float) $obligationTotals['paid']]],
                    ['name' => 'Pendiente', 'data' => [(float) $expectedIncomeStatus['pending'], $obligationPendingClean]],
                    ['name' => 'Vencido', 'data' => [(float) $expectedIncomeStatus['overdue'], (float) $obligationTotals['overdue']]],
                    ['name' => 'No pagado', 'data' => [0, (float) $obligationTotals['skipped']]],
                ],
                'colors' => [$colors['paid'], $colors['pending'], $colors['overdue'], $colors['overdue']],
            ],
            'expectedIncomeStatus' => $expectedIncomeStatus,
            'obligationStatus' => [
                'paid' => (float) $obligationTotals['paid'],
                'pending' => $obligationPendingClean,
                'overdue' => (float) $obligationTotals['overdue'],
                'skipped' => (float) $obligationTotals['skipped'],
            ],
        ];
    }

    private function sumByMovementType(Collection $movements, array $types): float
    {
        return round($movements
            ->whereIn('movement_type', $types)
            ->sum(fn (Movement $movement) => (float) $movement->amount), 2);
    }

    private function topIncomeRows(Collection $movements): Collection
    {
        $incomeMovements = $movements->filter(fn (Movement $movement) => in_array($movement->movement_type, ['income', 'yield'], true));
        $total = (float) $incomeMovements->sum(fn (Movement $movement) => (float) $movement->amount);

        return $incomeMovements
            ->groupBy(function (Movement $movement) {
                if ($movement->movement_type === 'yield') {
                    return 'yield';
                }

                if ($movement->category_id) {
                    return 'category:' . $movement->category_id;
                }

                if ($movement->person_id) {
                    return 'person:' . $movement->person_id;
                }

                return 'none';
            })
            ->map(function (Collection $rows) use ($total) {
                $first = $rows->first();
                $amount = round($rows->sum(fn (Movement $movement) => (float) $movement->amount), 2);
                $name = match (true) {
                    $first->movement_type === 'yield' => 'Rendimientos',
                    (bool) $first->category => $first->category->name,
                    (bool) $first->person => $first->person->name,
                    default => 'Ingresos sin categoria',
                };
                $color = $first->movement_type === 'yield'
                    ? '#38bdf8'
                    : $this->safeColor($first->category?->color ?: '#22c55e');

                return [
                    'name' => $name,
                    'amount' => $amount,
                    'count' => $rows->count(),
                    'percentage' => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
                    'color' => $color,
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    private function expectedIncomeStatusForMonth(User $user, Carbon $start, Carbon $end): array
    {
        $received = 0.0;
        $pending = 0.0;
        $overdue = 0.0;
        $expectedIncomes = ExpectedIncome::with('payments')
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->get();

        foreach ($expectedIncomes as $income) {
            if ($income->status === 'skipped') {
                continue;
            }

            $amount = (float) $income->amount;
            $receivedAmount = min((float) $income->received_amount, $amount);
            $remaining = round(max(0, $amount - $receivedAmount), 2);
            $received += $receivedAmount;

            if ($remaining <= 0) {
                continue;
            }

            if ($income->status === 'overdue' || ($income->due_date && $income->due_date->copy()->startOfDay()->lt(today()->startOfDay()))) {
                $overdue += $remaining;
            } else {
                $pending += $remaining;
            }
        }

        $manualRentPersonIds = $expectedIncomes
            ->where('is_rent', true)
            ->whereNotNull('person_id')
            ->pluck('person_id')
            ->all();
        $rentMovements = Movement::with('person')
            ->where('user_id', $user->id)
            ->where('movement_type', 'income')
            ->where('is_rent', true)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->get();

        RentalContract::with('person')
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
            ->each(function (RentalContract $contract) use ($start, $rentMovements, &$received, &$pending, &$overdue) {
                $personName = $contract->person?->name ?? 'Renta';
                $needle = Str::lower($personName);
                $expected = round((float) $contract->expected_amount, 2);
                $paid = round($rentMovements
                    ->filter(fn (Movement $movement) => $movement->person_id === $contract->person_id
                        || Str::contains(Str::lower($movement->description), $needle))
                    ->sum(fn (Movement $movement) => (float) $movement->amount), 2);
                $received += min($paid, $expected);
                $remaining = round(max(0, $expected - $paid), 2);

                if ($remaining <= 0) {
                    return;
                }

                $dueDay = $contract->due_day ?: 1;
                $dueDate = $start->copy()->day(min((int) $dueDay, $start->daysInMonth));

                if ($dueDate->lt(today()->startOfDay())) {
                    $overdue += $remaining;
                } else {
                    $pending += $remaining;
                }
            });

        return [
            'received' => round($received, 2),
            'pending' => round($pending, 2),
            'overdue' => round($overdue, 2),
            'total' => round($received + $pending + $overdue, 2),
        ];
    }

    private function expenseCategoryRows(Collection $movements): Collection
    {
        $total = (float) $movements->sum(fn (Movement $movement) => (float) $movement->amount);

        return $movements
            ->groupBy(fn (Movement $movement) => $movement->category_id ?: 'none')
            ->map(function (Collection $rows) use ($total) {
                $first = $rows->first();
                $amount = round($rows->sum(fn (Movement $movement) => (float) $movement->amount), 2);
                $category = $first->category;

                return [
                    'category_id' => $category?->id,
                    'name' => $category?->name ?: 'Sin categoría',
                    'group' => $category?->group ?: 'Sin grupo',
                    'color' => $this->safeColor($category?->color),
                    'amount' => $amount,
                    'count' => $rows->count(),
                    'percentage' => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    private function expenseConceptRows(Collection $movements, ?Category $selectedCategory): Collection
    {
        $filtered = $selectedCategory
            ? $movements->where('category_id', $selectedCategory->id)
            : $movements;
        $total = (float) $filtered->sum(fn (Movement $movement) => (float) $movement->amount);

        return $filtered
            ->groupBy(fn (Movement $movement) => $this->conceptLabel($movement))
            ->map(function (Collection $rows, string $concept) use ($total) {
                $amount = round($rows->sum(fn (Movement $movement) => (float) $movement->amount), 2);

                return [
                    'name' => $concept,
                    'category' => $rows->first()->category?->name ?: 'Sin categoría',
                    'amount' => $amount,
                    'count' => $rows->count(),
                    'percentage' => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
                    'last_date' => $rows->max(fn (Movement $movement) => $movement->happened_on?->format('Y-m-d')),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->take(15);
    }

    private function importantConceptRows(Collection $movements): Collection
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
            ->map(function (array $concept) use ($movements) {
                $rows = $movements->filter(function (Movement $movement) use ($concept) {
                    if ($concept['name'] === 'San Juan' && $movement->is_san_juan) {
                        return true;
                    }

                    return $this->matchesAny($this->movementSearchText($movement), $concept['keywords']);
                });
                $amount = round($rows->sum(fn (Movement $movement) => (float) $movement->amount), 2);

                return [
                    'name' => $concept['name'],
                    'color' => $concept['color'],
                    'amount' => $amount,
                    'count' => $rows->count(),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->sortByDesc('amount')
            ->values();
    }

    private function spendingOpportunityRows(Collection $movements): Collection
    {
        $total = round($movements->sum(fn (Movement $movement) => (float) $movement->amount), 2);

        if ($total <= 0) {
            return collect();
        }

        return $this->importantConceptRows($movements)
            ->take(6)
            ->map(function (array $row) use ($total) {
                $suggestedCut = round(((float) $row['amount']) * 0.10, 2);

                return $row + [
                    'percentage' => round((((float) $row['amount']) / $total) * 100, 1),
                    'recommendation' => 'Revisa este concepto; bajar 10% liberaría $' . number_format($suggestedCut, 2) . ' este mes.',
                ];
            })
            ->values();
    }

    private function periodRows(User $user, Collection $periods): Collection
    {
        if ($periods->isEmpty()) {
            return collect();
        }

        $rangeStart = $periods->min(fn (array $period) => $period['start'])->toDateString();
        $rangeEnd = $periods->max(fn (array $period) => $period['end'])->toDateString();
        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$rangeStart, $rangeEnd])
            ->get();

        return $periods->map(function (array $period) use ($movements) {
            $periodMovements = $movements->filter(fn (Movement $movement) => $movement->happened_on->gte($period['start'])
                && $movement->happened_on->lte($period['end']));
            $totals = $this->totalsFromMovements($periodMovements);

            return $period + $totals;
        });
    }

    private function totalsFromMovements(Collection $movements): array
    {
        $income = round($movements
            ->whereIn('movement_type', ['income', 'yield'])
            ->sum(fn (Movement $movement) => (float) $movement->amount), 2);
        $expenses = round($movements
            ->where('movement_type', 'expense')
            ->sum(fn (Movement $movement) => (float) $movement->amount), 2);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'net' => round($income - $expenses, 2),
        ];
    }

    private function conceptLabel(Movement $movement): string
    {
        $description = trim((string) $movement->description);

        if ($description !== '') {
            return mb_strimwidth($description, 0, 60, '...');
        }

        return $movement->category?->name ?: 'Sin concepto';
    }

    private function movementSearchText(Movement $movement): string
    {
        return mb_strtolower(implode(' ', array_filter([
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
            if (str_contains($text, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function safeColor(?string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color) ? $color : '#64748b';
    }

    private function dailyPeriods(Carbon $start, Carbon $end): Collection
    {
        $periods = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $periods->push([
                'label' => $date->format('Y-m-d'),
                'range' => $date->format('d/m'),
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ]);
        }

        return $periods;
    }

    private function weeklyPeriods(Carbon $start, Carbon $end): Collection
    {
        $periods = collect();
        $index = 1;

        for ($date = $start->copy(); $date->lte($end); $date = $periodEnd->copy()->addDay()) {
            $periodEnd = $date->copy()->addDays(6);

            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }

            $periods->push([
                'label' => 'Semana ' . $index,
                'range' => $date->format('d/m') . ' - ' . $periodEnd->format('d/m'),
                'start' => $date->copy()->startOfDay(),
                'end' => $periodEnd->copy()->endOfDay(),
            ]);

            $index++;
        }

        return $periods;
    }

    private function fortnightPeriods(Carbon $start, Carbon $end): Collection
    {
        $firstEnd = $start->copy()->day(min(15, $start->daysInMonth));
        $secondStart = $firstEnd->copy()->addDay();

        return collect([
            [
                'label' => 'Quincena 1',
                'range' => $start->format('d/m') . ' - ' . $firstEnd->format('d/m'),
                'start' => $start->copy()->startOfDay(),
                'end' => $firstEnd->copy()->endOfDay(),
            ],
            [
                'label' => 'Quincena 2',
                'range' => $secondStart->format('d/m') . ' - ' . $end->format('d/m'),
                'start' => $secondStart->copy()->startOfDay(),
                'end' => $end->copy()->endOfDay(),
            ],
        ]);
    }

    private function monthlyPeriods(Carbon $yearStart): Collection
    {
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return collect(range(1, 12))->map(function (int $month) use ($yearStart, $monthNames) {
            $date = $yearStart->copy()->month($month)->startOfMonth();

            return [
                'label' => $monthNames[$month - 1] . ' ' . $date->format('Y'),
                'range' => $date->format('Y-m'),
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ];
        });
    }

    private function yearlyPeriods(User $user, int $selectedYear): Collection
    {
        $years = Movement::query()
            ->where('user_id', $user->id)
            ->pluck('happened_on')
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->push($selectedYear)
            ->unique()
            ->sort()
            ->values();

        return $years->map(fn (int $year) => [
            'label' => (string) $year,
            'range' => (string) $year,
            'start' => Carbon::create($year, 1, 1)->startOfDay(),
            'end' => Carbon::create($year, 12, 31)->endOfDay(),
        ]);
    }
}
