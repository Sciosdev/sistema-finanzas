<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditInstallment;
use App\Models\Finance\ExpectedIncome;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Motor de periodos encadenados (Fase 1 del rediseño del Planificador).
 *
 * Construye la "pista" de efectivo dividida en quincenas de calendario (1–15 y
 * 16–fin) como contenedor/etiqueta y, dentro de cada quincena, en sub-periodos
 * cortados por cada ingreso esperado (el corte real lo marca el ingreso, según
 * la decisión "híbrido"). Encadena el saldo: el sobrante de un sub-periodo es la
 * apertura del siguiente, para poder narrar "tienes X, entra el ingreso, pagas
 * los flujos, te queda Z".
 *
 * Modelo de dinero por sub-periodo:
 *   cierre = apertura + ingreso_al_inicio − flujos_en_efectivo − cargos_a_tarjeta − mensualidades_de_credito
 * Los flujos domiciliados a tarjeta (is_credit) NO descuentan efectivo en su
 * fecha; ya vienen como card_charges desde la proyección y pegan el día que se
 * paga la tarjeta.
 *
 * Solo lectura: se apoya en FinanceProjectionService::projectUntil y no crea
 * movimientos ni cambia estados de pagos, ingresos ni mensualidades.
 */
class FinancePeriodPlanService
{
    private const MONTHS_ES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function __construct(private readonly FinanceProjectionService $projectionService) {}

    public function build(User $user): array
    {
        $today = today()->startOfDay();
        $nextMonthFirstIncome = $this->nextMonthFirstIncome($user, $today);
        $planningEnd = $this->planningEnd($today, $nextMonthFirstIncome);
        $projection = $this->projectionService->projectUntil($user, $planningEnd);

        $buffer = $this->money((float) ($projection['meta']['buffer'] ?? 0));
        $startingBalance = $this->money((float) ($projection['meta']['starting_balance'] ?? 0));

        $daysByDate = collect($projection['days'])->keyBy('date');
        $incomeDates = $this->incomeDatesWithin($daysByDate, $today, $planningEnd);

        $segments = $this->buildSegments($today, $planningEnd, $incomeDates, $daysByDate, $startingBalance, $buffer);
        $creditAccounts = $this->currentMonthCreditAccounts($user, $today);

        return [
            'meta' => [
                'today' => $today->toDateString(),
                'planning_end' => $planningEnd->toDateString(),
                'current_month_end' => $today->copy()->endOfMonth()->toDateString(),
                'next_month_first_income_date' => $nextMonthFirstIncome?->toDateString(),
                'starting_balance' => $startingBalance,
                'buffer' => $buffer,
            ],
            'segments' => $segments,
            'credit_accounts' => $creditAccounts,
            'current_month_credit_due_total' => $this->money(collect($creditAccounts)->sum('month_due_total')),
            'messages' => $this->messages($segments, $buffer),
        ];
    }

    /**
     * El horizonte de planeación se extiende, como mínimo, a fin del mes actual;
     * y si hay un ingreso en el mes siguiente, hasta ese ingreso (para poder
     * razonar sobre "el próximo ingreso ya seguro" y proteger ambas quincenas).
     */
    private function planningEnd(Carbon $today, ?Carbon $nextMonthFirstIncome): Carbon
    {
        $end = $today->copy()->endOfMonth()->startOfDay();

        if ($nextMonthFirstIncome && $nextMonthFirstIncome->gt($end)) {
            $end = $nextMonthFirstIncome->copy()->startOfDay();
        }

        return $end;
    }

    private function nextMonthFirstIncome(User $user, Carbon $today): ?Carbon
    {
        $nextStart = $today->copy()->startOfMonth()->addMonth();
        $nextEnd = $nextStart->copy()->endOfMonth();

        $income = ExpectedIncome::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $nextStart->toDateString())
            ->whereDate('due_date', '<=', $nextEnd->toDateString())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->first(fn (ExpectedIncome $i) => ((float) $i->amount - (float) $i->received_amount) > 0);

        return $income?->due_date?->copy()->startOfDay();
    }

    /**
     * @return array<int, Carbon>
     */
    private function incomeDatesWithin(Collection $daysByDate, Carbon $start, Carbon $end): array
    {
        return $daysByDate
            ->filter(fn (array $day) => (float) ($day['income_total'] ?? 0) > 0)
            ->keys()
            ->map(fn ($key) => Carbon::parse($key)->startOfDay())
            ->filter(fn (Carbon $date) => $date->betweenIncluded($start, $end))
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->values()
            ->all();
    }

    private function buildSegments(
        Carbon $today,
        Carbon $planningEnd,
        array $incomeDates,
        Collection $daysByDate,
        float $startingBalance,
        float $buffer
    ): array {
        $segments = [];
        $opening = $startingBalance;
        $index = 0;

        foreach ($this->quincenas($today, $planningEnd) as $quincena) {
            $boundaries = collect([$quincena['start']]);

            foreach ($incomeDates as $incomeDate) {
                if ($incomeDate->gt($quincena['start']) && $incomeDate->lte($quincena['end'])) {
                    $boundaries->push($incomeDate);
                }
            }

            $boundaries = $boundaries
                ->unique(fn (Carbon $date) => $date->toDateString())
                ->sortBy(fn (Carbon $date) => $date->timestamp)
                ->values();

            foreach ($boundaries as $i => $segStart) {
                $segEnd = isset($boundaries[$i + 1])
                    ? $boundaries[$i + 1]->copy()->subDay()
                    : $quincena['end']->copy();

                $segment = $this->segment($index++, $quincena, $segStart, $segEnd, $daysByDate, $opening, $buffer);
                $opening = $segment['closing_balance'];
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string, key: string}>
     */
    private function quincenas(Carbon $today, Carbon $planningEnd): array
    {
        $result = [];
        $month = $today->copy()->startOfMonth();
        $lastMonth = $planningEnd->copy()->startOfMonth();

        while ($month->lte($lastMonth)) {
            $halves = [
                ['q1', '1ª quincena', $month->copy()->startOfDay(), $month->copy()->day(15)->startOfDay()],
                ['q2', '2ª quincena', $month->copy()->day(16)->startOfDay(), $month->copy()->endOfMonth()->startOfDay()],
            ];

            foreach ($halves as [$half, $label, $start, $end]) {
                $start = $start->lt($today) ? $today->copy() : $start;
                $end = $end->gt($planningEnd) ? $planningEnd->copy() : $end;

                if ($start->lte($end)) {
                    $result[] = [
                        'start' => $start,
                        'end' => $end,
                        'label' => $label.' de '.$this->monthLabel($month),
                        'key' => $month->format('Y-m').'-'.$half,
                    ];
                }
            }

            $month->addMonth();
        }

        return $result;
    }

    private function segment(
        int $index,
        array $quincena,
        Carbon $start,
        Carbon $end,
        Collection $daysByDate,
        float $opening,
        float $buffer
    ): array {
        $income = 0.0;
        $incomeItems = [];
        $cashFlows = 0.0;
        $cashFlowItems = [];
        $cardCharges = 0.0;
        $cardItems = [];
        $creditDue = 0.0;
        $creditItems = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $day = $daysByDate->get($cursor->toDateString());

            if ($day) {
                foreach ($day['incomes'] ?? [] as $item) {
                    $income = $this->money($income + (float) $item['amount']);
                    $incomeItems[] = ['name' => $item['name'], 'amount' => $this->money((float) $item['amount']), 'date' => $day['date']];
                }
                foreach ($day['payments'] ?? [] as $item) {
                    $cashFlows = $this->money($cashFlows + (float) $item['amount']);
                    $cashFlowItems[] = ['name' => $item['name'], 'amount' => $this->money((float) $item['amount']), 'date' => $day['date']];
                }
                foreach ($day['card_charges'] ?? [] as $item) {
                    $cardCharges = $this->money($cardCharges + (float) $item['amount']);
                    $cardItems[] = [
                        'name' => $item['name'],
                        'amount' => $this->money((float) $item['amount']),
                        'card_account_name' => $item['card_account_name'] ?? 'Tarjeta',
                        'card_due_date' => $day['date'],
                        'charge_date' => $item['charge_date'] ?? null,
                    ];
                }
                foreach ($day['installments'] ?? [] as $item) {
                    $creditDue = $this->money($creditDue + (float) $item['amount']);
                    $creditItems[] = ['name' => $item['credit_name'] ?? 'Crédito', 'amount' => $this->money((float) $item['amount']), 'date' => $day['date']];
                }
            }

            $cursor->addDay();
        }

        $availableBeforeCredit = $this->money($opening + $income - $cashFlows - $cardCharges - $buffer);
        $closing = $this->money($opening + $income - $cashFlows - $cardCharges - $creditDue);

        return [
            'index' => $index,
            'quincena_key' => $quincena['key'],
            'quincena_label' => $quincena['label'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'opening_balance' => $this->money($opening),
            'income_total' => $income,
            'income_items' => $incomeItems,
            'cash_flows_total' => $cashFlows,
            'cash_flow_items' => $cashFlowItems,
            'card_charges_total' => $cardCharges,
            'card_charge_items' => $cardItems,
            'credit_due_total' => $creditDue,
            'credit_items' => $creditItems,
            'buffer' => $this->money($buffer),
            'available_before_credit' => $availableBeforeCredit,
            'closing_balance' => $closing,
            'cushion_ok' => $closing >= $buffer,
        ];
    }

    /**
     * Deuda de crédito (mensualidades) que vence dentro del mes en curso,
     * agrupada por cuenta/tarjeta. Es la "obligación del periodo" que la Fase 2
     * usará para decidir qué pagar y en qué sub-periodo. Solo referencia aquí.
     */
    private function currentMonthCreditAccounts(User $user, Carbon $today): array
    {
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        return CreditInstallment::with('creditPurchase.account')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->get()
            ->filter(function (CreditInstallment $installment) use ($monthStart, $monthEnd) {
                if (((float) $installment->amount - (float) $installment->paid_amount) <= 0) {
                    return false;
                }

                $due = $installment->due_date?->copy()->startOfDay()
                    ?? $installment->period_month?->copy()->endOfMonth()->startOfDay();

                return $due !== null && $due->betweenIncluded($monthStart, $monthEnd);
            })
            ->groupBy(function (CreditInstallment $installment) {
                return $installment->creditPurchase?->account_id
                    ? 'account:'.$installment->creditPurchase->account_id
                    : 'credit:'.$installment->credit_purchase_id;
            })
            ->map(function (Collection $items) {
                $first = $items->first();
                $account = $first->creditPurchase?->account;
                $total = $this->money($items->sum(fn (CreditInstallment $i) => max(0, (float) $i->amount - (float) $i->paid_amount)));
                $nextDue = $items
                    ->map(fn (CreditInstallment $i) => $i->due_date?->copy()->startOfDay())
                    ->filter()
                    ->sortBy(fn (Carbon $d) => $d->timestamp)
                    ->first();

                return [
                    'account_id' => $account?->id,
                    'account_name' => $account?->name ?? ($first->creditPurchase?->name ?? 'Sin cuenta'),
                    'month_due_total' => $total,
                    'next_due_date' => $nextDue?->toDateString(),
                    'credits_count' => $items->pluck('credit_purchase_id')->unique()->count(),
                ];
            })
            ->sortByDesc('month_due_total')
            ->values()
            ->all();
    }

    private function messages(array $segments, float $buffer): array
    {
        $messages = [];

        foreach ($segments as $segment) {
            if ($segment['income_total'] > 0) {
                $messages[] = 'El '.$this->formatDate($segment['start_date']).' entra '
                    .$this->formatMoney($segment['income_total']).'; con eso llegas a '
                    .$this->formatMoney($segment['closing_balance']).' al cierre de este tramo.';
            } elseif ($segment['cash_flows_total'] > 0) {
                $messages[] = 'De '.$this->formatDate($segment['start_date']).' a '.$this->formatDate($segment['end_date'])
                    .' aparta '.$this->formatMoney($segment['cash_flows_total']).' para tus flujos en efectivo; te quedas en '
                    .$this->formatMoney($segment['closing_balance']).'.';
            }

            if ($segment['card_charges_total'] > 0) {
                $messages[] = 'En este tramo se paga la tarjeta por '.$this->formatMoney($segment['card_charges_total'])
                    .' (flujos domiciliados a crédito); eso no era efectivo antes, ahora sí sale.';
            }

            if (! $segment['cushion_ok']) {
                $messages[] = 'Cuidado: en el tramo que cierra el '.$this->formatDate($segment['end_date'])
                    .' bajas de tu colchón de '.$this->formatMoney($buffer).'.';
            }
        }

        return $messages;
    }

    private function monthLabel(Carbon $date): string
    {
        return (self::MONTHS_ES[$date->month] ?? $date->format('m')).' '.$date->year;
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
