<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlannedPaymentController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($request->query('month', now()->format('Y-m')));

        $payments = PlannedPayment::with(['account', 'category', 'person', 'movement', 'creditPurchase.account'])
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->orderByRaw('due_date is null, due_date asc')
            ->orderBy('name')
            ->get();

        $creditInstallments = CreditInstallment::with(['creditPurchase.account', 'creditPurchase.category'])
            ->where('user_id', $user->id)
            ->whereBetween('period_month', [$start->toDateString(), $end->toDateString()])
            ->orderByRaw('due_date is null, due_date asc')
            ->orderBy('installment_number')
            ->get();

        $paymentTotals = $this->summaryService->obligationTotals(
            $this->summaryService->monthObligations($user, $start, $end)
        );
        $accounts = $this->accountsFor($user);
        $creditAccounts = $accounts
            ->filter(fn ($account) => in_array($account->type, ['card', 'credit'], true))
            ->values();

        if ($creditAccounts->isEmpty()) {
            $creditAccounts = $accounts;
        }

        $creditPurchases = CreditPurchase::with('account')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->orderByDesc('purchase_date')
            ->orderBy('name')
            ->get();

        $expenseMovements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->get();

        return view('finance.planned.index', [
            'payments' => $payments,
            'creditInstallments' => $creditInstallments,
            'paymentTotals' => $paymentTotals,
            'monthValue' => $start->format('Y-m'),
            'accounts' => $accounts,
            'creditAccounts' => $creditAccounts,
            'creditPurchases' => $creditPurchases,
            'expenseMovements' => $expenseMovements,
            'categories' => $this->categoriesFor($user, 'expense'),
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
            'notes' => ['nullable', 'string'],
        ]);

        $flags = $this->classifyFlags($user, $data);

        PlannedPayment::create(array_merge($data, [
            'user_id' => $user->id,
            'period_month' => Carbon::createFromFormat('Y-m', $data['period_month'])->startOfMonth()->toDateString(),
            'status' => 'pending',
            'is_san_juan' => $flags['is_san_juan'],
        ]));

        return back()->with('success', 'Pago planeado agregado.');
    }

    public function update(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $request->validate([
            'due_date' => ['nullable', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'person_id' => ['nullable', 'integer', Rule::exists('finance_people', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'notes' => ['nullable', 'string'],
        ]);

        $flags = $this->classifyFlags($user, $data);
        $amount = round((float) $data['amount'], 2);
        $paidAmount = (float) $payment->paid_amount;

        if ($payment->status === 'paid') {
            $paidAmount = $amount;
        } elseif ($paidAmount > $amount) {
            $paidAmount = $amount;
        }

        $payment->update(array_merge($data, [
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'is_san_juan' => $flags['is_san_juan'] || (bool) $payment->is_san_juan,
        ]));

        return redirect()
            ->route('finance.planned.index', ['month' => $payment->period_month->format('Y-m')])
            ->with('success', 'Pago planeado actualizado.');
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

        $payments = PlannedPayment::where('user_id', $user->id)
            ->whereDate('period_month', $sourceMonth->toDateString())
            ->orderByRaw('due_date is null, due_date asc')
            ->orderBy('name')
            ->get();

        foreach ($payments as $payment) {
            $exists = PlannedPayment::where('user_id', $user->id)
                ->whereDate('period_month', $targetMonth->toDateString())
                ->where('name', $payment->name)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $dueDate = null;
            if ($payment->due_date) {
                $dueDate = $targetMonth->copy()
                    ->day(min($payment->due_date->day, $targetMonth->daysInMonth))
                    ->toDateString();
            }

            PlannedPayment::create([
                'user_id' => $user->id,
                'period_month' => $targetMonth->toDateString(),
                'due_date' => $dueDate,
                'name' => $payment->name,
                'amount' => $payment->amount,
                'paid_amount' => 0,
                'status' => 'pending',
                'account_id' => $payment->account_id,
                'category_id' => $payment->category_id,
                'person_id' => $payment->person_id,
                'is_credit' => $payment->is_credit,
                'is_san_juan' => $payment->is_san_juan,
                'notes' => $payment->notes,
            ]);

            $copied++;
        }

        return redirect()
            ->route('finance.planned.index', ['month' => $targetMonth->format('Y-m')])
            ->with('success', "Flujo copiado: {$copied} pagos agregados, {$skipped} ya existian.");
    }

    public function markPaid(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
        ]);

        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();
        $remaining = max(0, (float) $payment->amount - (float) $payment->paid_amount);

        $movement = null;

        if ($remaining > 0) {
            $movement = Movement::create([
                'user_id' => $payment->user_id,
                'happened_on' => $paidOn->toDateString(),
                'movement_type' => 'expense',
                'amount' => $remaining,
                'description' => 'Pago planeado: ' . $payment->name,
                'account_id' => $payment->account_id,
                'category_id' => $payment->category_id,
                'person_id' => $payment->person_id,
                'is_san_juan' => $payment->is_san_juan,
                'source' => 'planned_payment',
            ]);
        }

        $payment->update([
            'status' => 'paid',
            'paid_amount' => $payment->amount,
            'paid_on' => $paidOn->toDateString(),
            'movement_id' => $movement?->id ?? $payment->movement_id,
            'credit_purchase_id' => null,
            'is_credit' => false,
        ]);

        return back()->with('success', 'Pago marcado como pagado.');
    }

    public function markPaidWithCredit(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->movement_id) {
            return back()->with('error', 'Este pago ya tiene un movimiento real vinculado; no lo marque con credito para evitar duplicar egresos.');
        }

        $user = $request->user();
        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'credit_purchase_id' => ['nullable', 'integer', Rule::exists('finance_credit_purchases', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
        ]);

        $credit = ! empty($data['credit_purchase_id'])
            ? CreditPurchase::where('user_id', $user->id)->findOrFail($data['credit_purchase_id'])
            : null;
        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();
        $accountId = $data['account_id'] ?? $credit?->account_id ?? $payment->account_id;

        $payment->update([
            'status' => 'paid',
            'paid_amount' => $payment->amount,
            'paid_on' => $paidOn->toDateString(),
            'account_id' => $accountId,
            'credit_purchase_id' => $credit?->id,
            'movement_id' => null,
            'is_credit' => true,
        ]);

        return redirect()
            ->route('finance.planned.index', ['month' => $payment->period_month->format('Y-m')])
            ->with('success', 'Pago marcado como cubierto con credito. La deuda queda en la seccion de creditos.');
    }

    public function link(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($payment->period_month->format('Y-m'));

        $movements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->get()
            ->sortBy(function (Movement $movement) use ($payment) {
                $amountDistance = abs((float) $movement->amount - (float) $payment->amount);
                $dateDistance = $payment->due_date
                    ? abs($movement->happened_on->diffInDays($payment->due_date, false))
                    : 0;

                return str_pad((string) round($amountDistance * 100), 12, '0', STR_PAD_LEFT)
                    . str_pad((string) $dateDistance, 6, '0', STR_PAD_LEFT)
                    . $movement->happened_on->format('Ymd');
            })
            ->values();

        return view('finance.planned.link', [
            'payment' => $payment->load(['account', 'category', 'person', 'movement']),
            'movements' => $movements,
            'monthValue' => $start->format('Y-m'),
        ]);
    }

    public function linkMovement(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'movement_id' => [
                'required',
                'integer',
                Rule::exists('finance_movements', 'id')->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->where('movement_type', 'expense')),
            ],
        ]);

        $movement = Movement::where('user_id', $request->user()->id)
            ->where('movement_type', 'expense')
            ->findOrFail($data['movement_id']);

        $payment->update([
            'status' => 'paid',
            'paid_amount' => $payment->amount,
            'paid_on' => $movement->happened_on->toDateString(),
            'movement_id' => $movement->id,
            'credit_purchase_id' => null,
            'is_credit' => false,
        ]);

        return redirect()
            ->route('finance.planned.index', ['month' => $payment->period_month->format('Y-m')])
            ->with('success', 'Pago vinculado con el movimiento real.');
    }

    public function markRegistered(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
        ]);

        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();

        $payment->update([
            'status' => 'paid',
            'paid_amount' => $payment->amount,
            'paid_on' => $paidOn->toDateString(),
            'credit_purchase_id' => null,
            'is_credit' => false,
        ]);

        return back()->with('success', 'Pago marcado como ya registrado.');
    }

    public function skip(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $payment->update(['status' => 'skipped']);

        return back()->with('success', 'Pago marcado como no pagado.');
    }

    public function destroy(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $payment) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $payment, 'planned_payment');
            $payment->delete();

            return $snapshot;
        });

        return back()
            ->with('success', 'Pago eliminado del flujo.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at->toDateTimeString(),
            ]);
    }
}
