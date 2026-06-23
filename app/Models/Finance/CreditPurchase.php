<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPurchase extends Model
{
    protected $table = 'finance_credit_purchases';

    protected $fillable = [
        'user_id',
        'purchase_date',
        'name',
        'total_amount',
        'months',
        'first_due_month',
        'due_day',
        'account_id',
        'category_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'first_due_month' => 'date',
            'total_amount' => 'decimal:2',
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

    public function installments(): HasMany
    {
        return $this->hasMany(CreditInstallment::class);
    }

    public function freePayments(): HasMany
    {
        return $this->hasMany(CreditFreePayment::class);
    }
}
