<?php

namespace App\Support;

class FinanceLabels
{
    public static function movementType(?string $type): string
    {
        return match ($type) {
            'expense' => 'Gasto',
            'income' => 'Ingreso',
            'yield' => 'Rendimiento',
            'transfer' => 'Transferencia',
            'adjustment' => 'Ajuste',
            default => 'Sin tipo',
        };
    }

    public static function categoryType(?string $type): string
    {
        return match ($type) {
            'expense' => 'Gasto',
            'income' => 'Ingreso',
            'yield' => 'Rendimiento',
            default => 'Sin tipo',
        };
    }

    public static function creditStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Activo',
            'open' => 'Activo',
            'paid' => 'Pagado',
            'cancelled' => 'Cancelado',
            default => 'En revisión',
        };
    }

    public static function dueLabel(mixed $dueDate, ?string $status = null): string
    {
        if ($status === 'paid') {
            return 'Pagado';
        }

        if ($status === 'skipped') {
            return 'No pagado';
        }

        if (! $dueDate) {
            return $status === 'overdue' ? 'Vencido' : 'Sin fecha';
        }

        $days = today()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);

        if ($days < 0) {
            $daysLate = abs((int) $days);

            return $daysLate === 1 ? 'Venció ayer' : 'Vencido hace ' . $daysLate . ' días';
        }

        if ($status === 'overdue') {
            return 'Vencido';
        }

        if ((int) $days === 0) {
            return 'Hoy';
        }

        if ((int) $days === 1) {
            return 'Mañana';
        }

        return 'En ' . (int) $days . ' días';
    }

    public static function dueBadgeClass(mixed $dueDate, ?string $status = null): string
    {
        if ($status === 'paid') {
            return 'badge-soft-success';
        }

        if ($status === 'skipped') {
            return 'badge-soft-danger';
        }

        if ($status === 'overdue') {
            return 'badge-soft-danger';
        }

        if (! $dueDate) {
            return 'badge-soft-secondary';
        }

        $days = today()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);

        if ($days < 0) {
            return 'badge-soft-danger';
        }

        if ($days <= 2) {
            return 'badge-soft-warning';
        }

        return 'badge-soft-primary';
    }
}
