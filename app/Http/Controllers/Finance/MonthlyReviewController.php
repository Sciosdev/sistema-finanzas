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
        $review = $this->reviews->review($request->user(), $month);
        $ignored = $this->ignoredKeys($request, $month);

        $review['suggestions'] = collect($review['suggestions'])
            ->reject(fn (array $suggestion) => in_array($suggestion['key'], $ignored, true))
            ->values()
            ->all();
        $review['applyable_count'] = collect($review['suggestions'])->where('applyable', true)->count();

        return view('finance.monthly-review.index', [
            'selectedMonth' => $month,
            'review' => $review,
            'ignoredCount' => count($ignored),
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
        $sessionKey = $this->ignoredSessionKey($month);

        $ignored = $this->ignoredKeys($request, $month);
        if (! in_array($key, $ignored, true)) {
            $ignored[] = $key;
            $request->session()->put($sessionKey, $ignored);
        }

        return redirect()
            ->route('finance.monthly-review.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Sugerencia ignorada. No se modificó ningún movimiento.');
    }

    public function restoreIgnored(Request $request)
    {
        $month = $this->monthFromRequest($request);
        $request->session()->forget($this->ignoredSessionKey($month));

        return redirect()
            ->route('finance.monthly-review.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Se restauraron las sugerencias ignoradas de este mes.');
    }

    private function ignoredSessionKey(Carbon $month): string
    {
        return 'finance_review_ignored_' . $month->format('Y-m');
    }

    /**
     * @return list<string>
     */
    private function ignoredKeys(Request $request, Carbon $month): array
    {
        return array_values(array_filter(
            (array) $request->session()->get($this->ignoredSessionKey($month), []),
            fn ($key) => is_string($key)
        ));
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
