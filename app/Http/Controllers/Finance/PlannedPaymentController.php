<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\Account;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlannedPaymentController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
    ) {}

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
            'creditInstallmentSummaries' => $this->creditInstallmentSummaries($creditInstallments),
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

    private function creditInstallmentSummaries(Collection $creditInstallments): Collection
    {
        return $creditInstallments
            ->filter(fn (CreditInstallment $installment) => $installment->status !== 'paid')
            ->map(fn (CreditInstallment $installment) => [
                'installment' => $installment,
                'amount_due' => round(max(0, (float) $installment->amount - (float) $installment->paid_amount), 2),
            ])
            ->filter(fn (array $row) => $row['amount_due'] > 0)
            ->groupBy(fn (array $row) => $row['installment']->creditPurchase?->account_id ?: 'none')
            ->map(function (Collection $rows) {
                /** @var CreditInstallment $first */
                $first = $rows->first()['installment'];
                $account = $first->creditPurchase?->account;
                $nextDueDate = $rows
                    ->pluck('installment.due_date')
                    ->filter()
                    ->sortBy(fn (Carbon $date) => $date->timestamp)
                    ->first();

                return [
                    'name' => $account?->name ?? 'Sin acreedor',
                    'color' => $this->safeHexColor($account?->color),
                    'amount' => round($rows->sum('amount_due'), 2),
                    'count' => $rows->count(),
                    'next_due_date' => $nextDueDate,
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    private function safeHexColor(?string $color): string
    {
        return is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1
            ? $color
            : '#22c55e';
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
            'is_automatic_charge' => ['sometimes', 'boolean'],
            'is_forced_charge_window' => ['sometimes', 'boolean'],
            'charge_window_before_days' => ['nullable', 'integer', 'min:0', 'max:7'],
            'charge_window_after_days' => ['nullable', 'integer', 'min:0', 'max:7'],
        ]);

        $flags = $this->classifyFlags($user, $data);
        $automaticCharge = $this->automaticChargeData($data);

        PlannedPayment::create(array_merge($data, $automaticCharge, [
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
            'is_automatic_charge' => ['sometimes', 'boolean'],
            'is_forced_charge_window' => ['sometimes', 'boolean'],
            'charge_window_before_days' => ['nullable', 'integer', 'min:0', 'max:7'],
            'charge_window_after_days' => ['nullable', 'integer', 'min:0', 'max:7'],
        ]);

        $flags = $this->classifyFlags($user, $data);
        $automaticCharge = $this->automaticChargeData($data);
        $amount = round((float) $data['amount'], 2);
        $paidAmount = (float) $payment->paid_amount;

        if ($payment->status === 'paid') {
            $paidAmount = $amount;
        } elseif ($paidAmount > $amount) {
            $paidAmount = $amount;
        }

        $payment->update(array_merge($data, $automaticCharge, [
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
                'is_automatic_charge' => $payment->is_automatic_charge,
                'is_forced_charge_window' => $payment->is_forced_charge_window,
                'charge_window_before_days' => $payment->charge_window_before_days,
                'charge_window_after_days' => $payment->charge_window_after_days,
                'notes' => $payment->notes,
            ]);

            $copied++;
        }

        return redirect()
            ->route('finance.planned.index', ['month' => $targetMonth->format('Y-m')])
            ->with('success', "Flujo copiado: {$copied} pagos agregados, {$skipped} ya existian.");
    }

    public function bulkAutomaticCharge(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                Rule::exists('finance_planned_payments', 'id')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'bulk_action' => ['required', Rule::in(['set_forced_automatic', 'set_automatic_only', 'clear_automatic'])],
            'charge_window_before_days' => ['nullable', 'integer', 'min:0', 'max:7'],
            'charge_window_after_days' => ['nullable', 'integer', 'min:0', 'max:7'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $values = match ($data['bulk_action']) {
            'set_forced_automatic' => [
                'is_automatic_charge' => true,
                'is_forced_charge_window' => true,
                'charge_window_before_days' => (int) ($data['charge_window_before_days'] ?? 0),
                'charge_window_after_days' => (int) ($data['charge_window_after_days'] ?? 0),
            ],
            'set_automatic_only' => [
                'is_automatic_charge' => true,
                'is_forced_charge_window' => false,
                'charge_window_before_days' => 0,
                'charge_window_after_days' => 0,
            ],
            default => [
                'is_automatic_charge' => false,
                'is_forced_charge_window' => false,
                'charge_window_before_days' => 0,
                'charge_window_after_days' => 0,
            ],
        };

        $updated = PlannedPayment::where('user_id', $user->id)
            ->whereIn('id', $data['ids'])
            ->update($values);

        $month = $data['month']
            ?? PlannedPayment::where('user_id', $user->id)
                ->whereIn('id', $data['ids'])
                ->orderBy('period_month')
                ->value('period_month');

        $monthValue = $month ? Carbon::parse($month)->format('Y-m') : now()->format('Y-m');

        return redirect()
            ->route('finance.planned.index', ['month' => $monthValue])
            ->with('success', "Se actualizaron {$updated} pagos planeados.");
    }

    public function markPaid(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where('user_id', $payment->user_id)],
        ]);

        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();
        // Cuenta de donde salió el dinero: la elegida al pagar o, si no, la del
        // pago planeado. Sin cuenta el egreso no se puede conciliar en cortes.
        $accountId = $data['account_id'] ?? $payment->account_id;
        $remaining = max(0, (float) $payment->amount - (float) $payment->paid_amount);

        $movement = null;

        if ($remaining > 0) {
            $movement = Movement::create([
                'user_id' => $payment->user_id,
                'happened_on' => $paidOn->toDateString(),
                'movement_type' => 'expense',
                'amount' => $remaining,
                'description' => 'Pago planeado: '.$payment->name,
                'account_id' => $accountId,
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

    public function markPaidWithNewCredit(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->movement_id) {
            return back()->with('error', 'Este pago ya tiene un movimiento real vinculado; no lo pagues con credito para evitar duplicar egresos.');
        }

        $user = $request->user();
        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'months' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();
        $months = (int) ($data['months'] ?? 1);
        $accountId = $data['account_id'] ?? $payment->account_id;
        $total = round((float) $payment->amount, 2);

        // Por defecto la primera mensualidad cae en el mes/día del pago planeado.
        $firstMonth = $payment->period_month->copy()->startOfMonth();
        $dueDay = $payment->due_date?->day;

        // Si la tarjeta tiene ciclo configurado (corte/pago), calcula la fecha real
        // de pago con ese ciclo, igual que la creacion normal de creditos. Asi un
        // cargo hecho despues del corte se va al estado de cuenta del mes siguiente.
        $account = $accountId ? Account::where('user_id', $user->id)->find($accountId) : null;
        if ($account && $account->hasCreditCycle()) {
            $due = $account->firstDueDateFor($paidOn->copy());
            if ($due) {
                $firstMonth = $due->copy()->startOfMonth();
                $dueDay = (int) $due->day;
            }
        }

        $credit = DB::transaction(function () use ($user, $payment, $paidOn, $months, $accountId, $total, $firstMonth, $dueDay) {
            $credit = CreditPurchase::create([
                'user_id' => $user->id,
                'purchase_date' => $paidOn->toDateString(),
                'name' => $payment->name,
                'total_amount' => $total,
                'months' => $months,
                'first_due_month' => $firstMonth->toDateString(),
                'due_day' => $dueDay,
                'account_id' => $accountId,
                'category_id' => $payment->category_id,
                'status' => 'active',
                'notes' => 'Generado desde flujo planeado: '.$payment->name,
            ]);

            $this->createInstallmentsForCredit($credit, $total, $months, $firstMonth, $dueDay);

            $payment->update([
                'status' => 'paid',
                'paid_amount' => $payment->amount,
                'paid_on' => $paidOn->toDateString(),
                'account_id' => $accountId,
                'credit_purchase_id' => $credit->id,
                'movement_id' => null,
                'is_credit' => true,
            ]);

            return $credit;
        });

        return redirect()
            ->route('finance.planned.index', ['month' => $payment->period_month->format('Y-m')])
            ->with('success', 'Credito "'.$credit->name.'" creado y pago cubierto. La deuda quedo en la seccion de creditos.');
    }

    /**
     * Reparte el total en mensualidades iguales (el ultimo absorbe el redondeo),
     * igual que la creacion normal de creditos.
     */
    private function createInstallmentsForCredit(CreditPurchase $credit, float $total, int $months, Carbon $firstMonth, ?int $dueDay): void
    {
        $base = round($total / $months, 2);
        $created = 0.0;

        for ($index = 1; $index <= $months; $index++) {
            $period = $firstMonth->copy()->addMonths($index - 1);
            $amount = $index === $months ? round($total - $created, 2) : $base;
            $created += $amount;
            $dueDate = $dueDay
                ? $period->copy()->day(min($dueDay, $period->daysInMonth))->toDateString()
                : null;

            CreditInstallment::create([
                'credit_purchase_id' => $credit->id,
                'user_id' => $credit->user_id,
                'period_month' => $period->toDateString(),
                'due_date' => $dueDate,
                'installment_number' => $index,
                'amount' => $amount,
                'paid_amount' => 0,
                'status' => 'pending',
            ]);
        }
    }

    private function automaticChargeData(array $data): array
    {
        $forcedWindow = (bool) ($data['is_forced_charge_window'] ?? false);
        $automaticCharge = (bool) ($data['is_automatic_charge'] ?? false) || $forcedWindow;

        return [
            'is_automatic_charge' => $automaticCharge,
            'is_forced_charge_window' => $forcedWindow,
            'charge_window_before_days' => (int) ($data['charge_window_before_days'] ?? 0),
            'charge_window_after_days' => (int) ($data['charge_window_after_days'] ?? 0),
        ];
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
                    .str_pad((string) $dateDistance, 6, '0', STR_PAD_LEFT)
                    .$movement->happened_on->format('Ymd');
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

    public function revert(Request $request, PlannedPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if (! in_array($payment->status, ['paid', 'skipped'], true)) {
            return back()->with('error', 'Este pago ya esta pendiente; no hay nada que revertir.');
        }

        $notes = [];

        DB::transaction(function () use ($payment, &$notes) {
            // Movimiento generado al marcar como pagado: se elimina. Si era un
            // movimiento real vinculado (capturado aparte), solo se desvincula.
            if ($payment->movement_id) {
                $movement = $payment->movement;

                if ($movement && $movement->source === 'planned_payment') {
                    $movement->delete();
                    $notes[] = 'se elimino el movimiento de gasto generado';
                } else {
                    $notes[] = 'se desvinculo el movimiento real (no se elimino)';
                }
            }

            // Credito creado automaticamente desde este pago: se elimina junto
            // con sus mensualidades, siempre que no tenga abonos ni lo use otro
            // pago planeado. Un credito preexistente solo se desvincula.
            if ($payment->credit_purchase_id) {
                $credit = $payment->creditPurchase;

                $wasGeneratedFromPlanned = $credit
                    && str_starts_with((string) $credit->notes, 'Generado desde flujo planeado:');
                $hasPayments = $credit
                    && ($credit->installments()->where('paid_amount', '>', 0)->exists()
                        || $credit->freePayments()->exists());
                $usedByOtherPayment = $credit
                    && PlannedPayment::where('credit_purchase_id', $credit->id)
                        ->where('id', '!=', $payment->id)
                        ->exists();

                if ($credit && $wasGeneratedFromPlanned && ! $hasPayments && ! $usedByOtherPayment) {
                    $credit->installments()->delete();
                    $credit->delete();
                    $notes[] = 'se elimino el credito "'.$credit->name.'" y sus mensualidades';
                } elseif ($credit) {
                    $notes[] = 'se desvinculo el credito "'.$credit->name.'" (revisalo en la seccion de creditos)';
                }
            }

            $payment->update([
                'status' => 'pending',
                'paid_amount' => 0,
                'paid_on' => null,
                'movement_id' => null,
                'credit_purchase_id' => null,
                'is_credit' => false,
            ]);
        });

        $message = 'Pago revertido a pendiente'.($notes !== [] ? '; '.implode(' y ', $notes) : '').'.';

        return back()->with('success', $message);
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
