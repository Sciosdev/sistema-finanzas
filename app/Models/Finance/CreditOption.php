<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditOption extends Model
{
    protected $table = 'finance_credit_options';

    public const COST_TYPES = ['total_percent', 'fixed_fee', 'percent_plus_fee'];

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'provider',
        'available_amount',
        'min_amount',
        'cost_type',
        'cost_percent',
        'fixed_fee',
        'term_months',
        'payment_day',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'available_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'cost_percent' => 'decimal:4',
            'fixed_fee' => 'decimal:2',
            'term_months' => 'integer',
            'payment_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
