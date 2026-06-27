<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'credit_limit',
        'statement_day',
        'payment_day',
        'display_order',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'statement_day' => 'integer',
            'payment_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Indica si la tarjeta tiene configurado su ciclo (día de corte y de pago).
     */
    public function hasCreditCycle(): bool
    {
        return ! empty($this->statement_day) && ! empty($this->payment_day);
    }

    /**
     * Calcula la fecha real de pago de una compra hecha con esta tarjeta.
     *
     * Regla: si la compra ocurre en/antes del día de corte, entra en el estado
     * de cuenta de ese mes; si ocurre después, pasa al siguiente. El pago cae en
     * el día de pago; si ese día es menor o igual al de corte, se paga el mes
     * siguiente al corte, si no, el mismo mes.
     */
    public function firstDueDateFor(Carbon $purchaseDate): ?Carbon
    {
        if (! $this->hasCreditCycle()) {
            return null;
        }

        $statementDay = (int) $this->statement_day;
        $paymentDay = (int) $this->payment_day;

        $closeMonth = $purchaseDate->day <= $statementDay
            ? $purchaseDate->copy()->startOfMonth()
            : $purchaseDate->copy()->startOfMonth()->addMonth();

        $dueMonth = $paymentDay <= $statementDay
            ? $closeMonth->copy()->addMonth()
            : $closeMonth->copy();

        return $dueMonth->day(min($paymentDay, $dueMonth->daysInMonth));
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
