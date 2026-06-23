<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'finance_categories';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'group',
        'color',
        'keywords',
        'is_san_juan',
        'is_rent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_san_juan' => 'boolean',
            'is_rent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }
}
