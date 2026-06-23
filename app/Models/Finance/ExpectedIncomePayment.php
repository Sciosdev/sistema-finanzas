<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpectedIncomePayment extends Model
{
    protected $table = 'finance_expected_income_payments';

    protected $fillable = [
        'user_id',
        'expected_income_id',
        'movement_id',
        'amount_applied',
        'paid_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_applied' => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expectedIncome(): BelongsTo
    {
        return $this->belongsTo(ExpectedIncome::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }
}
