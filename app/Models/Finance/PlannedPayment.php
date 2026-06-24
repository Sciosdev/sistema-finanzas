<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannedPayment extends Model
{
    protected $table = 'finance_planned_payments';

    protected $fillable = [
        'user_id',
        'period_month',
        'due_date',
        'name',
        'amount',
        'paid_amount',
        'paid_on',
        'status',
        'account_id',
        'category_id',
        'person_id',
        'movement_id',
        'credit_purchase_id',
        'is_credit',
        'is_san_juan',
        'notes',
        'import_key',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'due_date' => 'date',
            'paid_on' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_credit' => 'boolean',
            'is_san_juan' => 'boolean',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function creditPurchase(): BelongsTo
    {
        return $this->belongsTo(CreditPurchase::class);
    }
}
