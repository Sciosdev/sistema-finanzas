<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\DailyCut;
use App\Services\Finance\AutomaticYieldService;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceCutSuggestionService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyCutController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly AutomaticYieldService $automaticYieldService,
        private readonly FinanceCutSuggestionService $cutSuggestions,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($request->query('month', now()->format('Y-m')));

        $cuts = DailyCut::with('balances.account')
            ->where('user_id', $user->id)
            ->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('cut_date')
            ->get();

        $accounts = $this->accountsFor($user);
        $suggestion = $this->cutSuggestions->suggest($user, $accounts, today());

        $reconciliations = $cuts->mapWithKeys(fn (DailyCut $cut) => [
            $cut->id => $this->cutSuggestions->reconciliationFor($cut),
        ])->all();

        return view('finance.cuts.index', [
            'cuts' => $cuts,
            'monthValue' => $start->format('Y-m'),
            'accounts' => $accounts,
            'suggestedBalances' => $suggestion['suggested'],
            'previousBalances' => $suggestion['previous'],
            'previousCutDate' => $suggestion['previous_cut_date'],
            'reconciliations' => $reconciliations,
            'expectedBalances' => $this->cutSuggestions->expectedBalances($user, $accounts, today()),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'cut_date' => ['required', 'date'],
            'balances' => ['required', 'array'],
            'balances.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $cutDate = Carbon::parse($data['cut_date']);
        $accounts = $this->accountsFor($user)->keyBy('id');
        $cash = 0.0;
        $cards = 0.0;
        $balances = [];

        foreach ($data['balances'] as $accountId => $balance) {
            if (!$accounts->has((int) $accountId)) {
                continue;
            }

            $amount = round((float) ($balance ?: 0), 2);
            $account = $accounts->get((int) $accountId);
            $balances[$account->id] = $amount;

            if ($account->isCash()) {
                $cash += $amount;
            } else {
                $cards += $amount;
            }
        }

        $this->automaticYieldService->syncForCut($user, $cutDate, $balances);

        $realTotal = round($cash + $cards, 2);
        // "Saldo proyectado" = saldo de arranque (corte anterior o saldo inicial)
        // + movimientos hasta la fecha. Misma base que la revisión por cuenta, así
        // la diferencia da 0 cuando todo cuadra (antes ignoraba el saldo previo).
        $expected = $this->cutSuggestions->expectedTotalThrough($user, $accounts, $cutDate);
        [$start, $end] = $this->summaryService->monthRange($cutDate->format('Y-m'));
        $pending = $this->summaryService->pendingForMonth($user, $start, $end);
        $difference = round($expected - $realTotal, 2);
        $amountMissing = round($realTotal - $pending, 2);

        $cut = DailyCut::updateOrCreate(
            ['user_id' => $user->id, 'cut_date' => $cutDate->toDateString()],
            [
                'expected_leftover' => $expected,
                'cash_amount' => $cash,
                'cards_amount' => $cards,
                'real_total' => $realTotal,
                'pending_payments' => $pending,
                'difference' => $difference,
                'amount_missing' => $amountMissing,
                'status' => abs($difference) <= 0.01 ? 'ok' : 'review',
                'notes' => $data['notes'] ?? null,
            ]
        );

        $cut->balances()->delete();

        foreach ($balances as $accountId => $balance) {
            $cut->balances()->create([
                'account_id' => $accountId,
                'balance' => $balance,
            ]);
        }

        return back()->with('success', 'Corte guardado y conciliado.');
    }
}
