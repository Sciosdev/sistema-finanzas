<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Detecta (solo lectura) lo que está incompleto, vencido, sin ligar o con
 * posible error, para que el usuario pueda navegar a la pantalla correcta y
 * corregirlo a mano.
 *
 * No modifica datos, no aplica correcciones automáticas y no cambia ningún
 * cálculo financiero existente: únicamente consulta, todo filtrado por user_id.
 */
class FinancePendingResolutionService
{
    /**
     * @return array{groups: array<int, array<string, mixed>>, summary: array{total: int, groups: array<string, int>}}
     */
    public function run(User $user): array
    {
        $today = today()->startOfDay();

        $groups = [
            $this->group('movements_without_category', 'Movimientos sin categoría', 'tag', 'Ir a movimientos', $this->movementsWithoutCategory($user)),
            $this->group('movements_unknown', 'Movimientos marcados como desconocidos', 'help-circle', 'Editar movimiento', $this->movementsUnknown($user)),
            $this->group('movements_without_person', 'Movimientos de renta/San Juan sin persona', 'user-x', 'Editar movimiento', $this->movementsWithoutPerson($user)),
            $this->group('planned_overdue', 'Pagos planeados vencidos sin pagar', 'calendar-x', 'Ir a flujo planeado', $this->plannedOverdue($user, $today)),
            $this->group('planned_paid_unlinked', 'Pagos planeados marcados como pagados sin movimiento ligado', 'unlink', 'Vincular movimiento', $this->plannedPaidUnlinked($user)),
            $this->group('expected_incomes_overdue', 'Ingresos esperados vencidos no recibidos', 'calendar-clock', 'Ir a ingresos esperados', $this->expectedIncomesOverdue($user, $today)),
            $this->group('expected_incomes_partial', 'Ingresos esperados parciales con saldo pendiente', 'circle-dollar-sign', 'Ir a ingresos esperados', $this->expectedIncomesPartial($user)),
            $this->group('credit_installments_overdue', 'Mensualidades de crédito vencidas sin pagar', 'credit-card', 'Ir a créditos', $this->creditInstallmentsOverdue($user, $today)),
            $this->group('credit_free_payments_unlinked', 'Abonos libres de crédito sin movimiento ligado', 'unlink', 'Ir a créditos', $this->creditFreePaymentsUnlinked($user)),
            $this->group('cuts_with_difference', 'Cortes con diferencia de conciliación', 'scale', 'Ir a cortes', $this->cutsWithDifference($user)),
            $this->group('san_juan_pending', 'Rentas San Juan pendientes, parciales o vencidas', 'home', 'Ir a San Juan', $this->sanJuanPending($user, $today)),
        ];

        $summaryGroups = [];
        $total = 0;

        foreach ($groups as $group) {
            $summaryGroups[$group['key']] = $group['count'];
            $total += $group['count'];
        }

        return [
            'groups' => $groups,
            'summary' => [
                'total' => $total,
                'groups' => $summaryGroups,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function group(string $key, string $title, string $icon, string $defaultAction, array $items): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'icon' => $icon,
            'default_action' => $defaultAction,
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function movementsWithoutCategory(User $user): array
    {
        return Movement::query()
            ->where('user_id', $user->id)
            ->whereNull('category_id')
            ->orderByDesc('happened_on')
            ->limit(200)
            ->get()
            ->map(fn (Movement $movement) => $this->item(
                tipo: 'Movimiento sin categoría',
                descripcion: $this->movementDescription($movement),
                monto: (float) $movement->amount,
                fecha: $movement->happened_on,
                estado: 'Sin categoría',
                accion: 'Editar movimiento',
                url: route('finance.movements.edit', $movement->id),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function movementsUnknown(User $user): array
    {
        return Movement::query()
            ->where('user_id', $user->id)
            ->whereNotNull('category_id')
            ->where(function ($query) {
                $query->where('is_unknown', true)
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'Desconocido'));
            })
            ->orderByDesc('happened_on')
            ->limit(200)
            ->get()
            ->map(fn (Movement $movement) => $this->item(
                tipo: 'Movimiento desconocido',
                descripcion: $this->movementDescription($movement),
                monto: (float) $movement->amount,
                fecha: $movement->happened_on,
                estado: 'Marcado como desconocido',
                accion: 'Editar movimiento',
                url: route('finance.movements.edit', $movement->id),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function movementsWithoutPerson(User $user): array
    {
        return Movement::query()
            ->where('user_id', $user->id)
            ->whereNull('person_id')
            ->where(function ($query) {
                $query->where('is_rent', true)->orWhere('is_san_juan', true);
            })
            ->orderByDesc('happened_on')
            ->limit(200)
            ->get()
            ->map(fn (Movement $movement) => $this->item(
                tipo: 'Movimiento de renta sin persona',
                descripcion: $this->movementDescription($movement),
                monto: (float) $movement->amount,
                fecha: $movement->happened_on,
                estado: 'Falta asignar persona',
                accion: 'Editar movimiento',
                url: route('finance.movements.edit', $movement->id),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plannedOverdue(User $user, Carbon $today): array
    {
        return PlannedPayment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->orderBy('due_date')
            ->get()
            ->filter(fn (PlannedPayment $payment) => ((float) $payment->amount - (float) $payment->paid_amount) > 0.0)
            ->map(fn (PlannedPayment $payment) => $this->item(
                tipo: 'Pago planeado vencido',
                descripcion: (string) $payment->name,
                monto: (float) $payment->amount - (float) $payment->paid_amount,
                fecha: $payment->due_date,
                estado: 'Vencido sin pagar',
                accion: 'Ir a flujo planeado',
                url: route('finance.planned.index', ['month' => $this->monthParam($payment->period_month)]),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plannedPaidUnlinked(User $user): array
    {
        return PlannedPayment::query()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereNull('movement_id')
            ->orderByDesc('paid_on')
            ->get()
            ->map(fn (PlannedPayment $payment) => $this->item(
                tipo: 'Pago planeado sin movimiento',
                descripcion: (string) $payment->name,
                monto: (float) $payment->paid_amount,
                fecha: $payment->paid_on,
                estado: 'Pagado sin movimiento ligado',
                accion: 'Vincular movimiento',
                url: route('finance.planned.link', $payment->id),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function expectedIncomesOverdue(User $user, Carbon $today): array
    {
        return ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->where('is_rent', false)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->orderBy('due_date')
            ->get()
            ->map(fn (ExpectedIncome $income) => $this->item(
                tipo: 'Ingreso esperado vencido',
                descripcion: (string) $income->name,
                monto: (float) $income->amount - (float) $income->received_amount,
                fecha: $income->due_date,
                estado: 'Vencido no recibido',
                accion: 'Ir a ingresos esperados',
                url: route('finance.expected-incomes.index', ['month' => $this->monthParam($income->period_month)]),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function expectedIncomesPartial(User $user): array
    {
        return ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->where('is_rent', false)
            ->where('status', 'partial')
            ->orderBy('due_date')
            ->get()
            ->map(fn (ExpectedIncome $income) => $this->item(
                tipo: 'Ingreso esperado parcial',
                descripcion: (string) $income->name,
                monto: (float) $income->amount - (float) $income->received_amount,
                fecha: $income->due_date,
                estado: 'Saldo pendiente por recibir',
                accion: 'Ir a ingresos esperados',
                url: route('finance.expected-incomes.index', ['month' => $this->monthParam($income->period_month)]),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function creditInstallmentsOverdue(User $user, Carbon $today): array
    {
        return CreditInstallment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->with('creditPurchase')
            ->orderBy('due_date')
            ->get()
            ->filter(fn (CreditInstallment $installment) => ((float) $installment->amount - (float) $installment->paid_amount) > 0.0)
            ->map(fn (CreditInstallment $installment) => $this->item(
                tipo: 'Mensualidad vencida',
                descripcion: trim(($installment->creditPurchase?->name ?? 'Crédito') . ' · mensualidad ' . $installment->installment_number),
                monto: (float) $installment->amount - (float) $installment->paid_amount,
                fecha: $installment->due_date,
                estado: 'Vencida sin pagar',
                accion: 'Ir a créditos',
                url: route('finance.credits.index'),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function creditFreePaymentsUnlinked(User $user): array
    {
        return CreditFreePayment::query()
            ->where('user_id', $user->id)
            ->whereNull('movement_id')
            ->with('creditPurchase')
            ->orderByDesc('paid_on')
            ->get()
            ->map(fn (CreditFreePayment $payment) => $this->item(
                tipo: 'Abono libre sin movimiento',
                descripcion: (string) ($payment->creditPurchase?->name ?? 'Crédito'),
                monto: (float) $payment->amount_applied,
                fecha: $payment->paid_on,
                estado: 'Sin movimiento ligado',
                accion: 'Ir a créditos',
                url: route('finance.credits.index'),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cutsWithDifference(User $user): array
    {
        return DailyCut::query()
            ->where('user_id', $user->id)
            ->whereNotNull('difference')
            ->where('difference', '<>', 0)
            ->orderByDesc('cut_date')
            ->get()
            ->map(fn (DailyCut $cut) => $this->item(
                tipo: 'Corte con diferencia',
                descripcion: 'Corte del ' . optional($cut->cut_date)->format('d/m/Y'),
                monto: (float) $cut->difference,
                fecha: $cut->cut_date,
                estado: 'Diferencia de conciliación distinta de 0',
                accion: 'Ir a cortes',
                url: route('finance.cuts.index'),
            ))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sanJuanPending(User $user, Carbon $today): array
    {
        return ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->where('is_rent', true)
            ->where(function ($query) use ($today) {
                $query->where('status', 'partial')
                    ->orWhere(function ($overdue) use ($today) {
                        $overdue->whereIn('status', ['pending', 'overdue'])
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', $today->toDateString());
                    });
            })
            ->orderBy('due_date')
            ->get()
            ->map(fn (ExpectedIncome $income) => $this->item(
                tipo: 'Renta San Juan pendiente',
                descripcion: (string) $income->name,
                monto: (float) $income->amount - (float) $income->received_amount,
                fecha: $income->due_date,
                estado: $income->status === 'partial' ? 'Renta parcial' : 'Renta vencida',
                accion: 'Ir a San Juan',
                url: route('finance.san-juan.index', ['month' => $this->monthParam($income->period_month)]),
            ))
            ->all();
    }

    private function movementDescription(Movement $movement): string
    {
        $description = trim((string) $movement->description);

        return $description !== '' ? $description : 'Movimiento sin descripción';
    }

    private function monthParam(mixed $month): ?string
    {
        return $month ? Carbon::parse($month)->format('Y-m') : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $tipo,
        string $descripcion,
        ?float $monto,
        mixed $fecha,
        string $estado,
        string $accion,
        string $url,
    ): array {
        return [
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'monto' => $monto,
            'fecha' => $fecha ? Carbon::parse($fecha) : null,
            'estado' => $estado,
            'accion' => $accion,
            'url' => $url,
        ];
    }
}
