<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\AutomaticYieldService;
use App\Services\Finance\FinanceCatalogService;
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

        return view('finance.cuts.index', [
            'cuts' => $cuts,
            'monthValue' => $start->format('Y-m'),
            'accounts' => $accounts,
            'suggestedBalances' => $this->suggestedCutBalances($user, $accounts, today()),
        ]);
    }

    /**
     * Calcula el saldo esperado por cuenta para el nuevo corte: parte del último
     * corte y le suma los ingresos/rendimientos y le resta los egresos de cada
     * cuenta capturados después de esa fecha. Es solo una sugerencia editable;
     * no escribe nada ni cambia cálculos del corte.
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\Finance\Account> $accounts
     * @return array<int, float>
     */
    private function suggestedCutBalances(User $user, $accounts, Carbon $targetDate): array
    {
        $lastCut = DailyCut::with('balances')
            ->where('user_id', $user->id)
            ->orderByDesc('cut_date')
            ->first();

        if (! $lastCut) {
            return [];
        }

        $baseline = $lastCut->balances
            ->keyBy('account_id')
            ->map(fn ($balance) => (float) $balance->balance);

        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->whereNotNull('account_id')
            ->whereDate('happened_on', '>', $lastCut->cut_date->toDateString())
            ->whereDate('happened_on', '<=', $targetDate->toDateString())
            ->get(['account_id', 'movement_type', 'amount']);

        $delta = [];
        foreach ($movements as $movement) {
            $sign = match ($movement->movement_type) {
                'income', 'yield' => 1,
                'expense' => -1,
                default => 0,
            };

            if ($sign === 0) {
                continue;
            }

            $delta[$movement->account_id] = ($delta[$movement->account_id] ?? 0) + $sign * (float) $movement->amount;
        }

        $suggested = [];
        foreach ($accounts as $account) {
            $base = (float) ($baseline[$account->id] ?? 0);
            $suggested[$account->id] = round(max(0, $base + ($delta[$account->id] ?? 0)), 2);
        }

        return $suggested;
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
        $expected = $this->summaryService->expectedThroughDate($user, $cutDate);
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
