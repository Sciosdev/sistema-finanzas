<?php

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Sobres semanales por categoría (Fase 3 del rediseño del Planificador).
 *
 * Reparte el "dinero para vivir" del mes en curso (lo que queda tras flujos,
 * cargos a tarjeta, crédito y colchón, según el motor de periodos) en semanas
 * anidadas dentro de cada quincena, de modo que ninguna quincena se quede en $0.
 * Cada semana tiene un tope y ese tope se reparte por categoría según lo que se
 * gastó el MES PASADO (detección de patrón).
 *
 * Tradeoff cruzado: en la semana en curso, si ya se gastó el tope semanal (aunque
 * sea en una sola categoría), el disponible efectivo de las demás categorías baja
 * a 0 — "si te gastaste los $500 en saldo, ya no gastes en ropa".
 *
 * Solo lectura: no crea movimientos ni cambia estados.
 */
class FinanceWeeklyEnvelopeService
{
    private const DEBT_KEYWORDS = ['deuda', 'credito', 'creditos', 'tarjeta', 'prestamo', 'mensualidad'];

    private const MONTHS_ES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function __construct(private readonly FinancePeriodPlanService $periodPlanService) {}

    public function build(User $user): array
    {
        $today = today()->startOfDay();
        $monthEnd = $today->copy()->endOfMonth()->startOfDay();
        $totalDays = max(1, ((int) $today->diffInDays($monthEnd)) + 1);

        $plan = $this->periodPlanService->build($user);
        $buffer = $this->money((float) ($plan['meta']['buffer'] ?? 0));
        $livingPool = $this->livingPool($plan, $today, $buffer);
        $dailyCap = $this->money($livingPool / $totalDays);

        $weights = $this->categoryWeights($user, $today);
        $spentThisWeekByCategory = null; // se llena al topar con la semana en curso

        $weeks = [];
        foreach ($this->weekWindows($today, $monthEnd) as $index => $window) {
            $isCurrent = $today->betweenIncluded($window['start'], $window['end']);
            $weekDays = ((int) $window['start']->diffInDays($window['end'])) + 1;
            $weekCap = $this->money($dailyCap * $weekDays);

            $spent = [];
            if ($isCurrent) {
                $spentThisWeekByCategory ??= $this->spentByCategory($user, $window['start'], $window['end']);
                $spent = $spentThisWeekByCategory;
            }

            $weeks[$index] = $this->week($index, $window, $isCurrent, $weekDays, $weekCap, $weights, $spent);
        }

        $currentWeek = collect($weeks)->firstWhere('is_current', true);

        return [
            'meta' => [
                'today' => $today->toDateString(),
                'current_month_end' => $monthEnd->toDateString(),
                'living_pool_month' => $livingPool,
                'buffer' => $buffer,
                'total_days' => $totalDays,
                'daily_cap' => $dailyCap,
                'weeks_count' => count($weeks),
                'has_historical_basis' => collect($weights)->contains(fn (array $w) => (float) $w['last_month_spent'] > 0),
            ],
            'category_weights' => array_values($weights),
            'weeks' => array_values($weeks),
            'current_week' => $currentWeek,
            'pattern_advice' => $this->patternAdvice($weights),
            'messages' => $this->messages($livingPool, $currentWeek, $buffer),
        ];
    }

    /**
     * Dinero libre para vivir del mes: el punto más bajo de la pista de efectivo
     * de los tramos del mes en curso, menos el colchón (nunca negativo).
     */
    private function livingPool(array $plan, Carbon $today, float $buffer): float
    {
        $monthKey = $today->format('Y-m');
        $currentMonthClosings = collect($plan['segments'])
            ->filter(fn (array $segment) => Str::startsWith($segment['start_date'], $monthKey))
            ->pluck('closing_balance');

        if ($currentMonthClosings->isEmpty()) {
            return 0.0;
        }

        return $this->money(max(0, $currentMonthClosings->min() - $buffer));
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, quincena_label: string}>
     */
    private function weekWindows(Carbon $today, Carbon $monthEnd): array
    {
        $windows = [];

        foreach ($this->quincenas($today, $monthEnd) as $quincena) {
            $cursor = $quincena['start']->copy();

            while ($cursor->lte($quincena['end'])) {
                $weekEnd = $cursor->copy()->addDays(6);
                if ($weekEnd->gt($quincena['end'])) {
                    $weekEnd = $quincena['end']->copy();
                }

                $windows[] = [
                    'start' => $cursor->copy(),
                    'end' => $weekEnd->copy(),
                    'quincena_label' => $quincena['label'],
                ];

                $cursor = $weekEnd->copy()->addDay();
            }
        }

        return $windows;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    private function quincenas(Carbon $today, Carbon $monthEnd): array
    {
        $month = $today->copy()->startOfMonth();
        $result = [];

        $halves = [
            ['1ª quincena', $month->copy()->startOfDay(), $month->copy()->day(15)->startOfDay()],
            ['2ª quincena', $month->copy()->day(16)->startOfDay(), $monthEnd->copy()],
        ];

        foreach ($halves as [$label, $start, $end]) {
            $start = $start->lt($today) ? $today->copy() : $start;
            $end = $end->gt($monthEnd) ? $monthEnd->copy() : $end;

            if ($start->lte($end)) {
                $result[] = [
                    'start' => $start,
                    'end' => $end,
                    'label' => $label.' de '.$this->monthLabel($month),
                ];
            }
        }

        return $result;
    }

    private function week(int $index, array $window, bool $isCurrent, int $weekDays, float $weekCap, array $weights, array $spent): array
    {
        $categories = [];
        $spentTotal = $this->money(array_sum($spent));
        $remainingWeekTotal = $this->money(max(0, $weekCap - $spentTotal));

        foreach ($weights as $weight) {
            $envelope = $this->money($weekCap * ((float) $weight['weight_percent'] / 100));
            $row = [
                'category_id' => $weight['category_id'],
                'category_name' => $weight['category_name'],
                'weight_percent' => (float) $weight['weight_percent'],
                'envelope' => $envelope,
            ];

            if ($isCurrent) {
                $spentCat = $this->money((float) ($spent[$weight['category_id'] ?? '__null__'] ?? 0));
                $ownRemaining = $this->money(max(0, $envelope - $spentCat));
                // Tradeoff cruzado: aunque quede sobre en la categoría, si el tope
                // semanal ya se consumió, el disponible efectivo baja a 0.
                $effectiveRemaining = $this->money(min($ownRemaining, $remainingWeekTotal));

                $row['spent'] = $spentCat;
                $row['own_remaining'] = $ownRemaining;
                $row['effective_remaining'] = $effectiveRemaining;
                $row['over_envelope'] = $spentCat > $envelope;
            }

            $categories[] = $row;
        }

        return [
            'index' => $index,
            'start_date' => $window['start']->toDateString(),
            'end_date' => $window['end']->toDateString(),
            'quincena_label' => $window['quincena_label'],
            'days' => $weekDays,
            'is_current' => $isCurrent,
            'week_cap' => $weekCap,
            'spent_total' => $isCurrent ? $spentTotal : 0.0,
            'remaining_total' => $isCurrent ? $remainingWeekTotal : $weekCap,
            'tradeoff_active' => $isCurrent && $spentTotal >= $weekCap && $weekCap > 0,
            'categories' => $categories,
        ];
    }

    /**
     * Peso por categoría según el gasto del MES PASADO (patrón). Excluye
     * categorías de deuda/crédito y gastos marcados san juan o renta.
     *
     * @return array<int|string, array{category_id: ?int, category_name: string, weight_percent: float, last_month_spent: float}>
     */
    private function categoryWeights(User $user, Carbon $today): array
    {
        $start = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $end = $today->copy()->subMonthNoOverflow()->endOfMonth();

        $rows = Movement::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereNotNull('category_id')
            ->whereDate('happened_on', '>=', $start->toDateString())
            ->whereDate('happened_on', '<=', $end->toDateString())
            ->where(fn ($q) => $q->where('is_san_juan', false)->orWhereNull('is_san_juan'))
            ->where(fn ($q) => $q->where('is_rent', false)->orWhereNull('is_rent'))
            ->get()
            ->reject(fn (Movement $movement) => $this->isDebtCategory($movement->category))
            ->groupBy('category_id')
            ->map(function (Collection $movements) {
                $category = $movements->first()->category;

                return [
                    'category_id' => $category?->id,
                    'category_name' => $category?->name ?? 'Sin categoría',
                    'last_month_spent' => $this->money($movements->sum(fn (Movement $m) => (float) $m->amount)),
                ];
            })
            ->values();

        $total = $this->money($rows->sum('last_month_spent'));

        if ($rows->isEmpty() || $total <= 0) {
            return [
                '__default__' => [
                    'category_id' => null,
                    'category_name' => 'Gastos diarios',
                    'weight_percent' => 100.0,
                    'last_month_spent' => 0.0,
                ],
            ];
        }

        return $rows
            ->sortByDesc('last_month_spent')
            ->mapWithKeys(function (array $row) use ($total) {
                $key = $row['category_id'] ?? '__null__';

                return [$key => array_merge($row, [
                    'weight_percent' => round(((float) $row['last_month_spent'] / $total) * 100, 2),
                ])];
            })
            ->all();
    }

    /**
     * @return array<int|string, float> gasto de la semana por category_id
     */
    private function spentByCategory(User $user, Carbon $start, Carbon $end): array
    {
        return Movement::query()
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereNotNull('category_id')
            ->whereDate('happened_on', '>=', $start->toDateString())
            ->whereDate('happened_on', '<=', $end->toDateString())
            ->where(fn ($q) => $q->where('is_san_juan', false)->orWhereNull('is_san_juan'))
            ->where(fn ($q) => $q->where('is_rent', false)->orWhereNull('is_rent'))
            ->get()
            ->reject(fn (Movement $movement) => $this->isDebtCategory($movement->category))
            ->groupBy('category_id')
            ->map(fn (Collection $movements) => $this->money($movements->sum(fn (Movement $m) => (float) $m->amount)))
            ->all();
    }

    private function patternAdvice(array $weights): array
    {
        $advice = [];
        $top = collect($weights)->filter(fn (array $w) => (float) $w['last_month_spent'] > 0)->take(3);

        foreach ($top as $weight) {
            $advice[] = 'El mes pasado gastaste '.$this->formatMoney((float) $weight['last_month_spent']).' en '
                .$weight['category_name'].' ('.$this->money((float) $weight['weight_percent']).'% de tu gasto). Cuida ese rubro este mes.';
        }

        return $advice;
    }

    private function messages(float $livingPool, ?array $currentWeek, float $buffer): array
    {
        $messages = [];

        if ($livingPool <= 0) {
            $messages[] = 'Este mes no queda dinero libre para gastar sin bajar de tu colchón de '.$this->formatMoney($buffer).'.';

            return $messages;
        }

        $messages[] = 'Tienes '.$this->formatMoney($livingPool).' para vivir este mes; se reparte por semanas para no quedarte en $0 en ninguna quincena.';

        if ($currentWeek) {
            $messages[] = 'Esta semana ('.$this->formatDate($currentWeek['start_date']).' a '.$this->formatDate($currentWeek['end_date'])
                .') tu tope es '.$this->formatMoney((float) $currentWeek['week_cap']).'; te quedan '
                .$this->formatMoney((float) $currentWeek['remaining_total']).'.';

            if ($currentWeek['tradeoff_active']) {
                $messages[] = 'Ya usaste el tope de esta semana; aunque tengas sobre en otra categoría, mejor ya no gastes para no comerte la otra quincena.';
            }
        }

        return $messages;
    }

    private function isDebtCategory(?Category $category): bool
    {
        if (! $category) {
            return false;
        }

        $text = Str::lower(Str::ascii(implode(' ', array_filter([
            $category->name,
            $category->group,
            $category->keywords,
        ]))));

        return Str::contains($text, self::DEBT_KEYWORDS);
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
