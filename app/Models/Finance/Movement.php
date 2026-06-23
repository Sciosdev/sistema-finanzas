<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    protected $table = 'finance_movements';

    protected $fillable = [
        'user_id',
        'happened_on',
        'movement_type',
        'amount',
        'description',
        'account_id',
        'category_id',
        'person_id',
        'is_san_juan',
        'is_rent',
        'is_unknown',
        'source',
        'import_key',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'happened_on' => 'date',
            'amount' => 'decimal:2',
            'is_san_juan' => 'boolean',
            'is_rent' => 'boolean',
            'is_unknown' => 'boolean',
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
}
