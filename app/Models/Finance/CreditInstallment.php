<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditInstallment extends Model
{
    protected $table = 'finance_credit_installments';

    protected $fillable = [
        'credit_purchase_id',
        'user_id',
        'period_month',
        'due_date',
        'installment_number',
        'amount',
        'paid_amount',
        'paid_on',
        'status',
        'movement_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'due_date' => 'date',
            'paid_on' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPurchase(): BelongsTo
    {
        return $this->belongsTo(CreditPurchase::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }
}
