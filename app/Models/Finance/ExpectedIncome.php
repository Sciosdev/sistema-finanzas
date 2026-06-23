<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpectedIncome extends Model
{
    protected $table = 'finance_expected_incomes';

    protected $fillable = [
        'user_id',
        'period_month',
        'due_date',
        'name',
        'amount',
        'received_amount',
        'received_on',
        'status',
        'account_id',
        'category_id',
        'person_id',
        'movement_id',
        'is_rent',
        'notes',
        'import_key',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'due_date' => 'date',
            'received_on' => 'date',
            'amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'is_rent' => 'boolean',
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
}
