<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpendingLimit extends Model
{
    protected $table = 'finance_spending_limits';

    protected $fillable = [
        'user_id',
        'category_id',
        'period_type',
        'limit_amount',
        'warning_threshold_percent',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'limit_amount' => 'decimal:2',
            'warning_threshold_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
