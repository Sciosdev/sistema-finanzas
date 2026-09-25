<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPaymentCorrection;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\ExpectedIncomePayment;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Corrects the classification of an existing payment; never sends bank money. */
class CreditPaymentCorrectionService
{
    public function __construct(
        private readonly CreditEffectiveScheduleService $schedule,
        private readonly CreditFreePaymentService $payments,
    ) {}

    public function separateCharge(User $user, array $data): CreditPaymentCorrection
    {
        $ids = array_map('intval', $data['installment_ids'] ?? []);
        if (! $ids || count($ids) !== count(array_unique($ids)) || count($ids) > 60 || min($ids) < 1) {
            throw new RuntimeException('Selecciona mensualidades distintas y válidas.');
        }
        sort($ids);
        $paidTotal = $this->cents($data['expected_paid_total'] ?? 0);
        $amount = $this->cents($data['amount'] ?? 0);
        if ($amount <= 0 || $paidTotal <= $amount) {
            throw new RuntimeException('El cargo separado debe ser positivo y menor que el total pagado seleccionado.');
        }
        $key = trim((string) ($data['idempotency_key'] ?? ''));
        $concept = trim((string) ($data['concept'] ?? ''));
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($key === '' || strlen($key) > 100 || $concept === '' || mb_strlen($concept) > 255 || $reason === '') {
            throw new RuntimeException('Indica la referencia, el concepto y el motivo documentado de la corrección.');
        }
        $categoryId = empty($data['charge_category_id'])
            ? Category::where('user_id', $user->id)->where('name', 'Crédito / tarjeta')->value('id')
            : (int) $data['charge_category_id'];
        if ($categoryId && ! Category::whereKey($categoryId)->where('user_id', $user->id)->exists()) {
            throw new RuntimeException('La categoría del cargo no pertenece a este usuario.');
        }
        try {
            $paidOn = Carbon::parse($data['paid_on'])->toDateString();
            $chargeMonth = Carbon::createFromFormat('!Y-m', $data['charge_period_month'])->startOfMonth()->toDateString();
            $targetMonth = Carbon::createFromFormat('!Y-m', $data['target_period_month'])->startOfMonth()->toDateString();
            $chargeDue = Carbon::parse($data['charge_due_date'])->toDateString();
            $targetDue = Carbon::parse($data['target_due_date'])->toDateString();
        } catch (\Throwable) {
            throw new RuntimeException('Las fechas de la corrección no son válidas.');
        }
        if (substr($chargeMonth, 0, 7) !== substr($chargeDue, 0, 7)
            || substr($targetMonth, 0, 7) !== substr($targetDue, 0, 7)) {
            throw new RuntimeException('Cada vencimiento debe pertenecer al mes indicado.');
        }
        $hash = hash('sha256', json_encode([$ids, $paidTotal, $amount, $concept, $reason, $paidOn, $chargeMonth, $targetMonth, $chargeDue, $targetDue, $categoryId], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($user, $ids, $paidTotal, $amount, $concept, $reason, $key, $hash, $paidOn, $chargeMonth, $targetMonth, $chargeDue, $targetDue, $categoryId) {
            if ($existing = $this->existing($user, $key, $hash)) {
                return $existing;
            }
            $creditIds = CreditInstallment::whereIn('id', $ids)->where('user_id', $user->id)->pluck('credit_purchase_id')->unique();
            $credits = CreditPurchase::whereIn('id', $creditIds)->where('user_id', $user->id)->orderBy('id')->lockForUpdate()->get();
            if ($existing = $this->existing($user, $key, $hash)) {
                return $existing;
            }
            $rows = CreditInstallment::whereIn('id', $ids)->where('user_id', $user->id)->orderBy('id')->lockForUpdate()->get();
            if ($rows->count() !== count($ids) || $credits->isEmpty()) {
                throw new RuntimeException('Las mensualidades no pertenecen a este usuario.');
            }
            $accountIds = $credits->pluck('account_id')->unique();
            if ($accountIds->count() !== 1 || ! $accountIds->first()
                || ! \App\Models\Finance\Account::whereKey($accountIds->first())->where('user_id', $user->id)->exists()) {
                throw new RuntimeException('Las compras deben pertenecer a la misma tarjeta.');
            }
            foreach ($credits as $credit) {
                app(CardRefundService::class)->assertCreditCanBeChanged($credit);
                if ($credit->freePayments()->exists()) {
                    throw new RuntimeException('Hay abonos o devoluciones vinculados. Revísalos antes de separar este cargo.');
                }
            }
            if (PlannedPayment::whereIn('credit_purchase_id', $creditIds)->orWhereIn('credit_installment_id', $ids)->exists()) {
                throw new RuntimeException('Desvincula los pagos planeados antes de corregir el pago.');
            }

            $movementIds = $rows->pluck('movement_id');
            if ($movementIds->contains(null) || $movementIds->unique()->count() !== $rows->count()) {
                throw new RuntimeException('Cada mensualidad debe tener su propio movimiento de pago.');
            }
            $movements = Movement::whereIn('id', $movementIds)->where('user_id', $user->id)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($movements->count() !== $rows->count() || $movements->pluck('account_id')->unique()->count() !== 1) {
                throw new RuntimeException('Los pagos deben salir de una misma cuenta y pertenecer al usuario.');
            }
            $paymentAccountId = $movements->first()->account_id;
            if (! $paymentAccountId || ! \App\Models\Finance\Account::whereKey($paymentAccountId)->where('user_id', $user->id)->exists()) {
                throw new RuntimeException('Los movimientos necesitan su cuenta de origen real.');
            }
            $this->assertExclusiveMovements($movementIds->all(), $ids);
            $actualPaid = 0;
            foreach ($rows as $row) {
                $movement = $movements[$row->movement_id];
                if ($row->status !== 'paid' || $this->cents($row->paid_amount) !== $this->cents($row->amount)
                    || $row->paid_on?->toDateString() !== $paidOn || $movement->happened_on?->toDateString() !== $paidOn
                    || $movement->movement_type !== 'expense' || $movement->source !== 'credit_installment'
                    || $this->cents($movement->amount) !== $this->cents($row->paid_amount)) {
                    throw new RuntimeException('El pago seleccionado cambió o no está totalmente respaldado por sus movimientos originales.');
                }
                $actualPaid += $this->cents($row->paid_amount);
            }
            if ($actualPaid !== $paidTotal) {
                throw new RuntimeException('El total pagado cambió. Actualiza la página antes de corregirlo.');
            }

            $before = $this->snapshot($creditIds->all(), $movementIds->all());
            $orderedRows = $rows->sortBy(fn ($row) => sprintf('%s|%012d|%012d',
                $credits->firstWhere('id', $row->credit_purchase_id)->purchase_date?->toDateString() ?? '',
                $row->credit_purchase_id, $row->id))->values();
            $remainingPaid = $paidTotal - $amount;
            foreach ($orderedRows as $row) {
                $applied = min($remainingPaid, $this->cents($row->amount));
                $remainingPaid -= $applied;
                $movement = $movements[$row->movement_id];
                $row->update([
                    'period_month' => $targetMonth, 'due_date' => $targetDue,
                    'paid_amount' => $applied / 100,
                    'paid_on' => $applied > 0 ? $paidOn : null,
                    'status' => $applied === $this->cents($row->amount) ? 'paid' : 'pending',
                    'movement_id' => $applied > 0 ? $movement->id : null,
                ]);
                if ($applied > 0) {
                    $movement->update(['amount' => $applied / 100]);
                } else {
                    // Full original record remains in before_state and undo
                    // restores the same id, amount, dates and description.
                    $movement->delete();
                }
            }
            foreach ($credits as $credit) {
                $credit->update(['is_manual_schedule' => true, 'due_day' => null]);
                $credit->installments()->orderBy('period_month')->orderBy('due_date')->orderBy('id')->get()
                    ->each(fn ($row, $index) => $row->update(['installment_number' => $index + 1]));
                $this->payments->refreshCreditFromInstallments($credit);
                $this->schedule->flush($credit->id);
            }

            $charge = CreditPurchase::create([
                'user_id' => $user->id, 'account_id' => $accountIds->first(), 'purchase_date' => $paidOn,
                'name' => $concept, 'total_amount' => $amount / 100, 'months' => 1,
                'first_due_month' => $chargeMonth, 'due_day' => null, 'is_manual_schedule' => true,
                'status' => 'paid', 'notes' => $reason, 'category_id' => $categoryId,
            ]);
            $chargeMovement = Movement::create([
                'user_id' => $user->id, 'account_id' => $paymentAccountId, 'happened_on' => $paidOn,
                'movement_type' => 'expense', 'amount' => $amount / 100,
                'description' => mb_substr('Crédito: '.$concept.' 1/1', 0, 255), 'source' => 'credit_installment',
                'category_id' => $categoryId,
                'notes' => 'Parte del pago existente reclasificada. '.$reason,
            ]);
            $charge->installments()->create([
                'user_id' => $user->id, 'period_month' => $chargeMonth, 'due_date' => $chargeDue,
                'installment_number' => 1, 'amount' => $amount / 100, 'paid_amount' => $amount / 100,
                'paid_on' => $paidOn, 'status' => 'paid', 'movement_id' => $chargeMovement->id, 'notes' => $reason,
            ]);
            $after = $this->snapshot([...$creditIds->all(), $charge->id], [...$movementIds->all(), $chargeMovement->id]);
            if (array_sum(array_map(fn ($row) => $this->cents($row['amount']), $after['movements'])) !== $paidTotal) {
                throw new RuntimeException('La corrección no conserva el total original del pago. Se canceló la operación.');
            }

            return CreditPaymentCorrection::create([
                'user_id' => $user->id, 'account_id' => $accountIds->first(), 'charge_credit_purchase_id' => $charge->id, 'idempotency_key' => $key,
                'request_hash' => $hash, 'paid_total' => $paidTotal / 100, 'amount' => $amount / 100,
                'concept' => $concept, 'reason' => $reason, 'source_installment_ids' => $ids,
                'before_state' => $before, 'after_state' => $after,
            ]);
        }, 3);
    }

    public function undo(User $user, CreditPaymentCorrection $correction): void
    {
        DB::transaction(function () use ($user, $correction) {
            $correction = CreditPaymentCorrection::whereKey($correction->id)->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $correction || $correction->reverted_at) {
                throw new RuntimeException('La corrección no está disponible para revertir.');
            }
            $before = $correction->before_state;
            $after = $correction->after_state;
            $creditIds = array_column($after['credits'], 'id');
            $movementIds = array_column($after['movements'], 'id');
            $credits = CreditPurchase::whereIn('id', $creditIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($credits as $credit) {
                app(CardRefundService::class)->assertCreditCanBeChanged($credit);
            }
            CreditInstallment::whereIn('credit_purchase_id', $creditIds)->orderBy('id')->lockForUpdate()->get();
            Movement::whereIn('id', $movementIds)->orderBy('id')->lockForUpdate()->get();
            if ($this->snapshot($creditIds, $movementIds) != $after
                || CreditFreePayment::whereIn('credit_purchase_id', $creditIds)->exists()
                || PlannedPayment::whereIn('credit_purchase_id', $creditIds)
                    ->orWhereIn('credit_installment_id', array_column($after['installments'], 'id'))->exists()) {
                throw new RuntimeException('Los registros cambiaron después de la corrección. Revísalos antes de revertirla.');
            }
            $this->assertExclusiveMovements($movementIds, array_column($after['installments'], 'id'));
            $oldMovementIds = array_column($before['movements'], 'id');
            foreach ($before['movements'] as $row) {
                if (in_array($row['id'], $movementIds, true)) {
                    DB::table('finance_movements')->where('id', $row['id'])->update($row);
                } else {
                    if (Movement::whereKey($row['id'])->exists()) {
                        throw new RuntimeException('No se puede restaurar porque un movimiento original ya existe.');
                    }
                    DB::table('finance_movements')->insert($row);
                }
            }
            foreach ($before['credits'] as $row) {
                DB::table('finance_credit_purchases')->where('id', $row['id'])->update($row);
            }
            foreach ($before['installments'] as $row) {
                DB::table('finance_credit_installments')->where('id', $row['id'])->update($row);
            }
            $newCreditIds = array_diff($creditIds, array_column($before['credits'], 'id'));
            CreditInstallment::whereIn('credit_purchase_id', $newCreditIds)->delete();
            CreditPurchase::whereIn('id', $newCreditIds)->delete();
            Movement::whereIn('id', array_diff($movementIds, $oldMovementIds))->delete();
            $correction->update(['reverted_at' => now()]);
            $this->schedule->flush();
        }, 3);
    }

    private function existing(User $user, string $key, string $hash): ?CreditPaymentCorrection
    {
        $existing = CreditPaymentCorrection::where('user_id', $user->id)->where('idempotency_key', $key)->lockForUpdate()->first();
        if ($existing && ($existing->request_hash !== $hash || $existing->reverted_at)) {
            throw new RuntimeException('Esa referencia ya corresponde a otra corrección o fue revertida.');
        }

        return $existing;
    }

    private function assertExclusiveMovements(array $movementIds, array $installmentIds): void
    {
        if (CreditInstallment::whereIn('movement_id', $movementIds)->whereNotIn('id', $installmentIds)->exists()
            || CreditFreePayment::whereIn('movement_id', $movementIds)->exists()
            || PlannedPayment::whereIn('movement_id', $movementIds)->exists()
            || ExpectedIncome::whereIn('movement_id', $movementIds)->exists()
            || ExpectedIncomePayment::whereIn('movement_id', $movementIds)->exists()) {
            throw new RuntimeException('Un movimiento seleccionado está compartido con otros registros. Revisa sus vínculos primero.');
        }
    }

    private function snapshot(array $creditIds, array $movementIds): array
    {
        return [
            'credits' => CreditPurchase::whereIn('id', $creditIds)->orderBy('id')->get()->map->getAttributes()->all(),
            'installments' => CreditInstallment::whereIn('credit_purchase_id', $creditIds)->orderBy('id')->get()->map->getAttributes()->all(),
            'movements' => Movement::whereIn('id', $movementIds)->orderBy('id')->get()->map->getAttributes()->all(),
        ];
    }

    private function cents(mixed $value): int
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            throw new RuntimeException('El importe no es válido.');
        }

        return (int) round((float) $value * 100);
    }
}
