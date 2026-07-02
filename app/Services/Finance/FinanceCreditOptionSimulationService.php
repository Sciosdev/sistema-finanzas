<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditOption;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

class FinanceCreditOptionSimulationService
{
    private const RISK_RANK = ['ok' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];

    public function __construct(
        private readonly FinanceProjectionService $projectionService,
        private readonly FinancePaymentRecommendationService $recommendationService
    ) {}

    public function simulate(User $user, float $amount, int $horizonDays = 30, string $strategy = 'balanced'): array
    {
        if (! in_array($horizonDays, FinanceProjectionService::HORIZONS, true)) {
            throw new InvalidArgumentException('Horizonte inválido.');
        }

        if (! in_array($strategy, ['cheapest', 'lowest_monthly', 'safest_flow', 'balanced'], true)) {
            throw new InvalidArgumentException('Estrategia inválida.');
        }

        $amount = $this->money($amount);
        $projection = $this->projectionService->project($user, $horizonDays);
        $recommendations = $this->recommendationService->recommend($user, $horizonDays, $projection);

        $options = CreditOption::with('account')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (CreditOption $option) => $this->simulateOption($option, $amount, $projection))
            ->values()
            ->all();

        $availableOptions = array_values(array_filter($options, fn (array $option) => $option['available']));
        $ranking = $this->ranking($availableOptions, $strategy);
        $options = $this->withLabels($options, $ranking);

        return [
            'request' => [
                'amount' => $amount,
                'horizon_days' => $horizonDays,
                'strategy' => $strategy,
            ],
            'base' => [
                'available_safe_today' => $this->money((float) ($recommendations['available']['safe_today'] ?? 0)),
                'cash_needed_to_avoid_negative' => $this->money((float) ($recommendations['shortfall']['cash_needed_to_avoid_negative'] ?? 0)),
                'cash_needed_for_buffer' => $this->money((float) ($recommendations['shortfall']['cash_needed_for_buffer'] ?? 0)),
            ],
            'options' => $options,
            'ranking' => $ranking,
            'messages' => $this->messages($options, $ranking),
        ];
    }

    private function simulateOption(CreditOption $option, float $amount, array $projection): array
    {
        $unavailableReason = $this->unavailableReason($option, $amount);
        $paymentDay = $this->paymentDay($option);
        $firstPaymentDate = $this->firstPaymentDate($paymentDay);

        if ($unavailableReason !== null) {
            return $this->optionPayload($option, $amount, [
                'available' => false,
                'unavailable_reason' => $unavailableReason,
                'repayment_total' => 0.0,
                'total_cost' => 0.0,
                'cost_percent_effective' => 0.0,
                'monthly_payment' => 0.0,
                'first_payment_date' => $firstPaymentDate->toDateString(),
                'installments' => [],
                'simulation' => $this->emptySimulation($projection),
                'message' => $option->name.' no está disponible porque '.$unavailableReason.'.',
            ]);
        }

        $repaymentTotal = $this->repaymentTotal($option, $amount);
        $totalCost = $this->money($repaymentTotal - $amount);
        $termMonths = max(1, (int) $option->term_months);
        $installments = $this->installments($repaymentTotal, $termMonths, $firstPaymentDate, $paymentDay, $projection);
        $monthlyPayment = $this->money($installments[0]['amount'] ?? 0);
        $simulation = $this->simulateFlow($projection, $amount, $installments);

        return $this->optionPayload($option, $amount, [
            'available' => true,
            'unavailable_reason' => null,
            'repayment_total' => $repaymentTotal,
            'total_cost' => $totalCost,
            'cost_percent_effective' => $amount > 0 ? round(($totalCost / $amount) * 100, 2) : 0.0,
            'monthly_payment' => $monthlyPayment,
            'first_payment_date' => $firstPaymentDate->toDateString(),
            'installments' => $installments,
            'simulation' => $simulation,
            'message' => $option->name.' cuesta '.$this->formatMoney($totalCost).': recibes '.$this->formatMoney($amount).' y pagas '.$this->formatMoney($repaymentTotal).'.',
        ]);
    }

    private function optionPayload(CreditOption $option, float $amount, array $values): array
    {
        return [
            'id' => $option->id,
            'name' => $option->name,
            'provider' => $option->provider,
            'available' => $values['available'],
            'status' => $values['available'] ? 'available' : 'unavailable',
            'unavailable_reason' => $values['unavailable_reason'],
            'amount_received' => $amount,
            'repayment_total' => $values['repayment_total'],
            'total_cost' => $values['total_cost'],
            'cost_percent_effective' => $values['cost_percent_effective'],
            'term_months' => max(1, (int) $option->term_months),
            'monthly_payment' => $values['monthly_payment'],
            'first_payment_date' => $values['first_payment_date'],
            'installments' => $values['installments'],
            'simulation' => $values['simulation'],
            'labels' => [
                'cheapest' => false,
                'lowest_monthly' => false,
                'safest_flow' => false,
                'recommended' => false,
            ],
            'message' => $values['message'],
        ];
    }

    private function unavailableReason(CreditOption $option, float $amount): ?string
    {
        $availableAmount = $this->money((float) $option->available_amount);
        $minAmount = $this->money((float) $option->min_amount);

        if ($availableAmount <= 0) {
            return 'no tiene monto disponible configurado';
        }

        if ($amount > $availableAmount) {
            return 'el monto solicitado supera el disponible';
        }

        if ($amount < $minAmount) {
            return 'el monto solicitado es menor al mínimo permitido';
        }

        return null;
    }

    private function repaymentTotal(CreditOption $option, float $amount): float
    {
        $percentCost = $amount * ((float) $option->cost_percent / 100);
        $fixedFee = (float) $option->fixed_fee;

        return match ($option->cost_type) {
            'fixed_fee' => $this->money($amount + $fixedFee),
            'percent_plus_fee' => $this->money($amount + $percentCost + $fixedFee),
            default => $this->money($amount + $percentCost),
        };
    }

    private function paymentDay(CreditOption $option): int
    {
        return (int) ($option->payment_day ?: $option->account?->payment_day ?: 15);
    }

    private function firstPaymentDate(int $paymentDay): Carbon
    {
        $today = today()->startOfDay();
        $candidate = $this->dateForPaymentDay($today->copy()->startOfMonth(), $paymentDay);

        if ($candidate->lt($today)) {
            return $this->dateForPaymentDay($today->copy()->startOfMonth()->addMonth(), $paymentDay);
        }

        return $candidate;
    }

    private function dateForPaymentDay(Carbon $month, int $paymentDay): Carbon
    {
        return $month->copy()->day(min(max(1, $paymentDay), $month->daysInMonth))->startOfDay();
    }

    private function installments(float $repaymentTotal, int $termMonths, Carbon $firstPaymentDate, int $paymentDay, array $projection): array
    {
        $baseAmount = $this->money($repaymentTotal / $termMonths);
        $runningTotal = 0.0;
        $installments = [];
        $start = Carbon::parse($projection['meta']['start_date'])->startOfDay();
        $end = Carbon::parse($projection['meta']['end_date'])->startOfDay();

        for ($number = 1; $number <= $termMonths; $number++) {
            $dueDate = $number === 1
                ? $firstPaymentDate->copy()
                : $this->dateForPaymentDay($firstPaymentDate->copy()->startOfMonth()->addMonths($number - 1), $paymentDay);
            $amount = $number === $termMonths
                ? $this->money($repaymentTotal - $runningTotal)
                : $baseAmount;
            $runningTotal = $this->money($runningTotal + $amount);

            $installments[] = [
                'number' => $number,
                'due_date' => $dueDate->toDateString(),
                'amount' => $amount,
                'inside_horizon' => $dueDate->betweenIncluded($start, $end),
            ];
        }

        return $installments;
    }

    private function simulateFlow(array $projection, float $amount, array $installments): array
    {
        $buffer = $this->money((float) ($projection['meta']['buffer'] ?? 0));
        $installmentsByDate = [];

        foreach ($installments as $installment) {
            if ($installment['inside_horizon']) {
                $installmentsByDate[$installment['due_date']] = $this->money(($installmentsByDate[$installment['due_date']] ?? 0) + $installment['amount']);
            }
        }

        $delta = $amount;
        $minSafe = null;
        $minProjected = null;
        $firstRiskyDate = null;
        $firstHighDate = null;
        $firstCriticalDate = null;
        $maxRisk = 'ok';
        $endSafe = 0.0;
        $endProjected = 0.0;

        foreach ($projection['days'] as $day) {
            $date = $day['date'];
            $delta = $this->money($delta - (float) ($installmentsByDate[$date] ?? 0));
            $safe = $this->money((float) $day['closing_safe'] + $delta);
            $projected = $this->money((float) $day['closing_projected'] + $delta);
            $risk = $this->riskFor($safe, $projected, $buffer);

            if ($minSafe === null || $safe < $minSafe['balance']) {
                $minSafe = ['balance' => $safe, 'date' => $date];
            }

            if ($minProjected === null || $projected < $minProjected['balance']) {
                $minProjected = ['balance' => $projected, 'date' => $date];
            }

            if ($risk !== 'ok' && $firstRiskyDate === null) {
                $firstRiskyDate = $date;
            }

            if ($risk === 'high' && $firstHighDate === null) {
                $firstHighDate = $date;
            }

            if ($risk === 'critical' && $firstCriticalDate === null) {
                $firstCriticalDate = $date;
            }

            if (self::RISK_RANK[$risk] > self::RISK_RANK[$maxRisk]) {
                $maxRisk = $risk;
            }

            $endSafe = $safe;
            $endProjected = $projected;
        }

        $minProjectedBalance = $this->money((float) ($minProjected['balance'] ?? 0));

        return [
            'end_safe' => $endSafe,
            'end_projected' => $endProjected,
            'min_safe' => $this->money((float) ($minSafe['balance'] ?? 0)),
            'min_projected' => $minProjectedBalance,
            'min_safe_date' => $minSafe['date'] ?? null,
            'min_projected_date' => $minProjected['date'] ?? null,
            'first_risky_date' => $firstRiskyDate,
            'first_high_date' => $firstHighDate,
            'first_critical_date' => $firstCriticalDate,
            'max_risk' => $maxRisk,
            'cash_needed_to_avoid_negative' => $this->money(max(0, -$minProjectedBalance)),
            'cash_needed_for_buffer' => $this->money(max(0, $buffer - $minProjectedBalance)),
        ];
    }

    private function emptySimulation(array $projection): array
    {
        $summary = $projection['summary'];
        $buffer = $this->money((float) ($projection['meta']['buffer'] ?? 0));
        $minProjected = $this->money((float) ($summary['min_projected_balance'] ?? 0));

        return [
            'end_safe' => $this->money((float) ($summary['end_balance_safe'] ?? 0)),
            'end_projected' => $this->money((float) ($summary['end_balance_projected'] ?? 0)),
            'min_safe' => $this->money((float) ($summary['min_safe_balance'] ?? 0)),
            'min_projected' => $minProjected,
            'min_safe_date' => $summary['min_safe_date'] ?? null,
            'min_projected_date' => $summary['min_projected_date'] ?? null,
            'first_risky_date' => $summary['first_risky_date'] ?? null,
            'first_high_date' => null,
            'first_critical_date' => null,
            'max_risk' => $summary['max_risk'] ?? 'ok',
            'cash_needed_to_avoid_negative' => $this->money(max(0, -$minProjected)),
            'cash_needed_for_buffer' => $this->money(max(0, $buffer - $minProjected)),
        ];
    }

    private function riskFor(float $closingSafe, float $closingProjected, float $buffer): string
    {
        if ($closingProjected < 0) {
            return 'critical';
        }

        if ($closingProjected < $buffer) {
            return 'high';
        }

        if ($closingSafe < $buffer) {
            return 'medium';
        }

        return 'ok';
    }

    private function ranking(array $availableOptions, string $strategy): array
    {
        $cheapest = $this->firstSorted($availableOptions, [
            fn (array $option) => $option['total_cost'],
            fn (array $option) => $option['monthly_payment'],
        ]);
        $lowestMonthly = $this->firstSorted($availableOptions, [
            fn (array $option) => $option['monthly_payment'],
            fn (array $option) => $option['total_cost'],
        ]);
        $safestFlow = $this->firstSorted($availableOptions, [
            fn (array $option) => self::RISK_RANK[$option['simulation']['max_risk']],
            fn (array $option) => $option['simulation']['cash_needed_for_buffer'],
            fn (array $option) => -$option['simulation']['min_projected'],
            fn (array $option) => $option['monthly_payment'],
        ]);

        $recommended = match ($strategy) {
            'cheapest' => $cheapest,
            'lowest_monthly' => $lowestMonthly,
            'safest_flow' => $safestFlow,
            default => $this->balancedRecommendation($availableOptions),
        };

        return [
            'cheapest_option_id' => $cheapest['id'] ?? null,
            'lowest_monthly_option_id' => $lowestMonthly['id'] ?? null,
            'safest_flow_option_id' => $safestFlow['id'] ?? null,
            'recommended_option_id' => $recommended['id'] ?? null,
        ];
    }

    private function balancedRecommendation(array $availableOptions): ?array
    {
        $nonCritical = array_values(array_filter(
            $availableOptions,
            fn (array $option) => $option['simulation']['max_risk'] !== 'critical'
        ));

        return $this->firstSorted($nonCritical ?: $availableOptions, [
            fn (array $option) => $option['simulation']['cash_needed_for_buffer'],
            fn (array $option) => $option['total_cost'],
            fn (array $option) => $option['monthly_payment'],
        ]);
    }

    private function firstSorted(array $options, array $callbacks): ?array
    {
        if ($options === []) {
            return null;
        }

        usort($options, function (array $left, array $right) use ($callbacks) {
            foreach ($callbacks as $callback) {
                $comparison = $callback($left) <=> $callback($right);

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return $left['id'] <=> $right['id'];
        });

        return $options[0];
    }

    private function withLabels(array $options, array $ranking): array
    {
        return array_map(function (array $option) use ($ranking) {
            $option['labels'] = [
                'cheapest' => $option['id'] === $ranking['cheapest_option_id'],
                'lowest_monthly' => $option['id'] === $ranking['lowest_monthly_option_id'],
                'safest_flow' => $option['id'] === $ranking['safest_flow_option_id'],
                'recommended' => $option['id'] === $ranking['recommended_option_id'],
            ];

            return $option;
        }, $options);
    }

    private function messages(array $options, array $ranking): array
    {
        $byId = [];
        foreach ($options as $option) {
            $byId[$option['id']] = $option;
        }

        $messages = [];
        if ($ranking['cheapest_option_id'] && isset($byId[$ranking['cheapest_option_id']])) {
            $option = $byId[$ranking['cheapest_option_id']];
            $messages[] = 'La opción más barata es '.$option['name'].': cuesta '.$this->formatMoney($option['total_cost']).'.';
        }

        if ($ranking['lowest_monthly_option_id'] && isset($byId[$ranking['lowest_monthly_option_id']])) {
            $option = $byId[$ranking['lowest_monthly_option_id']];
            $messages[] = 'La mensualidad más baja es '.$option['name'].': '.$this->formatMoney($option['monthly_payment']).' durante '.$option['term_months'].' meses.';
        }

        if ($ranking['safest_flow_option_id'] && isset($byId[$ranking['safest_flow_option_id']])) {
            $option = $byId[$ranking['safest_flow_option_id']];
            $messages[] = 'La opción más segura para tu flujo es '.$option['name'].' porque reduce el impacto mensual.';
        }

        foreach ($options as $option) {
            if (! $option['available']) {
                $messages[] = $option['name'].' no está disponible porque '.$option['unavailable_reason'].'.';
            } elseif ($option['simulation']['cash_needed_for_buffer'] > 0) {
                $messages[] = 'Aunque '.$option['name'].' cubre el faltante, todavía quedarías debajo del colchón.';
            }
        }

        $messages[] = 'Esta simulación no crea deuda real; solo compara escenarios.';

        return array_values(array_unique($messages));
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
