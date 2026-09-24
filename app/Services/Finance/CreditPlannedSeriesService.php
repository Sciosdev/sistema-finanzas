<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditInstallment;
use App\Models\Finance\PlannedPayment;
use Illuminate\Support\Facades\DB;

class CreditPlannedSeriesService
{
    public function __construct(private readonly CreditEffectiveScheduleService $schedule) {}

    /** Reutiliza una serie ya confirmada cuando se crea otra copia del pago. */
    public function linkNewCopy(PlannedPayment $payment): bool
    {
        if ($payment->credit_installment_id) {
            return true;
        }

        $templates = PlannedPayment::with('creditInstallment')
            ->where('user_id', $payment->user_id)
            ->whereDate('period_month', '<', $payment->period_month->toDateString())
            ->where('name', $payment->name)
            ->where('amount', $payment->amount)
            ->where('category_id', $payment->category_id)
            ->where('person_id', $payment->person_id)
            ->where('account_id', $payment->account_id)
            ->whereNotNull('credit_installment_id')
            ->orderBy('period_month')
            ->get();
        $credits = $templates->pluck('creditInstallment.credit_purchase_id')->filter()->unique();

        if ($credits->count() !== 1) {
            return false;
        }

        $this->linkFuture($templates->first());

        return (bool) $payment->fresh()->credit_installment_id;
    }

    /**
     * Extiende una conciliación explícita a las copias futuras de ese mismo pago.
     * Solo vincula pares únicos, pendientes y del mismo mes e importe.
     */
    public function linkFuture(PlannedPayment $template): int
    {
        return DB::transaction(function () use ($template) {
            $template = PlannedPayment::with('creditInstallment')
                ->whereKey($template->id)->lockForUpdate()->firstOrFail();
            $source = $template->creditInstallment;
            if (! $source) {
                return 0;
            }

            $installments = CreditInstallment::where('user_id', $template->user_id)
                ->where('credit_purchase_id', $source->credit_purchase_id)
                ->whereDate('period_month', '>', $template->period_month->toDateString())
                ->orderBy('period_month')
                ->lockForUpdate()
                ->get();

            $groups = $installments->groupBy(fn (CreditInstallment $installment) =>
                $installment->period_month->format('Y-m').'|'.number_format((float) $installment->amount, 2, '.', '')
            );
            $linked = 0;

            foreach ($groups as $group) {
                if ($group->count() !== 1) {
                    continue;
                }

                $installment = $group->first();
                if (! in_array($installment->status, ['pending', 'overdue'], true)
                    || (float) $installment->paid_amount > 0
                    || $installment->movement_id
                    || (float) $installment->amount !== (float) $template->amount
                    || abs($this->schedule->effectivePending($installment) - (float) $template->amount) >= 0.005
                    || PlannedPayment::where('credit_installment_id', $installment->id)->exists()) {
                    continue;
                }

                $matches = PlannedPayment::where('user_id', $template->user_id)
                    ->whereDate('period_month', $installment->period_month->toDateString())
                    ->where('name', $template->name)
                    ->where('amount', $template->amount)
                    ->where('category_id', $template->category_id)
                    ->where('person_id', $template->person_id)
                    ->where('account_id', $template->account_id)
                    ->whereNull('credit_installment_id')
                    ->whereNull('credit_purchase_id')
                    ->whereNull('movement_id')
                    ->where('is_credit', false)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->where('paid_amount', 0)
                    ->lockForUpdate()
                    ->get();

                if ($matches->count() !== 1) {
                    continue;
                }

                $matches->first()->update(['credit_installment_id' => $installment->id]);
                $linked++;
            }

            return $linked;
        });
    }
}
