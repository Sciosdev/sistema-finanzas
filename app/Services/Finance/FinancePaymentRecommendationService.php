<?php

namespace App\Services\Finance;

use App\Models\User;
use Carbon\Carbon;

class FinancePaymentRecommendationService
{
    public function __construct(private readonly FinanceProjectionService $projectionService) {}

    public function recommend(User $user, int $horizonDays, ?array $projection = null): array
    {
        $projection ??= $this->projectionService->project($user, $horizonDays);

        return $this->recommendFromProjection($projection);
    }

    public function recommendFromProjection(array $projection): array
    {
        $days = $projection['days'] ?? [];
        $summary = $projection['summary'] ?? [];
        $buffer = $this->money((float) ($projection['meta']['buffer'] ?? 0));
        $dayOne = $days[0] ?? null;

        $availableSafeToday = $this->money(max(0, (float) ($dayOne['closing_safe'] ?? 0) - $buffer));
        $availableProjectedToday = $this->money(max(0, (float) ($dayOne['closing_projected'] ?? 0) - $buffer));

        $minProjectedBalance = $this->money((float) ($summary['min_projected_balance'] ?? 0));
        $cashNeededToAvoidNegative = $this->money(max(0, -$minProjectedBalance));
        $cashNeededForBuffer = $this->money(max(0, $buffer - $minProjectedBalance));

        $firstHighDate = $this->firstDateWithRisk($days, 'high');
        $firstCriticalDate = $this->firstDateWithRisk($days, 'critical');

        $recommendations = [
            'pay_now' => [],
            'upcoming' => [],
            'wait_for_income' => [],
            'risky_payments' => [],
            'overdue_income_to_collect' => $this->overdueIncomeItems($summary['overdue_income_items'] ?? []),
        ];

        $upcomingByDate = [];

        foreach ($days as $index => $day) {
            $isDayOne = $index === 0;
            $date = (string) ($day['date'] ?? '');
            $risk = (string) ($day['risk'] ?? 'ok');
            $events = array_merge(
                $this->paymentItems($day['payments'] ?? [], 'payment', $date, $risk, $isDayOne),
                $this->paymentItems($day['installments'] ?? [], 'installment', $date, $risk, $isDayOne)
            );

            foreach ($events as $event) {
                if ($isDayOne) {
                    $recommendations['pay_now'][] = $event;
                } else {
                    $upcomingByDate[$date][] = $event;
                }

                if ($risk === 'medium') {
                    $recommendations['wait_for_income'][] = array_merge($event, [
                        'reason' => 'Depende de que entren ingresos esperados.',
                    ]);
                }

                if (in_array($risk, ['high', 'critical'], true)) {
                    $recommendations['risky_payments'][] = array_merge($event, [
                        'reason' => 'Puede romper el colchón o dejarte negativo.',
                    ]);
                }
            }
        }

        foreach ($upcomingByDate as $date => $items) {
            $recommendations['upcoming'][] = [
                'date' => $date,
                'items' => $items,
            ];
        }

        $shortfall = [
            'cash_needed_to_avoid_negative' => $cashNeededToAvoidNegative,
            'cash_needed_for_buffer' => $cashNeededForBuffer,
            'first_risky_date' => $summary['first_risky_date'] ?? null,
            'first_high_date' => $firstHighDate,
            'first_critical_date' => $firstCriticalDate,
            'min_safe_date' => $summary['min_safe_date'] ?? null,
            'min_projected_date' => $summary['min_projected_date'] ?? null,
        ];

        return [
            'available' => [
                'safe_today' => $availableSafeToday,
                'projected_today' => $availableProjectedToday,
            ],
            'shortfall' => $shortfall,
            'recommendations' => $recommendations,
            'messages' => $this->messages($availableSafeToday, $availableProjectedToday, $shortfall, $recommendations),
        ];
    }

    private function firstDateWithRisk(array $days, string $risk): ?string
    {
        foreach ($days as $day) {
            if (($day['risk'] ?? null) === $risk) {
                return $day['date'] ?? null;
            }
        }

        return null;
    }

    private function paymentItems(array $events, string $type, string $date, string $risk, bool $isDayOne): array
    {
        $items = [];

        foreach ($events as $event) {
            $items[] = [
                'type' => $type,
                'id' => $event['id'] ?? null,
                'name' => $this->eventName($event, $type),
                'amount' => $this->money((float) ($event['amount'] ?? 0)),
                'date' => $date,
                'reason' => $this->reason($event, $risk, $isDayOne),
                'is_overdue' => (bool) ($event['is_overdue'] ?? false),
                'risk_after_payment' => $risk,
            ];
        }

        return $items;
    }

    private function eventName(array $event, string $type): string
    {
        if ($type === 'payment') {
            return (string) ($event['name'] ?? 'Pago planeado');
        }

        $creditName = (string) ($event['credit_name'] ?? 'Crédito');
        $label = $event['installment_label'] ?? null;

        return $label ? $creditName.' ('.$label.')' : $creditName;
    }

    private function reason(array $event, string $risk, bool $isDayOne): string
    {
        if ((bool) ($event['is_overdue'] ?? false)) {
            return 'Vencido y cae en el día de hoy.';
        }

        if ($isDayOne && array_key_exists('has_due_date', $event) && ! $event['has_due_date']) {
            return 'No tenía fecha y se asignó a hoy.';
        }

        if ($isDayOne) {
            return 'Vence hoy.';
        }

        if ($risk === 'medium') {
            return 'Depende de que entren ingresos esperados.';
        }

        if (in_array($risk, ['high', 'critical'], true)) {
            return 'Puede romper el colchón o dejarte negativo.';
        }

        return 'Próximo pago dentro del horizonte.';
    }

    private function overdueIncomeItems(array $items): array
    {
        return array_map(function (array $item): array {
            return [
                'type' => 'income',
                'id' => $item['id'] ?? null,
                'name' => (string) ($item['name'] ?? 'Ingreso esperado'),
                'amount' => $this->money((float) ($item['amount'] ?? 0)),
                'due_date' => $item['due_date'] ?? null,
                'reason' => 'Ingreso vencido por cobrar; no se cuenta como dinero seguro.',
            ];
        }, $items);
    }

    private function messages(
        float $availableSafeToday,
        float $availableProjectedToday,
        array $shortfall,
        array $recommendations
    ): array {
        $messages = [
            'Puedes gastar hasta '.$this->formatMoney($availableSafeToday).' hoy sin romper tu colchón.',
            'Considerando ingresos proyectados, hoy podrías gastar hasta '.$this->formatMoney($availableProjectedToday).'.',
        ];

        if ($shortfall['first_risky_date']) {
            $messages[] = 'Tu primer día de riesgo es el '.$this->formatDate($shortfall['first_risky_date']).'.';
        }

        if ($shortfall['cash_needed_to_avoid_negative'] > 0) {
            $messages[] = 'Necesitas conseguir '.$this->formatMoney($shortfall['cash_needed_to_avoid_negative']).' para no quedar negativo.';
        }

        if ($shortfall['cash_needed_for_buffer'] > 0) {
            $messages[] = 'Necesitas conseguir '.$this->formatMoney($shortfall['cash_needed_for_buffer']).' para mantener tu colchón.';
        }

        $overdueIncomeTotal = $this->money(array_sum(array_column($recommendations['overdue_income_to_collect'], 'amount')));
        if ($overdueIncomeTotal > 0) {
            $messages[] = 'Tienes '.$this->formatMoney($overdueIncomeTotal).' vencidos por cobrar que no se están contando como dinero seguro.';
        }

        if (count($recommendations['wait_for_income']) > 0) {
            $messages[] = 'Hay pagos que dependen de que lleguen ingresos esperados.';
        }

        if (count($recommendations['risky_payments']) > 0) {
            $messages[] = 'Hay pagos riesgosos que pueden romper tu colchón o dejarte negativo.';
        }

        return $messages;
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
