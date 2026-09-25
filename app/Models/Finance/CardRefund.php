<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardRefund extends Model
{
    protected $table = 'finance_card_refunds';

    protected $fillable = [
        'user_id', 'account_id', 'reference_credit_purchase_id', 'received_on',
        'period_month', 'amount', 'description', 'notes', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'period_month' => 'date', 'amount' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function originalPurchase(): BelongsTo
    {
        return $this->belongsTo(CreditPurchase::class, 'reference_credit_purchase_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CreditFreePayment::class, 'card_refund_id');
    }
}
