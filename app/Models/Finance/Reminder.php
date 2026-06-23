<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $table = 'finance_reminders';

    protected $fillable = [
        'user_id',
        'title',
        'reminder_type',
        'vehicle_type',
        'due_date',
        'amount',
        'recurrence',
        'notify_days_before',
        'status',
        'completed_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'notify_days_before' => 'integer',
            'completed_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
