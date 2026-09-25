<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditFreePayment extends Model
{
    protected $table = 'finance_credit_free_payments';

    protected $fillable = [
        'user_id',
        'credit_purchase_id',
        'movement_id',
        'amount_applied',
        'paid_on',
        'payment_type',
        'notes',
        'card_refund_id',
        'target_installment_id',
        'allocation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'amount_applied' => 'decimal:2',
            'paid_on' => 'date',
            'allocation_snapshot' => 'array',
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

    public function cardRefund(): BelongsTo
    {
        return $this->belongsTo(CardRefund::class);
    }

    public function targetInstallment(): BelongsTo
    {
        return $this->belongsTo(CreditInstallment::class, 'target_installment_id');
    }
}
