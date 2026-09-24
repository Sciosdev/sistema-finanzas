<?php

namespace App\Models\Finance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function plannedPayment(): HasOne
    {
        return $this->hasOne(PlannedPayment::class, 'credit_installment_id');
    }

    public function plannedPaymentDate(): ?Carbon
    {
        // Las cuotas ya pagadas conservan su fecha histórica; la regla del
        // crédito gobierna todas las cuotas que aún quedan por pagar.
        if ($this->status === 'paid') {
            return $this->plannedPayment?->due_date?->copy();
        }

        $day = $this->creditPurchase?->planned_payment_day;
        if ($day && $this->period_month) {
            $period = $this->period_month->copy()->startOfMonth();

            return $period->day(min((int) $day, $period->daysInMonth));
        }

        return $this->plannedPayment?->due_date?->copy();
    }

    public function effectiveDueDate(): ?Carbon
    {
        $planned = $this->plannedPaymentDate();
        $contractual = $this->due_date?->copy();

        return $planned && $contractual
            ? ($planned->lt($contractual) ? $planned : $contractual)
            : ($planned ?? $contractual);
    }
}
