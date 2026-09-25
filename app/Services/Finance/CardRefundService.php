<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CardRefund;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Records an actual bank refund, without inventing a cash receipt or payment. */
class CardRefundService
{
    public function __construct(
        private readonly CreditEffectiveScheduleService $schedule,
        private readonly CreditFreePaymentService $payments,
    ) {}

    public function create(
        User $user,
        Account $account,
        Carbon $receivedOn,
        Carbon $periodMonth,
        float $amount,
        string $description,
        string $notes,
        string $idempotencyKey,
        ?CreditPurchase $originalPurchase = null,
    ): CardRefund {
        if (! is_finite($amount) || $this->cents($amount) <= 0) {
            throw new RuntimeException('La devolución debe ser mayor a cero.');
        }
        if (trim($description) === '' || mb_strlen($description) > 255 || trim($notes) === '') {
            throw new RuntimeException('Indica el concepto de la devolución y la evidencia o motivo.');
        }
        if (trim($idempotencyKey) === '' || mb_strlen($idempotencyKey) > 80) {
            throw new RuntimeException('La referencia de la devolución no es válida.');
        }

        return DB::transaction(function () use ($user, $account, $receivedOn, $periodMonth, $amount, $description, $notes, $idempotencyKey, $originalPurchase) {
            $account = Account::whereKey($account->id)->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $account) {
                throw new RuntimeException('La tarjeta no pertenece a este usuario.');
            }
            if ($originalPurchase && ! CreditPurchase::whereKey($originalPurchase->id)
                ->where('user_id', $user->id)->where('account_id', $account->id)->exists()) {
                throw new RuntimeException('La compra de referencia no pertenece a esta tarjeta.');
            }

            $data = [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'reference_credit_purchase_id' => $originalPurchase?->id,
                'received_on' => $receivedOn->toDateString(),
                'period_month' => $periodMonth->copy()->startOfMonth()->toDateString(),
                'amount' => (float) ($this->cents($amount) / 100),
                'description' => trim($description),
                'notes' => trim($notes),
                'idempotency_key' => trim($idempotencyKey),
            ];
            $existing = CardRefund::where('user_id', $user->id)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                foreach ($data as $field => $value) {
                    $stored = match ($field) {
                        'received_on', 'period_month' => $existing->$field->toDateString(),
                        'amount' => (float) $existing->$field,
                        default => $existing->$field,
                    };
                    if ($stored !== $value) {
                        throw new RuntimeException('Esta referencia ya se registró con otros datos. Revisa la devolución existente.');
                    }
                }

                return $existing->load('allocations');
            }

            $credits = CreditPurchase::where('user_id', $user->id)->where('account_id', $account->id)
                ->orderBy('id')->lockForUpdate()->get();
            $installments = CreditInstallment::whereIn('credit_purchase_id', $credits->pluck('id'))
                ->orderBy('id')->lockForUpdate()->get()->groupBy('credit_purchase_id');
            $credits->load('freePayments');
            $this->schedule->flush();
            $candidates = [];
            $creditRoom = [];
            foreach ($credits as $credit) {
                $credit->setRelation('installments', $installments->get($credit->id, collect()));
                $pending = $this->schedule->effectivePendingFor($credit);
                $creditRoom[$credit->id] = $this->cents($this->schedule->balanceDue($credit));
                foreach ($credit->installments as $installment) {
                    if ($installment->period_month?->toDateString() !== $data['period_month']) {
                        continue;
                    }
                    $room = $this->cents($pending[$installment->id] ?? 0);
                    if ($room > 0) {
                        $candidates[] = ['installment' => $installment, 'room' => $room];
                    }
                }
            }

            // This is an internal allocation of card credit, not identification
            // of the purchase refunded by the bank. Never spill into another month.
            usort($candidates, fn ($a, $b) => [
                $a['installment']->due_date?->toDateString() ?? '9999-12-31', $a['installment']->id,
            ] <=> [
                $b['installment']->due_date?->toDateString() ?? '9999-12-31', $b['installment']->id,
            ]);
            $remaining = $this->cents($amount);
            $allocations = [];
            foreach ($candidates as $candidate) {
                $installment = $candidate['installment'];
                $applied = min($remaining, $candidate['room'], $creditRoom[$installment->credit_purchase_id]);
                if ($applied <= 0) {
                    continue;
                }
                $allocations[] = ['installment' => $installment, 'amount' => $applied];
                $creditRoom[$installment->credit_purchase_id] -= $applied;
                $remaining -= $applied;
                if ($remaining === 0) {
                    break;
                }
            }
            if ($remaining !== 0) {
                throw new RuntimeException('La devolución supera el pendiente de esta tarjeta en el periodo seleccionado.');
            }
            $linkedPlan = PlannedPayment::whereIn('credit_installment_id',
                collect($allocations)->map(fn ($allocation) => $allocation['installment']->id))
                ->lockForUpdate()->first();
            if ($linkedPlan) {
                throw new RuntimeException('Una mensualidad que recibiría esta devolución está vinculada a un pago planeado. Desvincúlalo antes de registrar la devolución.');
            }

            $refund = CardRefund::create($data);
            foreach ($allocations as $allocation) {
                $installment = $allocation['installment'];
                $refund->allocations()->create([
                    'user_id' => $user->id,
                    'credit_purchase_id' => $installment->credit_purchase_id,
                    'target_installment_id' => $installment->id,
                    'movement_id' => null,
                    'amount_applied' => $allocation['amount'] / 100,
                    'paid_on' => $data['received_on'],
                    'payment_type' => 'refund',
                    'allocation_snapshot' => $this->snapshot($installment),
                    'notes' => $data['description'].': '.$data['notes'],
                ]);
            }
            $changedIds = collect($allocations)->map(fn ($a) => $a['installment']->credit_purchase_id)->unique();
            foreach ($credits->whereIn('id', $changedIds) as $credit) {
                $this->schedule->flush($credit->id);
                $this->payments->syncCreditStatus($credit);
            }

            return $refund->load('allocations');
        }, 3);
    }

    public function delete(CardRefund $refund): void
    {
        DB::transaction(function () use ($refund) {
            $refund = CardRefund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $allocations = $refund->allocations()->orderBy('credit_purchase_id')->get();
            $credits = CreditPurchase::whereIn('id', $allocations->pluck('credit_purchase_id'))
                ->where('user_id', $refund->user_id)->orderBy('id')->lockForUpdate()->get();
            foreach ($allocations as $allocation) {
                $installment = CreditInstallment::whereKey($allocation->target_installment_id)
                    ->where('user_id', $refund->user_id)->lockForUpdate()->first();
                if (! $installment || $installment->status === 'paid'
                    || $this->snapshot($installment) !== $allocation->allocation_snapshot) {
                    throw new RuntimeException('La devolución ya se utilizó en un pago o su mensualidad cambió; no se puede eliminar directamente.');
                }
                if (CreditFreePayment::where('credit_purchase_id', $allocation->credit_purchase_id)
                    ->where(fn ($query) => $query->whereNull('card_refund_id')->orWhere('card_refund_id', '!=', $refund->id))
                    ->where('id', '>', $allocation->id)->exists()) {
                    throw new RuntimeException('Hay abonos o devoluciones posteriores en este crédito; revísalos antes de eliminar esta devolución.');
                }
            }
            $refund->allocations()->delete();
            $refund->delete();
            foreach ($credits as $credit) {
                $this->schedule->flush($credit->id);
                $this->payments->syncCreditStatus($credit);
            }
        }, 3);
    }

    public function assertCreditCanBeChanged(CreditPurchase $credit): void
    {
        if ($credit->freePayments()->where('payment_type', 'refund')->exists()) {
            throw new RuntimeException('Este crédito tiene una devolución aplicada. Revísala o elimínala antes de modificar su calendario o eliminarlo.');
        }
    }

    public function assertInstallmentCanBeChanged(CreditInstallment $installment): void
    {
        $this->assertCreditCanBeChanged($installment->creditPurchase()->firstOrFail());
    }

    private function snapshot(CreditInstallment $installment): array
    {
        return [
            'credit_purchase_id' => (int) $installment->credit_purchase_id,
            'period_month' => $installment->period_month?->toDateString(),
            'due_date' => $installment->due_date?->toDateString(),
            'amount_cents' => $this->cents($installment->amount),
            'paid_amount_cents' => $this->cents($installment->paid_amount),
            'status' => $installment->status,
            'paid_on' => $installment->paid_on?->toDateString(),
            'movement_id' => $installment->movement_id,
        ];
    }

    private function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
