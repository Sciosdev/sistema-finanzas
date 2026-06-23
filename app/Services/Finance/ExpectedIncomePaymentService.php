<?php

namespace App\Services\Finance;

use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\ExpectedIncomePayment;
use App\Models\Finance\Movement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExpectedIncomePaymentService
{
    public function addMovementPayment(ExpectedIncome $income, Movement $movement, ?float $amount = null, ?string $notes = null): ExpectedIncomePayment
    {
        if ((int) $income->user_id !== (int) $movement->user_id || $movement->movement_type !== 'income') {
            throw new RuntimeException('El movimiento no pertenece al ingreso esperado o no es un ingreso.');
        }

        return DB::transaction(function () use ($income, $movement, $amount, $notes) {
            $income = ExpectedIncome::whereKey($income->id)->lockForUpdate()->firstOrFail();
            $remaining = $this->remainingAmount($income);
            $amountApplied = round((float) ($amount ?? min((float) $movement->amount, $remaining)), 2);

            if ($amountApplied <= 0) {
                throw new RuntimeException('El monto del abono debe ser mayor a cero.');
            }

            if ($amountApplied - (float) $movement->amount > 0.01) {
                throw new RuntimeException('El abono no puede ser mayor al movimiento seleccionado.');
            }

            $payment = ExpectedIncomePayment::create([
                'user_id' => $income->user_id,
                'expected_income_id' => $income->id,
                'movement_id' => $movement->id,
                'amount_applied' => $amountApplied,
                'paid_on' => $movement->happened_on?->toDateString(),
                'notes' => $notes,
            ]);

            $this->syncIncome($income);

            return $payment;
        });
    }

    public function createMovementAndPayment(ExpectedIncome $income, Carbon $paidOn, float $amount, ?int $accountId = null, ?string $notes = null): ExpectedIncomePayment
    {
        return DB::transaction(function () use ($income, $paidOn, $amount, $accountId, $notes) {
            $income = ExpectedIncome::with(['category', 'person'])->whereKey($income->id)->lockForUpdate()->firstOrFail();
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new RuntimeException('El monto del abono debe ser mayor a cero.');
            }

            $remaining = $this->remainingAmount($income);
            $descriptionPrefix = $amount + 0.01 >= $remaining
                ? 'Ingreso esperado: '
                : 'Abono ingreso esperado: ';

            $movement = Movement::create([
                'user_id' => $income->user_id,
                'happened_on' => $paidOn->toDateString(),
                'movement_type' => 'income',
                'amount' => $amount,
                'description' => $descriptionPrefix . $income->name,
                'account_id' => $accountId ?: $income->account_id,
                'category_id' => $income->category_id,
                'person_id' => $income->person_id,
                'is_san_juan' => $income->is_rent,
                'is_rent' => $income->is_rent,
                'source' => 'expected_income_payment',
                'notes' => $notes,
            ]);

            return $this->addMovementPayment($income, $movement, $amount, $notes);
        });
    }

    public function addRegisteredPayment(ExpectedIncome $income, Carbon $paidOn, float $amount, ?string $notes = null): ExpectedIncomePayment
    {
        return DB::transaction(function () use ($income, $paidOn, $amount, $notes) {
            $income = ExpectedIncome::whereKey($income->id)->lockForUpdate()->firstOrFail();
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new RuntimeException('El monto del abono debe ser mayor a cero.');
            }

            $payment = ExpectedIncomePayment::create([
                'user_id' => $income->user_id,
                'expected_income_id' => $income->id,
                'movement_id' => null,
                'amount_applied' => $amount,
                'paid_on' => $paidOn->toDateString(),
                'notes' => $notes,
            ]);

            $this->syncIncome($income);

            return $payment;
        });
    }

    public function deletePayment(ExpectedIncomePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $income = ExpectedIncome::whereKey($payment->expected_income_id)->lockForUpdate()->firstOrFail();
            $payment->delete();
            $this->syncIncome($income);
        });
    }

    public function unlinkAll(ExpectedIncome $income): void
    {
        DB::transaction(function () use ($income) {
            $income = ExpectedIncome::whereKey($income->id)->lockForUpdate()->firstOrFail();
            $income->payments()->delete();
            $this->syncIncome($income, forcePending: true);
        });
    }

    public function syncIncome(ExpectedIncome $income, bool $forcePending = false): ExpectedIncome
    {
        $income->loadMissing('payments');

        $received = round($income->payments()->sum('amount_applied'), 2);
        $amount = round((float) $income->amount, 2);
        $lastPaidOn = $income->payments()->whereNotNull('paid_on')->max('paid_on');
        $firstMovementId = $income->payments()
            ->whereNotNull('movement_id')
            ->orderBy('paid_on')
            ->orderBy('id')
            ->value('movement_id');

        $status = 'pending';
        if (! $forcePending && $income->status === 'skipped' && $received <= 0) {
            $status = 'skipped';
        } elseif ($received + 0.01 >= $amount) {
            $status = 'received';
        } elseif ($received > 0) {
            $status = 'partial';
        } elseif ($income->due_date && $income->due_date->copy()->startOfDay()->lt(today()->startOfDay())) {
            $status = 'overdue';
        }

        $income->update([
            'received_amount' => min($received, $amount),
            'received_on' => $lastPaidOn,
            'status' => $status,
            'movement_id' => $firstMovementId,
        ]);

        return $income->refresh();
    }

    public function remainingAmount(ExpectedIncome $income): float
    {
        $received = round($income->payments()->sum('amount_applied'), 2);

        return round(max(0, (float) $income->amount - $received), 2);
    }
}
