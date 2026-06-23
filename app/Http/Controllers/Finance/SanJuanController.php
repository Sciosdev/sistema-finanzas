<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\RentalContract;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceSummaryService;
use App\Services\Finance\ExpectedIncomePaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SanJuanController extends Controller
{
    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
        private readonly ExpectedIncomePaymentService $incomePayments,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($request->query('month', now()->format('Y-m')));
        $summary = $this->summaryService->monthSummary($user, $start->format('Y-m'));

        $movements = Movement::with(['category', 'person', 'account'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->where('is_san_juan', true)
                    ->orWhere('is_rent', true);
            })
            ->orderByDesc('happened_on')
            ->get();

        $tenants = Person::with('rentalContracts')
            ->where('user_id', $user->id)
            ->where('is_tenant', true)
            ->orderBy('name')
            ->get();

        $rentalContracts = RentalContract::with('person')
            ->where('user_id', $user->id)
            ->orderByRaw('due_day is null, due_day asc')
            ->get()
            ->sortBy(fn (RentalContract $contract) => str_pad((string) ($contract->due_day ?? 99), 2, '0', STR_PAD_LEFT) . ($contract->person?->name ?? ''))
            ->values();

        $rentalTemplateTotals = [
            'active_count' => $rentalContracts->where('is_active', true)->count(),
            'inactive_count' => $rentalContracts->where('is_active', false)->count(),
            'monthly_expected' => round($rentalContracts
                ->where('is_active', true)
                ->sum(fn (RentalContract $contract) => (float) $contract->expected_amount), 2),
        ];

        $rentMovements = $movements
            ->where('movement_type', 'income')
            ->where('is_rent', true);

        $rentalDetailRows = $rentalContracts
            ->where('is_active', true)
            ->map(function (RentalContract $contract) use ($rentMovements, $start, $user) {
                $expectedIncome = ExpectedIncome::with('payments.movement')
                    ->where('user_id', $user->id)
                    ->where('import_key', "rental-contract:{$contract->id}:{$start->format('Y-m')}")
                    ->first();
                $relatedPayments = $expectedIncome?->payments ?? collect();
                $relatedMovements = $relatedPayments->isNotEmpty()
                    ? $relatedPayments->pluck('movement')->filter()->sortByDesc('happened_on')->values()
                    : $rentMovements
                        ->filter(fn (Movement $movement) => $movement->person_id === $contract->person_id)
                        ->sortByDesc('happened_on')
                        ->values();
                $received = $expectedIncome
                    ? round((float) $expectedIncome->received_amount, 2)
                    : round($relatedMovements->sum(fn (Movement $movement) => (float) $movement->amount), 2);
                $expected = round((float) $contract->expected_amount, 2);

                return [
                    'contract' => $contract,
                    'expected_income' => $expectedIncome,
                    'person' => $contract->person?->name ?? 'Sin inquilino',
                    'room' => $contract->room,
                    'expected' => $expected,
                    'received' => $received,
                    'pending' => round(max(0, $expected - $received), 2),
                    'payment_count' => $relatedPayments->count(),
                    'related_payments' => $relatedPayments,
                    'related_movements' => $relatedMovements,
                ];
            })
            ->values();

        $expenseConceptRows = $movements
            ->where('movement_type', 'expense')
            ->groupBy(fn (Movement $movement) => $movement->category?->name ?? 'Sin categoría')
            ->map(fn (Collection $rows, string $concept) => [
                'concept' => $concept,
                'amount' => round($rows->sum(fn (Movement $movement) => (float) $movement->amount), 2),
                'count' => $rows->count(),
            ])
            ->sortByDesc('amount')
            ->values();

        $personMovementRows = $movements
            ->groupBy(fn (Movement $movement) => $movement->person?->name ?? 'Sin persona')
            ->map(function (Collection $rows, string $person) {
                $income = round($rows
                    ->whereIn('movement_type', ['income', 'yield'])
                    ->sum(fn (Movement $movement) => (float) $movement->amount), 2);
                $expenses = round($rows
                    ->where('movement_type', 'expense')
                    ->sum(fn (Movement $movement) => (float) $movement->amount), 2);

                return [
                    'person' => $person,
                    'income' => $income,
                    'expenses' => $expenses,
                    'net' => round($income - $expenses, 2),
                    'count' => $rows->count(),
                ];
            })
            ->sortBy('person')
            ->values();

        $movementRelationGroups = $movements
            ->groupBy(function (Movement $movement) {
                if ($movement->is_rent && $movement->person) {
                    return 'Renta: ' . $movement->person->name;
                }

                if ($movement->person) {
                    return 'Persona: ' . $movement->person->name;
                }

                if ($movement->category) {
                    return 'Concepto: ' . $movement->category->name;
                }

                return 'Sin relación';
            })
            ->map(function (Collection $rows, string $label) {
                $income = round($rows
                    ->whereIn('movement_type', ['income', 'yield'])
                    ->sum(fn (Movement $movement) => (float) $movement->amount), 2);
                $expenses = round($rows
                    ->where('movement_type', 'expense')
                    ->sum(fn (Movement $movement) => (float) $movement->amount), 2);

                return [
                    'label' => $label,
                    'income' => $income,
                    'expenses' => $expenses,
                    'net' => round($income - $expenses, 2),
                    'count' => $rows->count(),
                    'movements' => $rows->sortByDesc('happened_on')->values(),
                ];
            })
            ->sortBy('label')
            ->values();

        return view('finance.san-juan.index', [
            'summary' => $summary,
            'monthValue' => $start->format('Y-m'),
            'movements' => $movements,
            'tenants' => $tenants,
            'rentalContracts' => $rentalContracts,
            'rentalTemplateTotals' => $rentalTemplateTotals,
            'rentalDetailRows' => $rentalDetailRows,
            'expenseConceptRows' => $expenseConceptRows,
            'personMovementRows' => $personMovementRows,
            'movementRelationGroups' => $movementRelationGroups,
        ]);
    }

    public function storeRentalContract(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'person_name' => [
                'required',
                'string',
                'max:255',
            ],
            'room' => ['nullable', 'string', 'max:255'],
            'expected_amount' => ['required', 'numeric', 'min:0'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
        ]);

        $person = Person::firstOrCreate(
            ['user_id' => $user->id, 'name' => trim($data['person_name'])],
            [
                'type' => 'tenant',
                'is_tenant' => true,
                'is_active' => true,
            ],
        );

        if (! $person->is_tenant || ! $person->is_active) {
            $person->update([
                'type' => 'tenant',
                'is_tenant' => true,
                'is_active' => true,
            ]);
        }

        RentalContract::create([
            'user_id' => $user->id,
            'person_id' => $person->id,
            'room' => $data['room'] ?? null,
            'expected_amount' => $data['expected_amount'],
            'due_day' => $data['due_day'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'is_active' => true,
            'manual_override' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Contrato de renta agregado.');
    }

    public function updateRentalContract(Request $request, RentalContract $contract)
    {
        abort_unless($contract->user_id === $request->user()->id, 403);

        $user = $request->user();
        $data = $request->validate([
            'person_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_people', 'name')
                    ->where(fn ($query) => $query->where('user_id', $user->id))
                    ->ignore($contract->person_id),
            ],
            'room' => ['nullable', 'string', 'max:255'],
            'expected_amount' => ['required', 'numeric', 'min:0'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $contract->person?->update([
            'name' => $data['person_name'],
            'type' => 'tenant',
            'is_tenant' => true,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $contract->update([
            'room' => $data['room'] ?? null,
            'expected_amount' => $data['expected_amount'],
            'due_day' => $data['due_day'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'manual_override' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Contrato de renta actualizado.');
    }

    public function destroyRentalContract(Request $request, RentalContract $contract)
    {
        abort_unless($contract->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $contract) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $contract, 'rental_contract');
            $person = $contract->person;

            $contract->delete();

            if ($person && $person->rentalContracts()->where('user_id', $request->user()->id)->doesntExist()) {
                $person->update([
                    'is_tenant' => false,
                    'is_active' => false,
                ]);
            }

            return $snapshot;
        });

        return back()
            ->with('success', 'Renta eliminada de la plantilla.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at,
            ]);
    }

    public function markRentalReceived(Request $request, RentalContract $contract)
    {
        abort_unless($contract->user_id === $request->user()->id, 403);

        $user = $request->user();

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'received_on' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
        ]);

        $periodMonth = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $receivedOn = isset($data['received_on']) ? Carbon::parse($data['received_on']) : today();
        $amount = round((float) ($data['amount'] ?? $contract->expected_amount), 2);
        $importKey = "rental-contract:{$contract->id}:{$periodMonth->format('Y-m')}";

        $category = Category::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Rentas San Juan', 'type' => 'income'],
            [
                'group' => 'San Juan',
                'color' => '#22b956',
                'keywords' => 'renta,rentas',
                'is_rent' => true,
                'is_active' => true,
            ]
        );

        $accountId = $data['account_id'] ?? Account::where('user_id', $user->id)->where('name', 'NU')->value('id');
        $personName = $contract->person?->name ?? 'Renta';
        $description = $contract->room ? 'Renta cuarto ' . $contract->room : 'Renta San Juan';

        $income = ExpectedIncome::firstOrCreate(
            [
                'user_id' => $user->id,
                'import_key' => $importKey,
            ],
            [
                'period_month' => $periodMonth->toDateString(),
                'due_date' => $periodMonth->copy()->day(min((int) ($contract->due_day ?: 1), $periodMonth->daysInMonth))->toDateString(),
                'name' => $description,
                'amount' => round((float) $contract->expected_amount, 2),
                'received_amount' => 0,
                'status' => 'pending',
                'account_id' => $accountId,
                'category_id' => $category->id,
                'person_id' => $contract->person_id,
                'is_rent' => true,
                'notes' => 'Renta mensual generada desde contrato de San Juan',
            ]
        );

        if ($income->status === 'received') {
            return back()->with('success', 'Esa renta ya estaba cubierta.');
        }

        $remaining = max(0, (float) $income->amount - (float) $income->received_amount);
        $amount = min($amount, $remaining > 0 ? $remaining : $amount);

        try {
            $payment = $this->incomePayments->createMovementAndPayment(
                $income,
                $receivedOn,
                $amount,
                $accountId,
                'Abono de renta San Juan: ' . $personName
            );

            if ($payment->movement_id) {
                Movement::where('user_id', $user->id)
                    ->whereKey($payment->movement_id)
                    ->update([
                        'source' => 'rental_contract',
                        'description' => $amount + 0.01 >= $remaining
                            ? 'Renta recibida: ' . $personName
                            : 'Abono renta San Juan: ' . $personName,
                    ]);
            }
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Abono de renta registrado.');
    }
}
