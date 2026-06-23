<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCutBalance extends Model
{
    protected $table = 'finance_daily_cut_balances';

    protected $fillable = [
        'daily_cut_id',
        'account_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function dailyCut(): BelongsTo
    {
        return $this->belongsTo(DailyCut::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
