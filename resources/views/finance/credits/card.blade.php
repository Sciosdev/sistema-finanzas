    @php
        $totals = $creditTotals[$credit->id] ?? [
            'total_original' => (float) $credit->total_amount,
            'installment_paid' => round($credit->installments->sum(fn ($installment) => (float) $installment->paid_amount), 2),
            'free_paid' => round($credit->freePayments->where('payment_type', '!=', 'refund')->sum(fn ($payment) => (float) $payment->amount_applied), 2),
            'refunded' => round($credit->freePayments->where('payment_type', 'refund')->sum(fn ($payment) => (float) $payment->amount_applied), 2),
            'total_paid' => 0,
            'balance_due' => 0,
        ];
        $creditPaid = (float) $totals['total_paid'];
        $creditFreePaid = (float) $totals['free_paid'];
        $creditInstallmentPaid = (float) $totals['installment_paid'];
        $creditPending = (float) $totals['balance_due'];
        $creditorName = $credit->account?->name ?? 'Sin acreedor';
        $creditorSummary = $creditorSummaries->firstWhere('name', $creditorName);
        $creditorStyle = $creditorSummary['style'] ?? ['color' => '#22c55e', 'soft' => 'rgba(34, 197, 94, .14)', 'text' => '#86efac'];
        $firstInstallment = $credit->installments->first();
        $monthlyAmount = $firstInstallment ? (float) $firstInstallment->amount : 0;
        $creditFormId = 'credit-form-' . $credit->id;
        $creditCardItem = $creditorSummary ? collect($creditorSummary['credits'])->firstWhere('id', $credit->id) : null;
        $creditCurrentDue = (float) ($creditCardItem['current_due'] ?? 0);
        $creditorKey = $creditorSummary['key'] ?? 'sin-acreedor';
        // El calendario combina abonos y devoluciones; mostrar por separado
        // las devoluciones fijadas a cada mensualidad y los abonos de efectivo.
        $schedule = $creditSchedules[$credit->id] ?? ['free_applied' => [], 'effective' => [], 'next_installment_id' => null, 'max_free_payment' => 0.0];
        $freeApplied = $schedule['free_applied'];
        $refundApplied = $credit->freePayments->where('payment_type', 'refund')
            ->groupBy('target_installment_id')
            ->map(fn ($payments) => round((float) $payments->sum('amount_applied'), 2));
        $effectiveDue = $schedule['effective'];
        $maxFreePayment = (float) $schedule['max_free_payment'];
        $nextInstallment = $schedule['next_installment_id']
            ? $credit->installments->firstWhere('id', $schedule['next_installment_id'])
            : null;
        $nextOriginal = $nextInstallment ? (float) $nextInstallment->amount : 0.0;
        $nextApplied = $nextInstallment ? (float) ($freeApplied[$nextInstallment->id] ?? 0) : 0.0;
        $nextRefundApplied = $nextInstallment ? min($nextApplied, (float) $refundApplied->get($nextInstallment->id, 0)) : 0.0;
        $nextFreeApplied = round(max(0, $nextApplied - $nextRefundApplied), 2);
        $nextEffective = $nextInstallment ? (float) ($effectiveDue[$nextInstallment->id] ?? 0) : 0.0;
    @endphp
    <div class="card finance-credit-card" id="credit-{{ $credit->id }}" style="border-left: 4px solid {{ $creditorStyle['color'] }};" data-creditor-key="{{ $creditorKey }}" data-current-due="{{ $creditCurrentDue }}" data-balance="{{ $creditPending }}">
        <div class="card-header finance-credit-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4 class="card-title mb-0">{{ $credit->name }}</h4>
                    <span class="badge" style="background: {{ $creditorStyle['soft'] }}; color: {{ $creditorStyle['text'] }}; border: 1px solid {{ $creditorStyle['color'] }};">
                        Se debe a {{ $creditorName }}
                    </span>
                    @if ($credit->is_manual_schedule)
                        <span class="badge badge-soft-info">
                            <i data-lucide="calendar-check" class="me-1"></i>Calendario manual
                        </span>
                    @endif
                </div>
                <p class="text-muted mb-0">
                    Total original {{ $money($credit->total_amount) }} - {{ $credit->months }} meses - {{ \App\Support\FinanceLabels::creditStatus($credit->status) }}
                    @if ($credit->notes)
                        <span class="ms-1">| {{ $credit->notes }}</span>
                    @endif
                </p>
                @if ($nextInstallment)
                    <p class="mb-0 mt-1 small">
                        <span class="text-muted">Siguiente mensualidad ({{ $nextInstallment->period_month?->format('Y-m') }}):</span>
                        <span class="ms-1">en flujo {{ $nextInstallment->effectiveDueDate()?->format('Y-m-d') ?? '-' }}</span>
                        <span class="ms-1">original {{ $money($nextOriginal) }}</span>
                        @if ($nextFreeApplied > 0)
                            <span class="ms-1 text-info">- abonos libres {{ $money($nextFreeApplied) }}</span>
                        @endif
                        @if ($nextRefundApplied > 0)
                            <span class="ms-1 text-info">- devoluciones {{ $money($nextRefundApplied) }}</span>
                        @endif
                        <span class="ms-1 fw-semibold text-warning">= pendiente efectivo {{ $money($nextEffective) }}</span>
                    </p>
                @endif
            </div>
            <div class="finance-credit-balances d-flex align-items-center flex-wrap gap-2">
                <span class="badge badge-soft-success">Pagado total {{ $money($creditPaid) }}</span>
                <span class="badge badge-soft-primary">Mensualidades {{ $money($creditInstallmentPaid) }}</span>
                <span class="badge badge-soft-info">Abonos libres {{ $money($creditFreePaid) }}</span>
                @if (($totals['refunded'] ?? 0) > 0)
                    <span class="badge badge-soft-info">Devoluciones {{ $money($totals['refunded']) }}</span>
                @endif
                <span class="badge badge-soft-warning">Saldo real {{ $money($creditPending) }}</span>
                <a href="#free-payments-{{ $credit->id }}" class="btn btn-sm btn-outline-primary" data-credit-open>Ver abonos</a>
            </div>
        </div>
        <details class="finance-credit-details" data-credit-details @if (($expandedCreditId ?? null) === $credit->id) open @endif>
            <summary class="card-body border-top fw-semibold">Ver mensualidades y editar</summary>
            <div data-credit-detail-content data-credit-id="{{ $credit->id }}" data-url="{{ route('finance.credits.details', $credit) }}" data-loaded="{{ ($expandedCreditId ?? null) === $credit->id ? '1' : '0' }}">
                @if (($expandedCreditId ?? null) === $credit->id)
                    @include('finance.credits.details')
                @else
                    <div class="card-body text-muted" role="status">Abre este crédito para consultar sus mensualidades y abonos.</div>
                @endif
                <noscript><a class="btn btn-outline-primary m-3" href="{{ route('finance.credits.index', ['credit' => $credit->id]) }}#credit-{{ $credit->id }}">Abrir crédito</a></noscript>
            </div>
        </details>
    </div>
