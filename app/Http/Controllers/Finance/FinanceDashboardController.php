<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceCutSuggestionService;
use App\Services\Finance\FinancePendingResolutionService;
use App\Services\Finance\FinanceReminderService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceReminderService $reminders,
        private readonly FinanceCutSuggestionService $cutSuggestions,
        private readonly FinancePendingResolutionService $pending,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $month = $request->query('month', now()->format('Y-m'));
        $summary = $this->summaryService->monthSummary($user, $month);
        $accounts = $this->accountsFor($user);
        $cutSuggestion = $this->cutSuggestions->suggest($user, $accounts, today());

        $monthStart = $summary['month'];
        $previousTotals = $this->summaryService->monthTotals($user, $monthStart->copy()->subMonth());

        return view('finance.dashboard', [
            'summary' => $summary,
            'accounts' => $accounts,
            'categories' => $this->categoriesFor($user),
            'people' => $this->peopleFor($user),
            'reminderSummary' => $this->reminders->dashboardReminders($user),
            'reminderTypes' => FinanceReminderService::TYPES,
            'reminderVehicles' => FinanceReminderService::VEHICLES,
            'suggestedBalances' => $cutSuggestion['suggested'],
            'previousBalances' => $cutSuggestion['previous'],
            'previousCutDate' => $cutSuggestion['previous_cut_date'],
            'creditLine' => $this->summaryService->creditLineSummary($user),
            'pendingSummary' => $this->pending->summaryCounts($user),
            'monthComparison' => $this->buildMonthComparison($summary, $previousTotals, $monthStart),
            'dashboardLayout' => $user->dashboard_layout,
        ]);
    }

    /**
     * Guarda (lado servidor) la distribución del Resumen del usuario: orden,
     * tamaños, cuadros ocultos y auto-ajuste. Solo preferencia visual; no toca
     * datos ni cálculos financieros. Cada usuario guarda la suya.
     */
    public function saveLayout(Request $request)
    {
        $data = $request->validate([
            'layout' => ['nullable', 'array'],
            'layout.order' => ['nullable', 'array', 'max:100'],
            'layout.order.*' => ['string', 'max:80'],
            'layout.sizes' => ['nullable', 'array', 'max:100'],
            'layout.sizes.*' => ['integer', 'min:1', 'max:4'],
            'layout.hidden' => ['nullable', 'array', 'max:100'],
            'layout.hidden.*' => ['string', 'max:80'],
            'layout.autoLayout' => ['nullable', 'boolean'],
        ]);

        $request->user()->update([
            'dashboard_layout' => $data['layout'] ?? null,
        ]);

        return response()->noContent();
    }

    /**
     * @param array<string, mixed> $summary
     * @param array{income: float, expenses: float} $previous
     * @return array<string, mixed>
     */
    private function buildMonthComparison(array $summary, array $previous, Carbon $monthStart): array
    {
        $changePercent = function (float $current, float $previous): ?float {
            if ($previous <= 0) {
                return null;
            }

            return round((($current - $previous) / $previous) * 100, 1);
        };

        $currentIncome = (float) $summary['total_income'];
        $currentExpenses = (float) $summary['expenses'];

        return [
            'previous_label' => $monthStart->copy()->subMonth()->translatedFormat('F Y'),
            'income_current' => $currentIncome,
            'income_previous' => $previous['income'],
            'income_change' => $changePercent($currentIncome, $previous['income']),
            'expenses_current' => $currentExpenses,
            'expenses_previous' => $previous['expenses'],
            'expenses_change' => $changePercent($currentExpenses, $previous['expenses']),
        ];
    }
}
