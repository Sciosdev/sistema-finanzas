<?php

namespace App\Services\Finance;

use App\Models\User;
use Carbon\Carbon;

/**
 * Estrategia de crédito multi-periodo (Fase 2 del rediseño del Planificador).
 *
 * Se apoya en el motor de periodos (FinancePeriodPlanService) y simula la pista
 * de efectivo encadenada para decidir CUÁNDO conviene pagar cada deuda de
 * crédito del mes en curso:
 *
 *   - pay_now: lo que cabe hoy sin bajar del colchón ni de las reservas de
 *     flujos hasta el próximo ingreso.
 *   - after_income: lo que conviene esperar a que entre un ingreso.
 *   - deferred (spillover): si un mes no alcanza, se difiere SOLO la parte que
 *     no cabe al mes siguiente, cuidando el colchón.
 *   - cushion_dip: si una mensualidad no cabe sobre el colchón pero un ingreso
 *     posterior ya seguro lo repone, se permite "rayar" el colchón, avisando.
 *
 * Solo lectura: no crea movimientos, abonos, ni cambia estados. Recomendación.
 */
class FinanceCreditScheduleService
{
    public function __construct(private readonly FinancePeriodPlanService $periodPlanService) {}

    public function build(User $user): array
    {
        $today = today()->startOfDay();
        $plan = $this->periodPlanService->build($user);

        $segments = $plan['segments'];
        $buffer = $this->money((float) ($plan['meta']['buffer'] ?? 0));
        $startingBalance = $this->money((float) ($plan['meta']['starting_balance'] ?? 0));
        $creditAccounts = $this->prioritizedCredits($plan['credit_accounts'], $segments, $today);

        $result = $this->simulate($segments, $creditAccounts, $startingBalance, $buffer);

        return array_merge($result, [
            'meta' => [
                'today' => $plan['meta']['today'],
                'planning_end' => $plan['meta']['planning_end'],
                'starting_balance' => $startingBalance,
                'buffer' => $buffer,
                'current_month_credit_due_total' => $this->money((float) ($plan['current_month_credit_due_total'] ?? 0)),
            ],
            'segments' => $segments,
            'messages' => $this->messages($result, $buffer),
        ]);
    }

    /**
     * Ordena las deudas de crédito del mes por presión (vencidas primero, luego
     * la de vencimiento más próximo, luego la de mayor monto) y les asigna el
     * índice del sub-periodo en que vencen.
     */
    private function prioritizedCredits(array $creditAccounts, array $segments, Carbon $today): array
    {
        $credits = array_map(function (array $account) use ($segments, $today) {
            $dueDate = $account['next_due_date'] ? Carbon::parse($account['next_due_date'])->startOfDay() : null;

            return [
                'account_id' => $account['account_id'] ?? null,
                'account_name' => $account['account_name'],
                'amount' => $this->money((float) $account['month_due_total']),
                'due_date' => $account['next_due_date'],
                'due_carbon' => $dueDate,
                'is_overdue' => $dueDate !== null && $dueDate->lt($today),
                'due_segment_index' => $this->dueSegmentIndex($segments, $dueDate),
                'credits_count' => (int) ($account['credits_count'] ?? 0),
            ];
        }, $creditAccounts);

        usort($credits, function (array $a, array $b) {
            if ($a['is_overdue'] !== $b['is_overdue']) {
                return $b['is_overdue'] <=> $a['is_overdue'];
            }

            $aDue = $a['due_carbon']?->timestamp ?? PHP_INT_MAX;
            $bDue = $b['due_carbon']?->timestamp ?? PHP_INT_MAX;
            if ($aDue !== $bDue) {
                return $aDue <=> $bDue;
            }

            if ((float) $a['amount'] !== (float) $b['amount']) {
                return (float) $b['amount'] <=> (float) $a['amount'];
            }

            return strcmp($a['account_name'], $b['account_name']);
        });

        return $credits;
    }

    private function dueSegmentIndex(array $segments, ?Carbon $dueDate): int
    {
        if ($segments === []) {
            return 0;
        }

        if ($dueDate === null) {
            return count($segments) - 1;
        }

        foreach ($segments as $index => $segment) {
            $start = Carbon::parse($segment['start_date'])->startOfDay();
            $end = Carbon::parse($segment['end_date'])->startOfDay();

            if ($dueDate->lt($start)) {
                return $index; // vencido o antes del primer tramo → el más cercano
            }

            if ($dueDate->betweenIncluded($start, $end)) {
                return $index;
            }
        }

        return count($segments) - 1; // vence después del horizonte → último tramo
    }

    private function simulate(array $segments, array $credits, float $startingBalance, float $buffer): array
    {
        $remaining = $credits;
        $payNow = [];
        $afterIncome = [];
        $deferred = [];
        $cushionDipItems = [];
        $cushionReponibleBy = null;

        $balance = $startingBalance;

        foreach ($segments as $i => $segment) {
            $balance = $this->money($balance + (float) $segment['income_total']);
            $reserve = $this->money((float) $segment['cash_flows_total'] + (float) $segment['card_charges_total']);
            $paidHere = 0.0;

            foreach ($remaining as $key => $credit) {
                $availAbove = $this->money($balance - $reserve - $buffer - $paidHere);
                $availToZero = $this->money($balance - $reserve - $paidHere);
                $mandatory = $credit['due_segment_index'] <= $i;

                if ((float) $credit['amount'] <= $availAbove) {
                    // Cabe sin tocar el colchón: se paga (hoy o tras el ingreso del tramo).
                    $this->place($payNow, $afterIncome, $i, $segment, $credit, (float) $credit['amount'], false);
                    $paidHere = $this->money($paidHere + (float) $credit['amount']);
                    unset($remaining[$key]);
                    continue;
                }

                if (! $mandatory) {
                    continue; // vence más adelante; se reintenta en un tramo con más efectivo
                }

                // Obligatoria en este tramo pero no cabe sobre el colchón.
                $replenishDate = $this->replenishingIncomeDateAfter($segments, $i, $buffer);

                if ($replenishDate !== null) {
                    // Se puede rayar el colchón: un ingreso posterior ya seguro lo repone.
                    $pay = $this->money(min((float) $credit['amount'], max(0, $availToZero)));

                    if ($pay > 0) {
                        $this->place($payNow, $afterIncome, $i, $segment, $credit, $pay, true);
                        $cushionDipItems[] = $this->creditLine($credit, $pay) + ['reponible_by_date' => $replenishDate];
                        $cushionReponibleBy = $cushionReponibleBy === null ? $replenishDate : min($cushionReponibleBy, $replenishDate);
                        $paidHere = $this->money($paidHere + $pay);
                    }

                    $rest = $this->money((float) $credit['amount'] - $pay);
                    if ($rest > 0) {
                        $deferred[] = $this->creditLine($credit, $rest) + ['reason' => 'No cabe ni rayando el colchón; se difiere al mes siguiente.'];
                    }
                    unset($remaining[$key]);
                    continue;
                }

                // Sin ingreso que reponga: se cubre solo hasta el colchón y se difiere el resto (spillover).
                $pay = $this->money(max(0, $availAbove));
                if ($pay > 0) {
                    $this->place($payNow, $afterIncome, $i, $segment, $credit, $pay, false, true);
                    $paidHere = $this->money($paidHere + $pay);
                }
                $deferred[] = $this->creditLine($credit, $this->money((float) $credit['amount'] - $pay))
                    + ['reason' => 'Este mes no alcanza sin romper el colchón; se difiere solo esta parte al mes siguiente.'];
                unset($remaining[$key]);
            }

            $balance = $this->money($balance - $reserve - $paidHere);
        }

        // Lo que nunca se pudo colocar (por si quedara algo) también es diferido.
        foreach ($remaining as $credit) {
            $deferred[] = $this->creditLine($credit, (float) $credit['amount'])
                + ['reason' => 'No cabe dentro del horizonte planeado; queda para después.'];
        }

        return [
            'pay_now' => [
                'total' => $this->money(array_sum(array_column($payNow, 'amount'))),
                'items' => array_values($payNow),
            ],
            'after_income' => $this->groupAfterIncome($afterIncome),
            'deferred' => [
                'total' => $this->money(array_sum(array_column($deferred, 'amount'))),
                'items' => array_values($deferred),
            ],
            'cushion_dip' => [
                'used' => $cushionDipItems !== [],
                'total' => $this->money(array_sum(array_column($cushionDipItems, 'amount'))),
                'reponible_by_date' => $cushionReponibleBy,
                'items' => array_values($cushionDipItems),
            ],
        ];
    }

    /**
     * Coloca un pago en pay_now (tramo 0) o en la bolsa after_income del tramo.
     */
    private function place(array &$payNow, array &$afterIncome, int $segmentIndex, array $segment, array $credit, float $amount, bool $cushionDip, bool $partial = false): void
    {
        $line = $this->creditLine($credit, $amount) + [
            'segment_index' => $segmentIndex,
            'is_cushion_dip' => $cushionDip,
            'is_partial' => $partial,
            'reason' => $this->payReason($credit, $cushionDip, $partial),
        ];

        if ($segmentIndex === 0) {
            $payNow[] = $line;

            return;
        }

        $afterIncome[] = $line + [
            'checkpoint_date' => $segment['start_date'],
            'income_total' => $this->money((float) $segment['income_total']),
            'income_name' => $segment['income_items'][0]['name'] ?? null,
        ];
    }

    private function groupAfterIncome(array $afterIncome): array
    {
        $groups = [];

        foreach ($afterIncome as $line) {
            $date = $line['checkpoint_date'];
            $groups[$date] ??= [
                'checkpoint_date' => $date,
                'income_total' => $line['income_total'],
                'income_name' => $line['income_name'],
                'total' => 0.0,
                'items' => [],
            ];
            $groups[$date]['total'] = $this->money($groups[$date]['total'] + (float) $line['amount']);
            $groups[$date]['items'][] = $line;
        }

        ksort($groups);

        return array_values($groups);
    }

    private function replenishingIncomeDateAfter(array $segments, int $fromIndex, float $buffer): ?string
    {
        for ($j = $fromIndex + 1; $j < count($segments); $j++) {
            if ((float) $segments[$j]['income_total'] > 0) {
                return $segments[$j]['start_date'];
            }
        }

        return null;
    }

    private function creditLine(array $credit, float $amount): array
    {
        return [
            'account_id' => $credit['account_id'] ?? null,
            'account_name' => $credit['account_name'],
            'amount' => $this->money($amount),
            'due_date' => $credit['due_date'] ?? null,
            'is_overdue' => (bool) ($credit['is_overdue'] ?? false),
            'credits_count' => (int) ($credit['credits_count'] ?? 0),
        ];
    }

    private function payReason(array $credit, bool $cushionDip, bool $partial): string
    {
        $name = $credit['account_name'];

        if ($cushionDip) {
            return 'Puedes pagar '.$name.' rayando un poco el colchón; el próximo ingreso ya seguro lo repone.';
        }

        if ($partial) {
            return 'Cubre lo que cabe de '.$name.' sin romper el colchón; el resto se difiere.';
        }

        if ((bool) ($credit['is_overdue'] ?? false)) {
            return 'Paga '.$name.' cuanto antes: está vencido.';
        }

        return 'Puedes cubrir '.$name.' sin bajar de tu colchón ni de tus reservas de flujos.';
    }

    private function messages(array $result, float $buffer): array
    {
        $messages = [];
        $payNow = $result['pay_now'];
        $afterIncome = $result['after_income'];
        $deferred = $result['deferred'];
        $cushion = $result['cushion_dip'];

        if (($payNow['total'] ?? 0) > 0) {
            $names = $this->humanList(array_column($payNow['items'], 'account_name'));
            $messages[] = 'Hoy puedes pagar '.$this->formatMoney((float) $payNow['total']).' de crédito ('.$names.') sin bajar de tu colchón.';
        } else {
            $messages[] = 'Hoy conviene no pagar crédito todavía; conserva efectivo para flujos y colchón.';
        }

        foreach ($afterIncome as $group) {
            $names = $this->humanList(array_column($group['items'], 'account_name'));

            if ((float) $group['income_total'] > 0) {
                $messages[] = 'Cuando entre el ingreso del '.$this->formatDate($group['checkpoint_date']).', paga '
                    .$this->formatMoney((float) $group['total']).' ('.$names.').';
            } else {
                $messages[] = 'Del '.$this->formatDate($group['checkpoint_date']).' en adelante, paga '
                    .$this->formatMoney((float) $group['total']).' ('.$names.').';
            }
        }

        if (($cushion['used'] ?? false) && $cushion['reponible_by_date']) {
            $messages[] = 'Si lo necesitas, puedes rayar el colchón por '.$this->formatMoney((float) $cushion['total'])
                .'; se repone con el ingreso del '.$this->formatDate($cushion['reponible_by_date']).'.';
        }

        if (($deferred['total'] ?? 0) > 0) {
            $names = $this->humanList(array_column($deferred['items'], 'account_name'));
            $messages[] = 'Este mes no alcanza para todo: difiere '.$this->formatMoney((float) $deferred['total'])
                .' ('.$names.') al mes siguiente; es solo una parte, no toda la deuda.';
        }

        return $messages;
    }

    private function humanList(array $items): string
    {
        $items = array_values(array_unique(array_filter(array_map(fn ($item) => (string) $item, $items))));

        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' y '.$last;
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    private function formatMoney(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
