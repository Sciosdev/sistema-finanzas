<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\ExpectedIncomePayment;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\RentalContract;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceSummaryService;
use App\Services\Finance\ExpectedIncomePaymentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpectedIncomeController extends Controller
{
    use PreparesFinanceData;

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

        $incomes = ExpectedIncome::with(['account', 'category', 'person', 'movement', 'payments.movement'])
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->orderByRaw('due_date is null, due_date asc')
            ->orderBy('name')
            ->get();

        $incomeRows = $this->incomeRows($user, $start, $end, $incomes);
        $receivedTotal = round($incomeRows->sum(fn (array $income) => (float) $income['received_amount']), 2);
        $expectedTotal = round($incomeRows->sum(fn (array $income) => (float) $income['amount']), 2);

        return view('finance.expected-incomes.index', [
            'incomeRows' => $incomeRows,
            'incomeTotals' => [
                'expected' => $expectedTotal,
                'received' => $receivedTotal,
                'pending' => round(max(0, $expectedTotal - $receivedTotal), 2),
            ],
            'monthValue' => $start->format('Y-m'),
            'editIncomeId' => (int) $request->query('edit'),
            'accounts' => $this->accountsFor($user),
            'categories' => $this->categoriesFor($user, 'income'),
            'people' => $this->peopleFor($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'person_id' => ['nullable', 'integer', Rule::exists('finance_people', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'new_person_name' => ['nullable', 'string', 'max:255'],
            'is_rent' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $newPersonName = trim((string) ($data['new_person_name'] ?? ''));

        if ($newPersonName !== '') {
            $isTenant = (bool) ($data['is_rent'] ?? false);
            $person = Person::firstOrCreate(
                ['user_id' => $user->id, 'name' => $newPersonName],
                [
                    'type' => $isTenant ? 'tenant' : 'other',
                    'is_tenant' => $isTenant,
                    'is_active' => true,
                ],
            );

            if ($isTenant && ! $person->is_tenant) {
                $person->update([
                    'type' => 'tenant',
                    'is_tenant' => true,
                ]);
            }

            $data['person_id'] = $person->id;
        }

        unset($data['new_person_name']);

        $flags = $this->classifyFlags($user, $data);

        ExpectedIncome::create(array_merge($data, [
            'user_id' => $user->id,
            'period_month' => Carbon::createFromFormat('Y-m', $data['period_month'])->startOfMonth()->toDateString(),
            'status' => 'pending',
            'is_rent' => $flags['is_rent'],
        ]));

        return back()->with('success', 'Ingreso esperado agregado.');
    }

    public function update(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'person_id' => ['nullable', 'integer', Rule::exists('finance_people', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'new_person_name' => ['nullable', 'string', 'max:255'],
            'is_rent' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $newPersonName = trim((string) ($data['new_person_name'] ?? ''));

        if ($newPersonName !== '') {
            $isTenant = (bool) ($data['is_rent'] ?? false);
            $person = Person::firstOrCreate(
                ['user_id' => $user->id, 'name' => $newPersonName],
                [
                    'type' => $isTenant ? 'tenant' : 'other',
                    'is_tenant' => $isTenant,
                    'is_active' => true,
                ],
            );

            if ($isTenant && ! $person->is_tenant) {
                $person->update([
                    'type' => 'tenant',
                    'is_tenant' => true,
                    'is_active' => true,
                ]);
            }

            $data['person_id'] = $person->id;
        }

        unset($data['new_person_name']);

        $flags = $this->classifyFlags($user, $data);

        $amount = round((float) $data['amount'], 2);
        $receivedAmount = min((float) $income->received_amount, $amount);
        $status = $income->status;

        if ($status !== 'skipped') {
            $status = match (true) {
                $receivedAmount + 0.01 >= $amount => 'received',
                $receivedAmount > 0 => 'partial',
                $income->due_date && $income->due_date->copy()->startOfDay()->lt(today()->startOfDay()) => 'overdue',
                default => 'pending',
            };
        }

        $income->update(array_merge($data, [
            'period_month' => Carbon::createFromFormat('Y-m', $data['period_month'])->startOfMonth()->toDateString(),
            'amount' => $amount,
            'received_amount' => $receivedAmount,
            'status' => $status,
            'is_rent' => $flags['is_rent'],
        ]));

        return redirect()
            ->route('finance.expected-incomes.index', ['month' => Carbon::parse($income->period_month)->format('Y-m')])
            ->with('success', 'Ingreso esperado actualizado.');
    }

    public function copyMonth(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'source_month' => ['required', 'date_format:Y-m'],
            'target_month' => ['required', 'date_format:Y-m', 'different:source_month'],
        ]);

        $sourceMonth = Carbon::createFromFormat('Y-m', $data['source_month'])->startOfMonth();
        $targetMonth = Carbon::createFromFormat('Y-m', $data['target_month'])->startOfMonth();
        $copied = 0;
        $skipped = 0;

        $incomes = ExpectedIncome::where('user_id', $user->id)
            ->whereDate('period_month', $sourceMonth->toDateString())
            ->where(function ($query) {
                $query->whereNull('import_key')
                    ->orWhere('import_key', 'not like', 'rental-contract:%');
            })
            ->orderByRaw('due_date is null, due_date asc')
            ->orderBy('name')
            ->get();

        foreach ($incomes as $income) {
            $exists = ExpectedIncome::where('user_id', $user->id)
                ->whereDate('period_month', $targetMonth->toDateString())
                ->where('name', $income->name)
                ->where(function ($query) use ($income) {
                    if ($income->person_id) {
                        $query->where('person_id', $income->person_id);

                        return;
                    }

                    $query->whereNull('person_id');
                })
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $dueDate = null;
            if ($income->due_date) {
                $dueDate = $targetMonth->copy()
                    ->day(min($income->due_date->day, $targetMonth->daysInMonth))
                    ->toDateString();
            }

            ExpectedIncome::create([
                'user_id' => $user->id,
                'period_month' => $targetMonth->toDateString(),
                'due_date' => $dueDate,
                'name' => $income->name,
                'amount' => $income->amount,
                'received_amount' => 0,
                'status' => 'pending',
                'account_id' => $income->account_id,
                'category_id' => $income->category_id,
                'person_id' => $income->person_id,
                'is_rent' => $income->is_rent,
                'notes' => $income->notes,
            ]);

            $copied++;
        }

        return redirect()
            ->route('finance.expected-incomes.index', ['month' => $targetMonth->format('Y-m')])
            ->with('success', "Ingresos copiados: {$copied} agregados, {$skipped} ya existian.");
    }

    public function link(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($income->period_month->format('Y-m'));

        $movements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->where('movement_type', 'income')
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->get()
            ->sortBy(function (Movement $movement) use ($income) {
                $amountDistance = abs((float) $movement->amount - (float) $income->amount);
                $dateDistance = $income->due_date
                    ? abs($movement->happened_on->diffInDays($income->due_date, false))
                    : 0;

                return str_pad((string) round($amountDistance * 100), 12, '0', STR_PAD_LEFT)
                    . str_pad((string) $dateDistance, 6, '0', STR_PAD_LEFT)
                    . $movement->happened_on->format('Ymd');
            })
            ->values();

        return view('finance.expected-incomes.link', [
            'income' => $income->load(['account', 'category', 'person', 'movement', 'payments.movement']),
            'movements' => $movements,
            'monthValue' => $start->format('Y-m'),
        ]);
    }

    public function linkMovement(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'movement_id' => [
                'required',
                'integer',
                Rule::exists('finance_movements', 'id')->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->where('movement_type', 'income')),
            ],
            'amount_applied' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = Movement::where('user_id', $request->user()->id)
            ->where('movement_type', 'income')
            ->findOrFail($data['movement_id']);

        if ($income->payments()->where('movement_id', $movement->id)->exists()) {
            return back()->with('error', 'Ese movimiento ya está ligado como abono de este ingreso esperado.');
        }

        try {
            $this->incomePayments->addMovementPayment(
                $income,
                $movement,
                isset($data['amount_applied']) ? (float) $data['amount_applied'] : null,
                $data['notes'] ?? null
            );

            $income->refresh();
            $income->update([
                'account_id' => $income->account_id ?: $movement->account_id,
                'category_id' => $income->category_id ?: $movement->category_id,
                'person_id' => $income->person_id ?: $movement->person_id,
                'is_rent' => $income->is_rent || $movement->is_rent,
            ]);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('finance.expected-incomes.index', ['month' => $income->period_month->format('Y-m')])
            ->with('success', 'Abono vinculado con el ingreso esperado.');
    }

    public function unlinkMovement(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $this->incomePayments->unlinkAll($income);

        return back()->with('success', 'Ingreso esperado desligado de sus abonos.');
    }

    public function markReceived(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'received_on' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id))],
        ]);

        $receivedOn = isset($data['received_on']) ? Carbon::parse($data['received_on']) : today();
        $remaining = max(0, (float) $income->amount - (float) $income->received_amount);
        $receivedAmount = round((float) ($data['amount'] ?? $remaining), 2);
        $accountId = $data['account_id'] ?? $income->account_id;

        try {
            $this->incomePayments->createMovementAndPayment(
                $income,
                $receivedOn,
                $receivedAmount,
                $accountId,
                'Abono registrado desde ingresos esperados.'
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Abono registrado como ingreso real.');
    }

    public function markRegistered(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'received_on' => ['nullable', 'date'],
        ]);

        $receivedOn = isset($data['received_on']) ? Carbon::parse($data['received_on']) : today();

        $remaining = max(0, (float) $income->amount - (float) $income->received_amount);

        if ($remaining <= 0) {
            return back()->with('success', 'Ingreso esperado ya estaba cubierto.');
        }

        try {
            $this->incomePayments->addRegisteredPayment(
                $income,
                $receivedOn,
                $remaining,
                'Marcado como ya registrado sin movimiento vinculado.'
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Ingreso marcado como ya registrado.');
    }

    public function destroyPayment(Request $request, ExpectedIncomePayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $this->incomePayments->deletePayment($payment);

        return back()->with('success', 'Abono desligado del ingreso esperado.');
    }

    public function skip(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $income->update(['status' => 'skipped']);

        return back()->with('success', 'Ingreso marcado como no recibido.');
    }

    public function destroy(Request $request, ExpectedIncome $income)
    {
        abort_unless($income->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $income) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $income, 'expected_income');
            $income->delete();

            return $snapshot;
        });

        return back()
            ->with('success', 'Ingreso esperado eliminado.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at->toDateTimeString(),
            ]);
    }

    private function incomeRows($user, Carbon $start, Carbon $end, Collection $incomes): Collection
    {
        $manualRentPersonIds = $incomes
            ->where('is_rent', true)
            ->whereNotNull('person_id')
            ->pluck('person_id')
            ->all();

        $manualRows = $incomes->map(function (ExpectedIncome $income) {
            $amount = (float) $income->amount;
            $received = (float) $income->received_amount;

            return [
                'kind' => 'expected',
                'id' => $income->id,
                'due_date' => $income->due_date,
                'name' => $income->name,
                'category' => $income->category?->name ?? '-',
                'person' => $income->person?->name ?? '-',
                'amount' => $amount,
                'received_amount' => $received,
                'amount_due' => round(max(0, $amount - $received), 2),
                'payment_count' => $income->payments->count(),
                'payments' => $income->payments,
                'status' => $income->status,
                'is_rent' => $income->is_rent,
                'account_id' => $income->account_id,
                'category_id' => $income->category_id,
                'person_id' => $income->person_id,
                'movement_id' => $income->movement_id,
                'movement' => $income->movement,
                'period_month' => $income->period_month,
                'notes' => $income->notes,
            ];
        })->toBase();

        $rentMovements = Movement::with('person')
            ->where('user_id', $user->id)
            ->where('movement_type', 'income')
            ->where('is_rent', true)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->get();

        $rentalRows = RentalContract::with('person')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expected_amount', '>', 0)
            ->where(function ($query) use ($end) {
                $query->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $end->toDateString());
            })
            ->where(function ($query) use ($start) {
                $query->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $start->toDateString());
            })
            ->get()
            ->reject(fn (RentalContract $contract) => in_array($contract->person_id, $manualRentPersonIds, true))
            ->map(function (RentalContract $contract) use ($start, $rentMovements) {
                $personName = $contract->person?->name ?? 'Renta';
                $needle = Str::lower($personName);
                $paidAmount = $rentMovements
                    ->filter(fn (Movement $movement) => $movement->person_id === $contract->person_id
                        || Str::contains(Str::lower($movement->description), $needle))
                    ->sum(fn (Movement $movement) => (float) $movement->amount);
                $amountDue = round(max(0, (float) $contract->expected_amount - (float) $paidAmount), 2);

                if ($amountDue <= 0) {
                    return null;
                }

                $dueDay = $contract->due_day ?: 1;
                $dueDate = $start->copy()->day(min((int) $dueDay, $start->daysInMonth));

                return [
                    'kind' => 'rental-contract',
                    'id' => $contract->id,
                    'due_date' => $dueDate,
                    'name' => $contract->room ? 'Renta cuarto ' . $contract->room : 'Renta San Juan',
                    'category' => 'Rentas San Juan',
                    'person' => $personName,
                    'amount' => $amountDue,
                    'received_amount' => 0.0,
                    'amount_due' => $amountDue,
                    'payment_count' => 0,
                    'payments' => collect(),
                    'status' => $dueDate->lt(today()->startOfDay()) ? 'overdue' : 'pending',
                    'is_rent' => true,
                    'account_id' => null,
                    'category_id' => null,
                    'person_id' => $contract->person_id,
                    'movement_id' => null,
                    'movement' => null,
                    'period_month' => $start->copy(),
                    'notes' => $contract->notes,
                ];
            })
            ->filter()
            ->values()
            ->toBase();

        return $manualRows
            ->merge($rentalRows)
            ->sort(function (array $left, array $right) {
                $dateCompare = ($left['due_date']?->timestamp ?? PHP_INT_MAX) <=> ($right['due_date']?->timestamp ?? PHP_INT_MAX);

                return $dateCompare !== 0 ? $dateCompare : $left['person'] <=> $right['person'];
            })
            ->values();
    }
}
