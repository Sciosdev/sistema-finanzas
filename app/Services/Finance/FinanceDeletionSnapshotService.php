<?php

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\Person;
use App\Models\Finance\RentalContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FinanceDeletionSnapshotService
{
    private const RESTORE_WINDOW_MINUTES = 2;

    /**
     * @var array<string, class-string<Model>>
     */
    private array $restorableModels = [
        'movement' => Movement::class,
        'planned_payment' => PlannedPayment::class,
        'expected_income' => ExpectedIncome::class,
        'category' => Category::class,
        'rental_contract' => RentalContract::class,
        'credit_purchase' => CreditPurchase::class,
        'credit_installment' => CreditInstallment::class,
    ];

    public function captureBeforeDelete(User $user, Model $model, string $entityType): DeleteSnapshot
    {
        $modelClass = $this->modelClassFor($entityType);

        if (! $model instanceof $modelClass) {
            throw new InvalidArgumentException("El tipo {$entityType} no coincide con el modelo recibido.");
        }

        if ((int) $model->getAttribute('user_id') !== (int) $user->id) {
            throw new InvalidArgumentException('No se puede crear snapshot de otro usuario.');
        }

        return DeleteSnapshot::create([
            'user_id' => $user->id,
            'token' => $this->uniqueToken(),
            'entity_type' => $entityType,
            'table_name' => $model->getTable(),
            'entity_id' => $model->getKey(),
            'payload' => $model->getAttributes(),
            'relations_payload' => $this->relationsPayload($user, $model, $entityType),
            'expires_at' => now()->addMinutes(self::RESTORE_WINDOW_MINUTES),
        ]);
    }

    public function restore(User $user, string $token): array
    {
        return DB::transaction(function () use ($user, $token) {
            $snapshot = DeleteSnapshot::where('user_id', $user->id)
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if (! $snapshot) {
                return [
                    'ok' => false,
                    'message' => 'No se encontró un borrado para deshacer.',
                ];
            }

            if ($snapshot->restored_at) {
                return [
                    'ok' => false,
                    'message' => 'Ese borrado ya fue restaurado.',
                ];
            }

            if ($snapshot->expires_at->lt(now())) {
                return [
                    'ok' => false,
                    'message' => 'El tiempo para deshacer ya expiró.',
                ];
            }

            $modelClass = $this->modelClassFor($snapshot->entity_type);

            $result = $this->restoreSnapshot($user, $snapshot, $modelClass);

            if (! $result['ok']) {
                return $result;
            }

            $snapshot->update(['restored_at' => now()]);

            return $result;
        });
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function restoreSnapshot(User $user, DeleteSnapshot $snapshot, string $modelClass): array
    {
        return match ($snapshot->entity_type) {
            'expected_income' => $this->restoreExpectedIncome($user, $snapshot),
            'category' => $this->restoreCategory($user, $snapshot),
            'rental_contract' => $this->restoreRentalContract($user, $snapshot),
            'credit_purchase' => $this->restoreCreditPurchase($user, $snapshot),
            'credit_installment' => $this->restoreCreditInstallment($user, $snapshot),
            default => $this->restoreGeneric($user, $snapshot, $modelClass),
        };
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function restoreGeneric(User $user, DeleteSnapshot $snapshot, string $modelClass): array
    {
        if ($modelClass::query()->whereKey($snapshot->entity_id)->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque el registro ya existe.',
            ];
        }

        $modelClass::query()->insert($snapshot->payload);
        $this->restoreRelations($user, $snapshot);

        return [
            'ok' => true,
            'message' => $this->restoredMessage($snapshot->entity_type),
        ];
    }

    private function restoreExpectedIncome(User $user, DeleteSnapshot $snapshot): array
    {
        if (ExpectedIncome::query()->whereKey($snapshot->entity_id)->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque el registro ya existe.',
            ];
        }

        $payload = $snapshot->payload;

        if (! empty($payload['import_key']) && ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->where('import_key', $payload['import_key'])
            ->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque ya existe un ingreso esperado equivalente.',
            ];
        }

        $movementMissing = false;

        if (! empty($payload['movement_id']) && ! Movement::query()
            ->where('user_id', $user->id)
            ->whereKey($payload['movement_id'])
            ->exists()) {
            $payload['movement_id'] = null;
            $movementMissing = true;
        }

        ExpectedIncome::query()->insert($payload);

        return [
            'ok' => true,
            'message' => $movementMissing
                ? 'Ingreso esperado restaurado, pero el movimiento vinculado ya no existe.'
                : $this->restoredMessage($snapshot->entity_type),
        ];
    }

    private function restoreCategory(User $user, DeleteSnapshot $snapshot): array
    {
        $payload = $snapshot->payload;

        if (Category::query()
            ->where('user_id', $user->id)
            ->where('name', $payload['name'])
            ->where('type', $payload['type'])
            ->where('id', '!=', $snapshot->entity_id)
            ->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque ya existe otra categoría con el mismo nombre y tipo.',
            ];
        }

        $existing = Category::query()
            ->where('user_id', $user->id)
            ->whereKey($snapshot->entity_id)
            ->first();

        if ($existing) {
            DB::table($snapshot->table_name)
                ->where('id', $snapshot->entity_id)
                ->where('user_id', $user->id)
                ->update(collect($payload)->except('id')->all());

            return [
                'ok' => true,
                'message' => $this->restoredMessage($snapshot->entity_type),
            ];
        }

        Category::query()->insert($payload);

        return [
            'ok' => true,
            'message' => $this->restoredMessage($snapshot->entity_type),
        ];
    }

    private function restoreRentalContract(User $user, DeleteSnapshot $snapshot): array
    {
        if (RentalContract::query()->whereKey($snapshot->entity_id)->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque el contrato ya existe.',
            ];
        }

        $payload = $snapshot->payload;
        $personId = $payload['person_id'] ?? null;

        if ($personId && ! Person::query()
            ->where('user_id', $user->id)
            ->whereKey($personId)
            ->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque el inquilino ya no existe.',
            ];
        }

        RentalContract::query()->insert($payload);
        $this->restoreRentalContractPersonState($user, $snapshot);

        return [
            'ok' => true,
            'message' => $this->restoredMessage($snapshot->entity_type),
        ];
    }

    private function restoreCreditPurchase(User $user, DeleteSnapshot $snapshot): array
    {
        if (CreditPurchase::query()->whereKey($snapshot->entity_id)->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque el crédito ya existe.',
            ];
        }

        $installments = $snapshot->relations_payload['installments'] ?? [];
        $installmentIds = collect($installments)
            ->pluck('id')
            ->filter()
            ->all();

        if ($installmentIds !== [] && CreditInstallment::query()->whereIn('id', $installmentIds)->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque una mensualidad ya existe.',
            ];
        }

        CreditPurchase::query()->insert($snapshot->payload);

        foreach ($installments as $installment) {
            if (! empty($installment['movement_id']) && ! Movement::query()
                ->where('user_id', $user->id)
                ->whereKey($installment['movement_id'])
                ->exists()) {
                $installment['movement_id'] = null;
            }

            CreditInstallment::query()->insert($installment);
        }

        return [
            'ok' => true,
            'message' => $this->restoredMessage($snapshot->entity_type),
        ];
    }

    private function restoreCreditInstallment(User $user, DeleteSnapshot $snapshot): array
    {
        if (CreditInstallment::query()->whereKey($snapshot->entity_id)->exists()) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque la mensualidad ya existe.',
            ];
        }

        $payload = $snapshot->payload;
        $creditId = $payload['credit_purchase_id'] ?? null;
        $credit = $creditId
            ? CreditPurchase::query()
                ->where('user_id', $user->id)
                ->whereKey($creditId)
                ->first()
            : null;

        if (! $credit) {
            return [
                'ok' => false,
                'message' => 'No se pudo restaurar porque el crédito ya no existe.',
            ];
        }

        if (! empty($payload['movement_id']) && ! Movement::query()
            ->where('user_id', $user->id)
            ->whereKey($payload['movement_id'])
            ->exists()) {
            $payload['movement_id'] = null;
        }

        CreditInstallment::query()->insert($payload);

        $this->renumberCreditInstallments($credit);
        $this->refreshCreditFromInstallments($credit);

        return [
            'ok' => true,
            'message' => $this->restoredMessage($snapshot->entity_type),
        ];
    }

    private function relationsPayload(User $user, Model $model, string $entityType): ?array
    {
        if ($entityType === 'movement') {
            return [
                'planned_payment_ids' => PlannedPayment::where('user_id', $user->id)
                    ->where('movement_id', $model->getKey())
                    ->pluck('id')
                    ->all(),
            ];
        }

        if ($entityType === 'rental_contract' && $model instanceof RentalContract) {
            $person = $model->person_id
                ? Person::where('user_id', $user->id)->whereKey($model->person_id)->first()
                : null;

            $isLastContract = $model->person_id
                ? RentalContract::where('user_id', $user->id)
                    ->where('person_id', $model->person_id)
                    ->whereKeyNot($model->getKey())
                    ->doesntExist()
                : false;

            return [
                'person' => $person?->getAttributes(),
                'was_last_contract_for_person' => $isLastContract,
                'would_deactivate_person' => (bool) ($person && $isLastContract),
            ];
        }

        if ($entityType === 'credit_purchase' && $model instanceof CreditPurchase) {
            return [
                'installments' => CreditInstallment::where('user_id', $user->id)
                    ->where('credit_purchase_id', $model->getKey())
                    ->orderBy('installment_number')
                    ->get()
                    ->map(fn (CreditInstallment $installment) => $installment->getAttributes())
                    ->all(),
            ];
        }

        if ($entityType === 'credit_installment' && $model instanceof CreditInstallment) {
            $credit = CreditPurchase::where('user_id', $user->id)
                ->whereKey($model->credit_purchase_id)
                ->first();

            return [
                'credit' => $credit?->only([
                    'id',
                    'user_id',
                    'total_amount',
                    'months',
                    'first_due_month',
                    'status',
                ]),
            ];
        }

        return null;
    }

    private function restoreRelations(User $user, DeleteSnapshot $snapshot): void
    {
        if ($snapshot->entity_type !== 'movement') {
            return;
        }

        $plannedPaymentIds = $snapshot->relations_payload['planned_payment_ids'] ?? [];

        if ($plannedPaymentIds === []) {
            return;
        }

        PlannedPayment::where('user_id', $user->id)
            ->whereIn('id', $plannedPaymentIds)
            ->whereNull('movement_id')
            ->update(['movement_id' => $snapshot->entity_id]);
    }

    private function restoreRentalContractPersonState(User $user, DeleteSnapshot $snapshot): void
    {
        $relations = $snapshot->relations_payload ?? [];

        if (! ($relations['would_deactivate_person'] ?? false)) {
            return;
        }

        $personPayload = $relations['person'] ?? null;
        $personId = $personPayload['id'] ?? null;

        if (! $personId) {
            return;
        }

        $person = Person::where('user_id', $user->id)->whereKey($personId)->first();

        if (! $person || $person->is_tenant || $person->is_active) {
            return;
        }

        $person->update([
            'is_tenant' => (bool) ($personPayload['is_tenant'] ?? true),
            'is_active' => (bool) ($personPayload['is_active'] ?? true),
        ]);
    }

    private function renumberCreditInstallments(CreditPurchase $credit): void
    {
        $credit->installments()
            ->orderBy('period_month')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(fn (CreditInstallment $installment, int $index) => $installment->update([
                'installment_number' => $index + 1,
            ]));
    }

    private function refreshCreditFromInstallments(CreditPurchase $credit): void
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
            'status' => $installments->every(fn (CreditInstallment $installment) => $installment->status === 'paid') ? 'paid' : 'active',
        ]);
    }

    /**
     * @return class-string<Model>
     */
    private function modelClassFor(string $entityType): string
    {
        if (! array_key_exists($entityType, $this->restorableModels)) {
            throw new InvalidArgumentException("El tipo {$entityType} no se puede restaurar.");
        }

        return $this->restorableModels[$entityType];
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (DeleteSnapshot::where('token', $token)->exists());

        return $token;
    }

    private function restoredMessage(string $entityType): string
    {
        return match ($entityType) {
            'movement' => 'Movimiento restaurado.',
            'planned_payment' => 'Pago planeado restaurado.',
            'expected_income' => 'Ingreso esperado restaurado.',
            'category' => 'Categoría restaurada.',
            'rental_contract' => 'Renta restaurada en la plantilla.',
            'credit_purchase' => 'Crédito restaurado.',
            'credit_installment' => 'Mensualidad restaurada.',
            default => 'Registro restaurado.',
        };
    }
}
