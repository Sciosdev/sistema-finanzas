<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\Movement;
use App\Services\Finance\CreditFreePaymentService;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreditPurchaseController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
        private readonly CreditFreePaymentService $freePayments,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $credits = CreditPurchase::with([
            'account',
            'category',
            'freePayments.movement',
            'installments' => fn ($query) => $query->orderBy('installment_number'),
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('purchase_date')
            ->get();

        $installments = $credits->flatMap->installments;
        $freePayments = $credits->flatMap->freePayments;
        $creditTotals = $credits->mapWithKeys(fn (CreditPurchase $credit) => [
            $credit->id => $this->freePayments->totals($credit),
        ]);
        $creditorSummaries = $this->creditorSummaries($credits, $creditTotals);
        $currentMonth = now()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        $summary = [
            'total' => round($credits->sum(fn (CreditPurchase $credit) => (float) $credit->total_amount), 2),
            'paid' => round($creditTotals->sum('total_paid'), 2),
            'installment_paid' => round($installments->sum(fn (CreditInstallment $installment) => (float) $installment->paid_amount), 2),
            'free_paid' => round($freePayments->sum(fn (CreditFreePayment $payment) => (float) $payment->amount_applied), 2),
            'pending' => round($creditTotals->sum('balance_due'), 2),
            'current_month' => round($installments
                ->filter(fn (CreditInstallment $installment) => $installment->period_month->isSameMonth($currentMonth))
                ->sum(fn (CreditInstallment $installment) => max(0, (float) $installment->amount - (float) $installment->paid_amount)), 2),
            'next_month' => round($installments
                ->filter(fn (CreditInstallment $installment) => $installment->period_month->isSameMonth($nextMonth))
                ->sum(fn (CreditInstallment $installment) => max(0, (float) $installment->amount - (float) $installment->paid_amount)), 2),
            'active_count' => $credits->where('status', '!=', 'paid')->count(),
        ];

        return view('finance.credits.index', [
            'credits' => $credits,
            'creditTotals' => $creditTotals,
            'creditorSummaries' => $creditorSummaries,
            'summary' => $summary,
            'currentMonthLabel' => $currentMonth->format('Y-m'),
            'nextMonthLabel' => $nextMonth->format('Y-m'),
            'accounts' => $this->accountsFor($user),
            'categories' => $this->categoriesFor($user, 'expense'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $this->creditData($request, $user);
        $amounts = $this->creditAmounts($data);

        $credit = CreditPurchase::create([
            'user_id' => $user->id,
            'purchase_date' => $data['purchase_date'],
            'name' => $data['name'],
            'total_amount' => $amounts['total'],
            'months' => $data['months'],
            'first_due_month' => Carbon::createFromFormat('Y-m', $data['first_due_month'])->startOfMonth()->toDateString(),
            'due_day' => $data['due_day'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncInstallments(
            $credit,
            $amounts['total'],
            (int) $data['months'],
            Carbon::createFromFormat('Y-m', $data['first_due_month'])->startOfMonth(),
            $data['due_day'] ?? null,
            $amounts['monthly']
        );

        return back()->with('success', 'Crédito creado con mensualidades.');
    }

    public function update(Request $request, CreditPurchase $credit)
    {
        abort_unless($credit->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);
        $data = $this->creditData($request, $user);
        $amounts = $this->creditAmounts($data);

        $credit->update([
            'purchase_date' => $data['purchase_date'],
            'name' => $data['name'],
            'total_amount' => $amounts['total'],
            'months' => $data['months'],
            'first_due_month' => Carbon::createFromFormat('Y-m', $data['first_due_month'])->startOfMonth()->toDateString(),
            'due_day' => $data['due_day'] ?? null,
            'account_id' => $data['account_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncInstallments(
            $credit,
            $amounts['total'],
            (int) $data['months'],
            Carbon::createFromFormat('Y-m', $data['first_due_month'])->startOfMonth(),
            $data['due_day'] ?? null,
            $amounts['monthly']
        );
        $this->refreshCreditStatus($credit);

        return back()->with('success', 'Crédito actualizado.');
    }

    public function destroy(Request $request, CreditPurchase $credit)
    {
        abort_unless($credit->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $credit) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $credit, 'credit_purchase');

            $credit->delete();

            return $snapshot;
        });

        return back()
            ->with('success', 'Crédito eliminado.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at,
            ]);
    }

    public function markInstallmentPaid(Request $request, CreditInstallment $installment)
    {
        abort_unless($installment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
        ]);

        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();
        $credit = $installment->creditPurchase()->firstOrFail();
        $remaining = max(0, (float) $installment->amount - (float) $installment->paid_amount);

        $movement = null;

        if ($remaining > 0) {
            $movement = Movement::create([
                'user_id' => $installment->user_id,
                'happened_on' => $paidOn->toDateString(),
                'movement_type' => 'expense',
                'amount' => $remaining,
                'description' => 'Crédito: ' . $credit->name . ' ' . $installment->installment_number . '/' . $credit->months,
                'account_id' => $credit->account_id,
                'category_id' => $credit->category_id,
                'source' => 'credit_installment',
            ]);
        }

        $installment->update([
            'status' => 'paid',
            'paid_amount' => $installment->amount,
            'paid_on' => $paidOn->toDateString(),
            'movement_id' => $movement?->id ?? $installment->movement_id,
        ]);

        $this->refreshCreditStatus($credit);

        return back()->with('success', 'Mensualidad marcada como pagada.');
    }

    public function markInstallmentRegistered(Request $request, CreditInstallment $installment)
    {
        abort_unless($installment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
        ]);

        $paidOn = isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today();
        $credit = $installment->creditPurchase()->firstOrFail();

        $installment->update([
            'status' => 'paid',
            'paid_amount' => $installment->amount,
            'paid_on' => $paidOn->toDateString(),
        ]);

        $this->refreshCreditStatus($credit);

        return back()->with('success', 'Mensualidad marcada como ya registrada.');
    }

    public function updateInstallment(Request $request, CreditInstallment $installment)
    {
        abort_unless($installment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in(['pending', 'paid'])],
            'paid_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $isPaid = $data['status'] === 'paid';

        $installment->update([
            'period_month' => Carbon::createFromFormat('Y-m', $data['period_month'])->startOfMonth()->toDateString(),
            'due_date' => $data['due_date'] ?? null,
            'amount' => $amount,
            'status' => $data['status'],
            'paid_amount' => $isPaid ? $amount : 0,
            'paid_on' => $isPaid ? ($data['paid_on'] ?? today()->toDateString()) : null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->refreshCreditFromInstallments($installment->creditPurchase()->firstOrFail());

        return back()->with('success', 'Mensualidad actualizada.');
    }

    public function storeFreePayment(Request $request, CreditPurchase $credit)
    {
        abort_unless($credit->user_id === $request->user()->id, 403);

        $user = $request->user();
        $data = $request->validate([
            'paid_on' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'movement_id' => ['nullable', 'integer', Rule::exists('finance_movements', 'id')->where(fn ($query) => $query->where('user_id', $user->id)->where('movement_type', 'expense'))],
            'notes' => ['nullable', 'string'],
        ]);

        $movement = ! empty($data['movement_id'])
            ? Movement::where('user_id', $user->id)->whereKey($data['movement_id'])->first()
            : null;

        try {
            $this->freePayments->createFreePayment(
                $credit,
                isset($data['paid_on']) ? Carbon::parse($data['paid_on']) : today(),
                (float) $data['amount'],
                $data['account_id'] ?? null,
                $data['category_id'] ?? null,
                $data['notes'] ?? null,
                $movement,
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Abono libre registrado como egreso real.');
    }

    public function destroyFreePayment(Request $request, CreditFreePayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $payment) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $payment, 'credit_free_payment');

            $this->freePayments->deleteFreePayment($payment);

            return $snapshot;
        });

        return back()
            ->with('success', 'Abono libre eliminado.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at,
            ]);
    }

    public function destroyInstallment(Request $request, CreditInstallment $installment)
    {
        abort_unless($installment->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $installment) {
            $credit = $installment->creditPurchase()->firstOrFail();
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $installment, 'credit_installment');

            $installment->delete();
            $this->renumberInstallments($credit);
            $this->refreshCreditFromInstallments($credit);

            return $snapshot;
        });

        return back()
            ->with('success', 'Mensualidad eliminada.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at,
            ]);
    }

    private function creditData(Request $request, $user): array
    {
        return $request->validate([
            'purchase_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'amount_mode' => ['nullable', Rule::in(['total', 'monthly'])],
            'total_amount' => ['nullable', 'numeric', 'min:0.01'],
            'monthly_amount' => ['nullable', 'numeric', 'min:0.01'],
            'months' => ['required', 'integer', 'min:1', 'max:60'],
            'first_due_month' => ['required', 'date_format:Y-m'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function creditAmounts(array $data): array
    {
        $mode = $data['amount_mode'] ?? 'total';
        $months = (int) $data['months'];

        if ($mode === 'monthly') {
            if (empty($data['monthly_amount'])) {
                throw ValidationException::withMessages([
                    'monthly_amount' => 'Captura el pago mensual del crédito.',
                ]);
            }

            $monthly = round((float) $data['monthly_amount'], 2);

            return [
                'mode' => 'monthly',
                'monthly' => $monthly,
                'total' => round($monthly * $months, 2),
            ];
        }

        if (empty($data['total_amount'])) {
            throw ValidationException::withMessages([
                'total_amount' => 'Captura el total del crédito.',
            ]);
        }

        return [
            'mode' => 'total',
            'monthly' => null,
            'total' => round((float) $data['total_amount'], 2),
        ];
    }

    private function syncInstallments(CreditPurchase $credit, float $total, int $months, Carbon $firstMonth, ?int $dueDay, ?float $monthlyAmount = null): void
    {
        $amounts = $this->installmentAmounts($total, $months, $monthlyAmount);
        $existing = $credit->installments()->get()->keyBy('installment_number');

        for ($index = 1; $index <= $months; $index++) {
            $period = $firstMonth->copy()->addMonths($index - 1);
            $dueDate = $dueDay
                ? $period->copy()->day(min((int) $dueDay, $period->daysInMonth))->toDateString()
                : null;
            $amount = $amounts[$index - 1];
            $installment = $existing->get($index);
            $paidAmount = $installment
                ? min((float) $installment->paid_amount, $amount)
                : 0;

            if ($installment?->status === 'paid') {
                $paidAmount = $amount;
            }

            CreditInstallment::updateOrCreate(
                [
                    'credit_purchase_id' => $credit->id,
                    'installment_number' => $index,
                ],
                [
                    'user_id' => $credit->user_id,
                    'period_month' => $period->toDateString(),
                    'due_date' => $dueDate,
                    'amount' => $amount,
                    'paid_amount' => $paidAmount,
                    'paid_on' => $installment?->paid_on,
                    'status' => $installment?->status === 'paid' ? 'paid' : 'pending',
                    'movement_id' => $installment?->movement_id,
                    'notes' => $installment?->notes,
                ]
            );
        }

        $credit->installments()
            ->where('installment_number', '>', $months)
            ->delete();
    }

    private function installmentAmounts(float $total, int $months, ?float $monthlyAmount = null): array
    {
        if ($monthlyAmount !== null) {
            return array_fill(0, $months, round($monthlyAmount, 2));
        }

        $baseAmount = round($total / $months, 2);
        $amounts = [];
        $createdAmount = 0.0;

        for ($index = 1; $index <= $months; $index++) {
            $amount = $index === $months
                ? round($total - $createdAmount, 2)
                : $baseAmount;
            $amounts[] = $amount;
            $createdAmount += $amount;
        }

        return $amounts;
    }

    private function renumberInstallments(CreditPurchase $credit): void
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

        $this->freePayments->refreshCreditFromInstallments($credit);
    }

    private function refreshCreditStatus(CreditPurchase $credit): void
    {
        $this->freePayments->syncCreditStatus($credit);
    }

    private function creditorSummaries($credits, $creditTotals)
    {
        return $credits
            ->groupBy(fn (CreditPurchase $credit) => $credit->account?->name ?: 'Sin acreedor')
            ->map(function ($group, string $creditorName) use ($creditTotals) {
                $style = $this->creditorStyle($creditorName);
                $items = $group
                    ->map(function (CreditPurchase $credit) use ($creditTotals, $style, $creditorName) {
                        $totals = $creditTotals[$credit->id] ?? $this->freePayments->totals($credit);

                        return [
                            'id' => $credit->id,
                            'name' => $credit->name,
                            'notes' => $credit->notes,
                            'months' => $credit->months,
                            'status' => $credit->status,
                            'creditor' => $creditorName,
                            'style' => $style,
                            'total' => (float) $credit->total_amount,
                            'paid' => (float) ($totals['total_paid'] ?? 0),
                            'pending' => (float) ($totals['balance_due'] ?? 0),
                        ];
                    })
                    ->sortByDesc('pending')
                    ->values();

                return [
                    'name' => $creditorName,
                    'key' => str('creditor-' . $creditorName)->slug()->toString() ?: 'sin-acreedor',
                    'style' => $style,
                    'count' => $items->count(),
                    'pending' => round($items->sum('pending'), 2),
                    'paid' => round($items->sum('paid'), 2),
                    'total' => round($items->sum('total'), 2),
                    'credits' => $items,
                ];
            })
            ->sortByDesc('pending')
            ->values();
    }

    private function creditorStyle(string $name): array
    {
        $normalized = str($name)->ascii()->lower()->toString();

        $styles = [
            'nu' => ['label' => 'NU', 'color' => '#7c3aed', 'soft' => 'rgba(124, 58, 237, .14)', 'text' => '#c4b5fd', 'badge_text' => '#ffffff'],
            'mpw' => ['label' => 'MPW', 'color' => '#facc15', 'soft' => 'rgba(250, 204, 21, .16)', 'text' => '#fde68a', 'badge_text' => '#111827'],
            'didi' => ['label' => 'DIDI', 'color' => '#f97316', 'soft' => 'rgba(249, 115, 22, .16)', 'text' => '#fdba74', 'badge_text' => '#111827'],
            'mercado pago' => ['label' => 'Mercado Pago', 'color' => '#00b4d8', 'soft' => 'rgba(0, 180, 216, .16)', 'text' => '#67e8f9', 'badge_text' => '#111827'],
            'bbva' => ['label' => 'BBVA', 'color' => '#2563eb', 'soft' => 'rgba(37, 99, 235, .16)', 'text' => '#93c5fd', 'badge_text' => '#ffffff'],
            'amazon' => ['label' => 'Amazon', 'color' => '#f59e0b', 'soft' => 'rgba(245, 158, 11, .16)', 'text' => '#fcd34d', 'badge_text' => '#111827'],
            'sin acreedor' => ['label' => 'Sin acreedor', 'color' => '#94a3b8', 'soft' => 'rgba(148, 163, 184, .14)', 'text' => '#cbd5e1', 'badge_text' => '#111827'],
        ];

        foreach ($styles as $needle => $style) {
            if (str_contains($normalized, $needle)) {
                return $style;
            }
        }

        return ['label' => $name, 'color' => '#22c55e', 'soft' => 'rgba(34, 197, 94, .14)', 'text' => '#86efac', 'badge_text' => '#111827'];
    }
}
