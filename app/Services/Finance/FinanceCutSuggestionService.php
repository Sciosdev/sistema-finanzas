<?php

namespace App\Services\Finance;

use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula los saldos sugeridos para un nuevo corte (sin escribir nada):
 *  - "previous": el saldo de cada cuenta en el último corte (lo que había antes).
 *  - "suggested": ese saldo + ingresos/rendimientos − egresos capturados después.
 *
 * Es solo una sugerencia editable; no modifica datos ni cálculos del corte.
 */
class FinanceCutSuggestionService
{
    /**
     * @param Collection<int, \App\Models\Finance\Account> $accounts
     * @return array{suggested: array<int, float>, previous: array<int, float>, previous_cut_date: ?Carbon}
     */
    public function suggest(User $user, Collection $accounts, Carbon $targetDate): array
    {
        $lastCut = DailyCut::with('balances')
            ->where('user_id', $user->id)
            ->orderByDesc('cut_date')
            ->first();

        if (! $lastCut) {
            return ['suggested' => [], 'previous' => [], 'previous_cut_date' => null];
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
        $previous = [];
        foreach ($accounts as $account) {
            $base = (float) ($baseline[$account->id] ?? 0);
            $previous[$account->id] = round($base, 2);
            $suggested[$account->id] = round(max(0, $base + ($delta[$account->id] ?? 0)), 2);
        }

        return [
            'suggested' => $suggested,
            'previous' => $previous,
            'previous_cut_date' => $lastCut->cut_date,
        ];
    }
}
