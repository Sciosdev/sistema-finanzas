<?php

namespace App\Services\Finance;

use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\RentalContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Ingresos de renta por contrato (finance_rental_contracts). No viven en
 * finance_expected_incomes: la pantalla de ingresos los genera al vuelo, así que
 * la proyección y el planificador los ignoraban. Este servicio los materializa
 * como eventos {fecha, nombre, monto} por cada mes del rango, con la MISMA
 * deduplicación que la lista de ingresos:
 *   - se salta a las personas con renta capturada manualmente ese mes, y
 *   - resta lo ya recibido en movimientos de renta (is_rent) de ese mes.
 *
 * Solo lectura. Fuente única para proyección, plan por periodos y plan legacy.
 */
class FinanceRentalContractIncomeService
{
    /**
     * Eventos de renta por contrato en cada mes tocado por [start, end]. La fecha
     * de cada evento es el due_day del contrato dentro de ese mes (puede quedar
     * antes de $start si el día ya pasó en el mes en curso: el consumidor decide
     * si lo trata como vencido). El filtrado fino al horizonte lo hace quien llama.
     *
     * @return array<int, array{date: Carbon, name: string, amount: float, person_id: ?int}>
     */
    public function eventsBetween(User $user, Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        $contracts = RentalContract::with('person')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expected_amount', '>', 0)
            ->where(function ($query) use ($end) {
                $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', $end->toDateString());
            })
            ->where(function ($query) use ($start) {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start->toDateString());
            })
            ->get();

        if ($contracts->isEmpty()) {
            return [];
        }

        $manualRentIncomes = ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->where('is_rent', true)
            ->whereNotNull('person_id')
            ->get(['person_id', 'period_month']);

        $rentMovements = Movement::query()
            ->where('user_id', $user->id)
            ->where('movement_type', 'income')
            ->where('is_rent', true)
            ->whereDate('happened_on', '>=', $start->copy()->startOfMonth()->toDateString())
            ->whereDate('happened_on', '<=', $end->copy()->endOfMonth()->toDateString())
            ->get();

        $events = [];
        $month = $start->copy()->startOfMonth();
        $lastMonth = $end->copy()->startOfMonth();

        while ($month->lte($lastMonth)) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $manualPersonIds = $manualRentIncomes
                ->filter(fn (ExpectedIncome $income) => $income->period_month
                    && $income->period_month->copy()->startOfMonth()->isSameDay($monthStart))
                ->pluck('person_id')
                ->all();

            $monthMovements = $rentMovements
                ->filter(fn (Movement $movement) => $movement->happened_on
                    && $movement->happened_on->betweenIncluded($monthStart, $monthEnd));

            foreach ($contracts as $contract) {
                if ($contract->starts_on && $contract->starts_on->copy()->startOfDay()->gt($monthEnd)) {
                    continue;
                }
                if ($contract->ends_on && $contract->ends_on->copy()->startOfDay()->lt($monthStart)) {
                    continue;
                }
                if (in_array($contract->person_id, $manualPersonIds, true)) {
                    continue;
                }

                $personName = $contract->person?->name ?? 'Renta';
                $needle = Str::lower($personName);
                $paidAmount = $monthMovements
                    ->filter(fn (Movement $movement) => $movement->person_id === $contract->person_id
                        || ($needle !== '' && Str::contains(Str::lower((string) $movement->description), $needle)))
                    ->sum(fn (Movement $movement) => (float) $movement->amount);
                $amountDue = round(max(0, (float) $contract->expected_amount - (float) $paidAmount), 2);

                if ($amountDue <= 0) {
                    continue;
                }

                $dueDay = (int) ($contract->due_day ?: 1);
                $date = $monthStart->copy()->day(min($dueDay, $monthStart->daysInMonth))->startOfDay();

                $events[] = [
                    'date' => $date,
                    'name' => $contract->room ? 'Renta cuarto '.$contract->room : 'Renta San Juan',
                    'amount' => $amountDue,
                    'person_id' => $contract->person_id,
                ];
            }

            $month->addMonth();
        }

        return $events;
    }
}
