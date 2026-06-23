<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceMonthlyReviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyReviewController extends Controller
{
    public function __construct(
        private readonly FinanceMonthlyReviewService $reviews,
    ) {
    }

    public function index(Request $request)
    {
        $month = $this->monthFromRequest($request);

        return view('finance.monthly-review.index', [
            'selectedMonth' => $month,
            'review' => $this->reviews->review($request->user(), $month),
        ]);
    }

    public function apply(Request $request, string $key)
    {
        $month = $this->monthFromRequest($request);
        $result = $this->reviews->apply($request->user(), $month, $key);

        return redirect()
            ->route('finance.monthly-review.index', ['month' => $month->format('Y-m')])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function ignore(Request $request, string $key)
    {
        $month = $this->monthFromRequest($request);

        return redirect()
            ->route('finance.monthly-review.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Sugerencia ignorada por ahora. No se modificó ningún movimiento.');
    }

    private function monthFromRequest(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $request->query('month', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
