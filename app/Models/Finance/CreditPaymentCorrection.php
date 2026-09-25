<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class CreditPaymentCorrection extends Model
{
    protected $table = 'finance_credit_payment_corrections';

    protected $fillable = [
        'user_id', 'account_id', 'charge_credit_purchase_id', 'idempotency_key', 'request_hash', 'paid_total', 'amount',
        'concept', 'reason', 'source_installment_ids', 'before_state', 'after_state', 'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_total' => 'decimal:2', 'amount' => 'decimal:2',
            'source_installment_ids' => 'array', 'before_state' => 'array', 'after_state' => 'array',
            'reverted_at' => 'datetime',
        ];
    }
}
