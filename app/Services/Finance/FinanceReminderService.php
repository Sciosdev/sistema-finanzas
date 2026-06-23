<?php

namespace App\Services\Finance;

use App\Models\Finance\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinanceReminderService
{
    public const TYPES = [
        'refrendo' => 'Refrendo',
        'verificacion' => 'Verificación',
        'recurring_payment' => 'Pago recurrente',
        'other' => 'Otro',
    ];

    public const VEHICLES = [
        'car' => 'Carro',
        'motorcycle' => 'Moto',
        'other' => 'Otro',
    ];

    public const RECURRENCES = [
        'none' => 'Una sola vez',
        'monthly' => 'Mensual',
        'quarterly' => 'Trimestral',
        'semiannual' => 'Semestral',
        'annual' => 'Anual',
    ];

    public function dashboardReminders(User $user): array
    {
        $today = now()->startOfDay();
        $upcoming = $this->upcomingForUser($user, 8);

        return [
            'upcoming' => $upcoming,
            'pending_total' => Reminder::where('user_id', $user->id)->where('status', 'pending')->count(),
            'overdue_total' => Reminder::where('user_id', $user->id)
                ->where('status', 'pending')
                ->whereDate('due_date', '<', $today->toDateString())
                ->count(),
            'soon_total' => $upcoming->filter(fn (Reminder $reminder) => $this->isSoon($reminder))->count(),
        ];
    }

    public function upcomingForUser(User $user, int $limit = 25): Collection
    {
        return Reminder::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function complete(Reminder $reminder, ?Carbon $completedOn = null): ?Reminder
    {
        $completedOn ??= now();

        $reminder->update([
            'status' => 'done',
            'completed_on' => $completedOn->toDateString(),
        ]);

        $nextDate = $this->nextDueDate($reminder);

        if (! $nextDate) {
            return null;
        }

        return Reminder::create([
            'user_id' => $reminder->user_id,
            'title' => $reminder->title,
            'reminder_type' => $reminder->reminder_type,
            'vehicle_type' => $reminder->vehicle_type,
            'due_date' => $nextDate->toDateString(),
            'amount' => $reminder->amount,
            'recurrence' => $reminder->recurrence,
            'notify_days_before' => $reminder->notify_days_before,
            'status' => 'pending',
            'notes' => $reminder->notes,
        ]);
    }

    public function nextDueDate(Reminder $reminder): ?Carbon
    {
        $date = $reminder->due_date?->copy();

        if (! $date || $reminder->recurrence === 'none') {
            return null;
        }

        return match ($reminder->recurrence) {
            'monthly' => $date->addMonthNoOverflow(),
            'quarterly' => $date->addMonthsNoOverflow(3),
            'semiannual' => $date->addMonthsNoOverflow(6),
            'annual' => $date->addYearNoOverflow(),
            default => null,
        };
    }

    public function isSoon(Reminder $reminder): bool
    {
        $today = now()->startOfDay();
        $dueDate = $reminder->due_date?->copy()->startOfDay();

        if (! $dueDate) {
            return false;
        }

        return $dueDate->gte($today)
            && $today->diffInDays($dueDate, false) <= $reminder->notify_days_before;
    }
}
