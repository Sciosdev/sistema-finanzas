<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceReminderService;
use App\Services\Finance\FinanceSummaryService;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceReminderService $reminders,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $month = $request->query('month', now()->format('Y-m'));
        $summary = $this->summaryService->monthSummary($user, $month);

        return view('finance.dashboard', [
            'summary' => $summary,
            'accounts' => $this->accountsFor($user),
            'categories' => $this->categoriesFor($user),
            'people' => $this->peopleFor($user),
            'reminderSummary' => $this->reminders->dashboardReminders($user),
            'reminderTypes' => FinanceReminderService::TYPES,
            'reminderVehicles' => FinanceReminderService::VEHICLES,
        ]);
    }
}
