<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditFreePaymentService
{
    public function createFreePayment(
        CreditPurchase $credit,
        Carbon $paidOn,
        float $amount,
        ?int $accountId = null,
        ?int $categoryId = null,
        ?string $notes = null,
        ?Movement $movement = null,
    ): CreditFreePayment {
        return DB::transaction(function () use ($credit, $paidOn, $amount, $accountId, $categoryId, $notes, $movement) {
            $credit = CreditPurchase::whereKey($credit->id)->lockForUpdate()->firstOrFail();
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new RuntimeException('El monto del abono libre debe ser mayor a cero.');
            }

            if ($movement) {
                if ((int) $movement->user_id !== (int) $credit->user_id || $movement->movement_type !== 'expense') {
                    throw new RuntimeException('El movimiento seleccionado no pertenece al crédito o no es un egreso.');
                }

                if ($amount - (float) $movement->amount > 0.01) {
                    throw new RuntimeException('El abono libre no puede ser mayor al movimiento seleccionado.');
                }
            } else {
                $movement = Movement::create([
                    'user_id' => $credit->user_id,
                    'happened_on' => $paidOn->toDateString(),
                    'movement_type' => 'expense',
                    'amount' => $amount,
                    'description' => 'Abono libre crédito: ' . $credit->name,
                    'account_id' => $accountId ?: $credit->account_id,
                    'category_id' => $categoryId ?: $credit->category_id,
                    'source' => 'credit_free_payment',
                    'notes' => $notes,
                ]);
            }

            $payment = CreditFreePayment::create([
                'user_id' => $credit->user_id,
                'credit_purchase_id' => $credit->id,
                'movement_id' => $movement->id,
                'amount_applied' => $amount,
                'paid_on' => $paidOn->toDateString(),
                'payment_type' => 'free_payment',
                'notes' => $notes,
            ]);

            $this->syncCreditStatus($credit);

            return $payment;
        });
    }

    public function deleteFreePayment(CreditFreePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $credit = CreditPurchase::whereKey($payment->credit_purchase_id)->lockForUpdate()->firstOrFail();
            $movement = $payment->movement;

            $payment->delete();

            if ($movement && $movement->source === 'credit_free_payment') {
                $movement->delete();
            }

            $this->syncCreditStatus($credit);
        });
    }

    public function totals(CreditPurchase $credit): array
    {
        $installmentPaid = round($credit->installments()->sum('paid_amount'), 2);
        $installmentTotal = round($credit->installments()->sum('amount'), 2);
        $freePaid = round($credit->freePayments()->sum('amount_applied'), 2);
        $total = round((float) $credit->total_amount, 2);
        $totalPaid = round($installmentPaid + $freePaid, 2);
        $pending = round(max(0, $total - $totalPaid), 2);

        return [
            'total_original' => $total,
            'installment_total' => $installmentTotal,
            'installment_paid' => $installmentPaid,
            'free_paid' => $freePaid,
            'total_paid' => min($total, $totalPaid),
            'total_paid_raw' => $totalPaid,
            'balance_due' => $pending,
            'status' => $this->statusFor($total, $totalPaid),
        ];
    }

    public function syncCreditStatus(CreditPurchase $credit): void
    {
        $totals = $this->totals($credit);

        $credit->update([
            'status' => $totals['status'],
        ]);
    }

    public function refreshCreditFromInstallments(CreditPurchase $credit): void
    {
        $installments = $credit->installments()->orderBy('installment_number')->get();

        if ($installments->isEmpty()) {
            $credit->delete();

            return;
        }

        $credit->update([
            'total_amount' => round($installments->sum(fn (CreditInstallment $installment) => (float) $installment->amount), 2),
            'months' => $installments->count(),
            'first_due_month' => $installments->first()->period_month->copy()->startOfMonth()->toDateString(),
        ]);

        $this->syncCreditStatus($credit);
    }

    private function statusFor(float $total, float $totalPaid): string
    {
        if ($total > 0 && $totalPaid + 0.01 >= $total) {
            return 'paid';
        }

        if ($totalPaid > 0) {
            return 'partially_paid';
        }

        return 'active';
    }
}
