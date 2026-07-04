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
        // Base = el corte ANTERIOR a la fecha objetivo (no uno del mismo día).
        // Si tomáramos el corte del mismo día que se está editando, el sugerido
        // sería circular (igual a lo ya guardado) y el formulario siempre diría
        // "cuadra", aunque al guardar la conciliación (que usa el corte anterior)
        // marcara diferencia. Con esto el sugerido = lo que espera la conciliación.
        $lastCut = DailyCut::with('balances')
            ->where('user_id', $user->id)
            ->whereDate('cut_date', '<', $targetDate->toDateString())
            ->orderByDesc('cut_date')
            ->first();

        if (! $lastCut) {
            return ['suggested' => [], 'previous' => [], 'previous_cut_date' => null];
        }

        $baseline = $lastCut->balances
            ->keyBy('account_id')
            ->map(fn ($balance) => (float) $balance->balance);

        $delta = $this->movementDeltaSince($user->id, $lastCut, $targetDate);

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

    /**
     * Saldo esperado HOY por cada cuenta, con el mismo criterio que el corte:
     * parte del saldo del último corte (o del saldo inicial si aún no hay cortes)
     * y le suma ingresos/rendimientos y le resta egresos capturados después.
     *
     * Sirve para comparar contra el dinero real: si no coincide, faltó registrar
     * algo o hubo una diferencia. Solo lee; no escribe ni cambia cálculos.
     *
     * @param Collection<int, \App\Models\Finance\Account> $accounts
     * @return array<int, array{expected: float, baseline: float, delta: float, since: ?Carbon, from_cut: bool}>
     */
    public function expectedBalances(User $user, Collection $accounts, Carbon $asOf): array
    {
        $lastCut = DailyCut::with('balances')
            ->where('user_id', $user->id)
            ->orderByDesc('cut_date')
            ->first();

        $baseline = $lastCut
            ? $lastCut->balances->keyBy('account_id')->map(fn ($balance) => (float) $balance->balance)
            : collect();
        $cutDate = $lastCut?->cut_date;

        $delta = $this->movementDeltaSince($user->id, $lastCut, $asOf);

        $result = [];
        foreach ($accounts as $account) {
            $fromCut = $cutDate !== null && $baseline->has($account->id);
            $base = $fromCut ? (float) $baseline[$account->id] : (float) $account->opening_balance;
            $accountDelta = (float) ($delta[$account->id] ?? 0);

            $result[$account->id] = [
                'expected' => round($base + $accountDelta, 2),
                'baseline' => round($base, 2),
                'delta' => round($accountDelta, 2),
                'since' => $fromCut ? $cutDate : null,
                'from_cut' => $fromCut,
            ];
        }

        return $result;
    }

    /**
     * Diferencia por cuenta de un corte ya guardado: compara lo declarado contra
     * lo esperado (saldo del corte anterior + movimientos hasta este corte). Solo
     * lee y recalcula de datos existentes; no escribe ni cambia ningún cálculo.
     *
     * @return array<int, array{name: string, color: ?string, expected: float, real: float, difference: float, has_baseline: bool}>
     */
    public function reconciliationFor(DailyCut $cut): array
    {
        $previousCut = DailyCut::with('balances')
            ->where('user_id', $cut->user_id)
            ->whereDate('cut_date', '<', $cut->cut_date->toDateString())
            ->orderByDesc('cut_date')
            ->first();

        $baseline = $previousCut
            ? $previousCut->balances->keyBy('account_id')->map(fn ($balance) => (float) $balance->balance)
            : collect();

        $delta = $this->movementDeltaSince($cut->user_id, $previousCut, $cut->cut_date);

        $result = [];
        foreach ($cut->balances as $balance) {
            $account = $balance->account;
            $accountId = (int) $balance->account_id;
            $hasBaseline = $previousCut !== null && $baseline->has($accountId);
            $base = $hasBaseline
                ? (float) $baseline[$accountId]
                : (float) ($account->opening_balance ?? 0);
            $expected = round($base + (float) ($delta[$accountId] ?? 0), 2);
            $real = round((float) $balance->balance, 2);

            $result[$accountId] = [
                'name' => $account->name ?? ('Cuenta #' . $accountId),
                'color' => $account->color ?? null,
                'expected' => $expected,
                'real' => $real,
                'difference' => round($real - $expected, 2),
                'has_baseline' => $hasBaseline,
            ];
        }

        return $result;
    }

    /**
     * Total esperado (saldo de arranque + movimientos) a una fecha de corte,
     * tomando como base el corte ANTERIOR (cut_date < $cutDate) o el saldo
     * inicial de cada cuenta. Es la misma base que reconciliationFor y
     * expectedBalances, para que la "Diferencia de conciliación" del encabezado
     * sea consistente con la revisión por cuenta. (Antes se usaba solo el neto
     * de movimientos del mes, sin el saldo de arranque, y el primer corte daba
     * una diferencia igual a todo tu saldo en negativo.)
     *
     * @param Collection<int, \App\Models\Finance\Account> $accounts
     */
    public function expectedTotalThrough(User $user, Collection $accounts, Carbon $cutDate): float
    {
        $previousCut = DailyCut::with('balances')
            ->where('user_id', $user->id)
            ->whereDate('cut_date', '<', $cutDate->toDateString())
            ->orderByDesc('cut_date')
            ->first();

        $baseline = $previousCut
            ? $previousCut->balances->keyBy('account_id')->map(fn ($balance) => (float) $balance->balance)
            : collect();

        $delta = $this->movementDeltaSince($user->id, $previousCut, $cutDate);

        $total = 0.0;
        foreach ($accounts as $account) {
            $hasBaseline = $previousCut !== null && $baseline->has($account->id);
            $base = $hasBaseline ? (float) $baseline[$account->id] : (float) ($account->opening_balance ?? 0);
            $total += $base + (float) ($delta[$account->id] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Cambio neto por cuenta desde un corte base hasta una fecha tope: cuenta los
     * movimientos con happened_on posterior a la fecha del corte base y hasta la
     * fecha tope (inclusive). Los movimientos del MISMO día del corte se
     * consideran ya incluidos en el saldo declarado de ese corte.
     *
     * @return array<int, float>
     */
    private function movementDeltaSince(int $userId, ?DailyCut $baselineCut, Carbon $upperBound): array
    {
        $query = Movement::query()
            ->where('user_id', $userId)
            ->whereNotNull('account_id')
            ->whereDate('happened_on', '<=', $upperBound->toDateString());

        if ($baselineCut !== null) {
            $query->whereDate('happened_on', '>', $baselineCut->cut_date->toDateString());
        }

        $delta = [];
        foreach ($query->get(['account_id', 'movement_type', 'amount']) as $movement) {
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

        return $delta;
    }
}
