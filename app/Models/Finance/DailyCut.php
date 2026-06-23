<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyCut extends Model
{
    protected $table = 'finance_daily_cuts';

    protected $fillable = [
        'user_id',
        'cut_date',
        'expected_leftover',
        'cash_amount',
        'cards_amount',
        'real_total',
        'pending_payments',
        'difference',
        'amount_missing',
        'status',
        'notes',
        'import_key',
    ];

    protected function casts(): array
    {
        return [
            'cut_date' => 'date',
            'expected_leftover' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'cards_amount' => 'decimal:2',
            'real_total' => 'decimal:2',
            'pending_payments' => 'decimal:2',
            'difference' => 'decimal:2',
            'amount_missing' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(DailyCutBalance::class);
    }
}
