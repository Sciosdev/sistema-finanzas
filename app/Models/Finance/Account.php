<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'finance_accounts';

    public const TYPE_LABELS = [
        'cash' => 'Efectivo',
        'bank' => 'Banco',
        'card' => 'Tarjeta',
        'credit' => 'Crédito',
        'wallet' => 'Billetera',
        'other' => 'Otro',
    ];

    public const TYPE_ALIASES = [
        'efectivo' => 'cash',
        'banco' => 'bank',
        'tarjeta' => 'card',
        'credito' => 'credit',
        'crédito' => 'credit',
        'billetera' => 'wallet',
        'otro' => 'other',
        'cash' => 'cash',
        'bank' => 'bank',
        'card' => 'card',
        'credit' => 'credit',
        'wallet' => 'wallet',
        'other' => 'other',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'color',
        'opening_balance',
        'display_order',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function typeOptions(): array
    {
        return self::TYPE_LABELS;
    }

    public static function normalizeType(?string $type): string
    {
        $normalized = mb_strtolower(trim((string) $type));

        return self::TYPE_ALIASES[$normalized] ?? 'other';
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isCash(): bool
    {
        return self::normalizeType($this->type) === 'cash';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function cutBalances(): HasMany
    {
        return $this->hasMany(DailyCutBalance::class);
    }
}
