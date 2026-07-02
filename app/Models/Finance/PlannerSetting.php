<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannerSetting extends Model
{
    protected $table = 'finance_planner_settings';

    protected $fillable = [
        'user_id',
        'minimum_buffer',
        'count_overdue_income',
        'use_daily_spend_estimate',
    ];

    protected function casts(): array
    {
        return [
            'minimum_buffer' => 'decimal:2',
            'count_overdue_income' => 'boolean',
            'use_daily_spend_estimate' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
