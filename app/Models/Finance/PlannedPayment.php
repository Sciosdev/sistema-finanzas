<?php

namespace App\Models\Finance;

use App\Models\User;
use Carbon\Carbon;
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
        'is_automatic_charge',
        'is_forced_charge_window',
        'charge_window_before_days',
        'charge_window_after_days',
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
            'is_automatic_charge' => 'boolean',
            'is_forced_charge_window' => 'boolean',
            'charge_window_before_days' => 'integer',
            'charge_window_after_days' => 'integer',
        ];
    }

    public function hasForcedChargeWindow(): bool
    {
        return (bool) $this->is_automatic_charge
            && (bool) $this->is_forced_charge_window
            && $this->due_date !== null;
    }

    public function chargeWindowStart(): ?Carbon
    {
        if (! $this->hasForcedChargeWindow()) {
            return null;
        }

        return $this->due_date
            ->copy()
            ->startOfDay()
            ->subDays(max(0, (int) $this->charge_window_before_days));
    }

    public function chargeWindowEnd(): ?Carbon
    {
        if (! $this->hasForcedChargeWindow()) {
            return null;
        }

        return $this->due_date
            ->copy()
            ->startOfDay()
            ->addDays(max(0, (int) $this->charge_window_after_days));
    }

    public function isInChargeWindow(Carbon $date): bool
    {
        $start = $this->chargeWindowStart();
        $end = $this->chargeWindowEnd();

        return $start !== null
            && $end !== null
            && $date->copy()->startOfDay()->betweenIncluded($start, $end);
    }

    public function isBeforeChargeWindow(Carbon $date): bool
    {
        $start = $this->chargeWindowStart();

        return $start !== null && $date->copy()->startOfDay()->lt($start);
    }

    public function isAfterChargeWindow(Carbon $date): bool
    {
        $end = $this->chargeWindowEnd();

        return $end !== null && $date->copy()->startOfDay()->gt($end);
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
