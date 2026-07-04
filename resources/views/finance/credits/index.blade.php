@extends('layouts.vertical', ['title' => 'Créditos'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $creditorSummaries = collect($creditorSummaries ?? []);
    $summaryWithoutOnix = $summaryWithoutOnix ?? [];
@endphp

@include('finance.partials.flash')

@if (session('recalculated') && count(session('recalculated')) > 0)
    <div class="alert alert-info">
        <strong>Créditos recalculados según el ciclo de su tarjeta:</strong>
        <ul class="mb-0 mt-2">
            @foreach (session('recalculated') as $row)
                <li>
                    <strong>{{ $row['name'] }}</strong> ({{ $row['card'] }}):
                    {{ $row['from'] }} → <strong>{{ $row['to'] }}</strong>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row align-items-center mb-3">
    <div class="col-md-7">
        <h4 class="mb-0 fw-semibold">Créditos manuales</h4>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <form method="POST" action="{{ route('finance.credits.recalculate-dates') }}" class="d-inline"
              onsubmit="return confirm('Recalculará la fecha de pago de los créditos existentes según el corte/pago de su tarjeta. No cambia montos ni pagos. ¿Continuar?');">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i data-lucide="refresh-cw" class="me-1"></i>Recalcular fechas con el ciclo de tarjeta
            </button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Deuda total en créditos</p>
                <h4 class="fw-semibold text-primary mb-0">{{ $money($summary['total'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Pagado acumulado</p>
                <h4 class="fw-semibold text-success mb-0">{{ $money($summary['paid'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Pagado este mes</p>
                <h4 class="fw-semibold text-info mb-0">{{ $money($summary['paid_this_month'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Pendiente total</p>
                <h4 class="fw-semibold text-warning mb-0">{{ $money($summary['pending'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Créditos activos</p>
                <h4 class="fw-semibold mb-0">{{ $summary['active_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card mb-0 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted mb-1">Debes pagar este mes</p>
                    <h4 class="fw-semibold text-warning mb-0">{{ $money($summary['current_month'] ?? 0) }}</h4>
                </div>
                <span class="badge badge-soft-primary">{{ $currentMonthLabel }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card mb-0 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted mb-1">Debes pagar el siguiente mes</p>
                    <h4 class="fw-semibold text-warning mb-0">{{ $money($summary['next_month'] ?? 0) }}</h4>
                </div>
                <span class="badge badge-soft-primary">{{ $nextMonthLabel }}</span>
            </div>
        </div>
    </div>
    @if ($creditLineSummary['has_limits'] ?? false)
        <div class="col-12">
            <div class="card mb-0 border border-success border-opacity-25">
                <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                    <div>
                        <p class="text-muted mb-1">Crédito disponible en todas tus tarjetas</p>
                        <h3 class="fw-semibold text-success mb-0">{{ $money($creditLineSummary['available']) }}</h3>
                    </div>
                    <div class="text-lg-end small text-muted">
                        Límite total {{ $money($creditLineSummary['limit']) }} · Usado {{ $money($creditLineSummary['used']) }} · {{ $creditLineSummary['cards'] }} tarjeta(s) con límite
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="col-12">
        <div class="card mb-0 border border-info border-opacity-25">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
                    <div>
                        <p class="text-muted mb-1">Vista realista sin el crédito del Onix</p>
                        <h5 class="mb-0">Lo que debes en créditos normales, separado del carro.</h5>
                    </div>
                    <span class="badge badge-soft-info align-self-start align-self-lg-center">No incluye créditos llamados Onix ni acreedor Onix</span>
                </div>
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <p class="text-muted mb-1">Deuda sin Onix</p>
                        <h4 class="fw-semibold text-info mb-0">{{ $money($summaryWithoutOnix['total'] ?? 0) }}</h4>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <p class="text-muted mb-1">Pendiente sin Onix</p>
                        <h4 class="fw-semibold text-warning mb-0">{{ $money($summaryWithoutOnix['pending'] ?? 0) }}</h4>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <p class="text-muted mb-1">Este mes sin Onix</p>
                        <h4 class="fw-semibold text-warning mb-0">{{ $money($summaryWithoutOnix['current_month'] ?? 0) }}</h4>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <p class="text-muted mb-1">Siguiente mes sin Onix</p>
                        <h4 class="fw-semibold text-warning mb-0">{{ $money($summaryWithoutOnix['next_month'] ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($creditorSummaries->isNotEmpty())
    <div class="card">
        <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
            <div>
                <h4 class="card-title mb-1">A quién se le debe</h4>
                <p class="text-muted mb-0">Presiona una caja para filtrar la lista y desplazarte a los créditos de ese acreedor/tarjeta.</p>
            </div>
            <span class="badge badge-soft-primary align-self-lg-center">Saldo pendiente real {{ $money($summary['pending'] ?? 0) }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ($creditorSummaries as $creditor)
                    @php
                        $style = $creditor['style'];
                    @endphp
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="d-flex flex-column h-100">
                        <button
                            type="button"
                            class="w-100 text-start border-0 rounded-2 p-3 flex-grow-1"
                            style="background: {{ $style['soft'] }}; border-left: 4px solid {{ $style['color'] }} !important; cursor: pointer;"
                            title="Filtrar la lista por {{ $creditor['name'] }}"
                            data-credit-creditor="{{ $creditor['key'] }}"
                        >
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="fw-semibold" style="color: {{ $style['text'] }};">{{ $creditor['name'] }}</span>
                                <span class="badge" style="background: {{ $style['color'] }}; color: {{ $style['badge_text'] ?? '#111827' }};">{{ $creditor['count'] }}</span>
                            </div>
                            <div class="row g-2 mt-3">
                                <div class="col-6">
                                    <div class="text-muted small">Este mes se debe</div>
                                    <div class="fw-semibold" style="color: {{ $style['text'] }};">{{ $money($creditor['current_due']) }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Se pagó este mes</div>
                                    <div class="fw-semibold text-success">{{ $money($creditor['paid_this_month']) }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Siguiente mes</div>
                                    <div class="fw-semibold text-warning">{{ $money($creditor['next_due']) }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Total se le debe</div>
                                    <div class="fw-semibold" style="color: {{ $style['text'] }};">{{ $money($creditor['pending']) }}</div>
                                </div>
                            </div>
                            @if (! is_null($creditor['credit_limit'] ?? null))
                                <div class="mt-3 pt-2 border-top" style="border-color: {{ $style['color'] }}33 !important;">
                                    <div class="d-flex flex-wrap justify-content-between gap-1 small" style="color: {{ $style['text'] }};">
                                        <span>Límite {{ $money($creditor['credit_limit']) }}</span>
                                        <span>Usado {{ $money($creditor['used']) }}</span>
                                        <span class="fw-semibold">Disponible {{ $money($creditor['available']) }}</span>
                                    </div>
                                    @if ($creditor['payment_day'])
                                        <div class="text-muted small mt-1">Paga el día {{ $creditor['payment_day'] }} de cada mes</div>
                                    @endif
                                </div>
                            @endif
                        </button>
                        @if (($creditor['current_due'] ?? 0) > 0)
                            <form
                                method="POST"
                                action="{{ route('finance.credits.creditors.pay-month') }}"
                                class="mt-2"
                                onsubmit="return confirm('¿Pagar {{ $money($creditor['current_due']) }} de {{ $creditor['name'] }} de este mes? Se crearán los movimientos y se marcarán las mensualidades como pagadas.');"
                            >
                                @csrf
                                <input type="hidden" name="account_id" value="{{ $creditor['account_id'] ?? '' }}">
                                <input type="hidden" name="creditor_name" value="{{ $creditor['name'] }}">
                                <button type="submit" class="btn btn-sm w-100" style="background: {{ $style['color'] }}; color: {{ $style['badge_text'] ?? '#111827' }};">
                                    <i data-lucide="check-check" class="me-1"></i>Pagar el mes ({{ $money($creditor['current_due']) }})
                                </button>
                            </form>
                        @endif
                        @if (! empty($creditor['pending_installments']))
                            <button type="button" class="btn btn-sm btn-outline-light w-100 mt-2"
                                data-bs-toggle="modal" data-bs-target="#pay-select-{{ $creditor['key'] }}">
                                <i data-lucide="list-checks" class="me-1"></i>Seleccionar y pagar
                            </button>
                        @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @foreach ($creditorSummaries as $creditor)
        @if (! empty($creditor['pending_installments']))
            <div class="modal fade" id="pay-select-{{ $creditor['key'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('finance.credits.installments.pay-selected') }}"
                              data-pay-select-form
                              onsubmit="return confirm('¿Pagar las mensualidades seleccionadas? Se crearán los movimientos y se marcarán como pagadas.');">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Pagar selección · {{ $creditor['name'] }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-2">Marca las mensualidades que quieras pagar; el total se suma abajo. Cada una se marca pagada y crea su movimiento (no se parten mensualidades).</p>
                                <div class="input-group input-group-sm mb-3">
                                    <span class="input-group-text">Auto hasta $</span>
                                    <input type="number" step="0.01" min="0" class="form-control" data-pay-select-target placeholder="Ej. 1500">
                                    <button type="button" class="btn btn-outline-primary" data-pay-select-auto>Auto-seleccionar</button>
                                </div>
                                <p class="text-muted small mb-2">Precarga las mensualidades por vencimiento más próximo sin pasarse del monto; luego puedes ajustarlas a mano.</p>
                                <div class="d-flex flex-column gap-1">
                                    @foreach ($creditor['pending_installments'] as $inst)
                                        <label class="d-flex align-items-center justify-content-between gap-2 border rounded p-2 mb-0">
                                            <span class="d-flex align-items-center gap-2">
                                                <input type="checkbox" class="form-check-input mt-0" name="installment_ids[]" value="{{ $inst['id'] }}" data-amount="{{ $inst['amount'] }}" data-pay-select-check>
                                                <span>
                                                    <span class="fw-semibold">{{ $inst['credit_name'] }}</span>
                                                    <span class="text-muted small d-block">#{{ $inst['installment_number'] }}/{{ $inst['months'] }} · {{ $inst['period_label'] }}@if ($inst['due_date']) · vence {{ $inst['due_date'] }}@endif</span>
                                                </span>
                                            </span>
                                            <span class="fw-semibold">{{ $money($inst['amount']) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <div>Seleccionado: <span class="fw-semibold" data-pay-select-total>$0.00</span> <span class="text-muted small">(<span data-pay-select-count>0</span> mensualidad(es))</span></div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-sm btn-primary" data-pay-select-submit disabled>Pagar seleccionadas</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nuevo crédito a meses</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.credits.store') }}" class="needs-validation" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Compra</label>
                    <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Concepto / que fue</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Amazon, mueble, PASE..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capturar por</label>
                    <select name="amount_mode" class="form-select">
                        <option value="total" @selected(old('amount_mode', 'total') === 'total')>Total</option>
                        <option value="monthly" @selected(old('amount_mode') === 'monthly')>Pago mensual</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total crédito</label>
                    <input type="number" name="total_amount" class="form-control" step="0.01" min="0.01" value="{{ old('total_amount') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pago mensual</label>
                    <input type="number" name="monthly_amount" class="form-control" step="0.01" min="0.01" value="{{ old('monthly_amount') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Meses</label>
                    <input type="number" name="months" class="form-control" min="1" max="60" value="{{ old('months', 1) }}" required>
                </div>
                <div class="col-md-2 js-cycle-field">
                    <label class="form-label">Primer mes</label>
                    <input type="month" name="first_due_month" class="form-control" value="{{ old('first_due_month', now()->format('Y-m')) }}">
                </div>
                <div class="col-md-2 js-cycle-field">
                    <label class="form-label">Día pago</label>
                    <input type="number" name="due_day" class="form-control" min="1" max="31" value="{{ old('due_day') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cuenta</label>
                    <select name="account_id" class="form-select" data-credit-account>
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                @if ($account->hasCreditCycle()) data-has-cycle="1" data-statement-day="{{ (int) $account->statement_day }}" data-payment-day="{{ (int) $account->payment_day }}" @endif
                                @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 js-cycle-note d-none">
                    <div class="alert alert-info py-2 px-3 mb-0 small">
                        <i data-lucide="info" class="me-1"></i><span class="js-cycle-note-text"></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notas / identificación</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Tarjeta, folio, porque fue, quien lo debe pagar...">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus" class="me-1"></i>Crear
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($credits->isNotEmpty())
    <div class="card" id="credits-list-anchor">
        <div class="card-body d-flex flex-column gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small me-1"><i data-lucide="filter" class="me-1"></i>Estado:</span>
                <button type="button" class="btn btn-sm btn-outline-success" data-credit-status="owed">Debo</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-credit-status="paid">Pagados</button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-credit-status="current-month">Este mes</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-credit-status="all">Todos</button>
                <span class="ms-auto small text-muted" id="credits-filter-status">Mostrando {{ $credits->count() }} de {{ $credits->count() }} créditos</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small me-1"><i data-lucide="wallet" class="me-1"></i>Acreedor:</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-credit-creditor="all">Todos</button>
                @foreach ($creditorSummaries as $creditor)
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-credit-creditor="{{ $creditor['key'] }}"
                    >
                        {{ $creditor['name'] }}
                        <span class="badge text-bg-secondary ms-1">{{ $creditor['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@endif

@forelse ($credits as $credit)
    @php
        $totals = $creditTotals[$credit->id] ?? [
            'total_original' => (float) $credit->total_amount,
            'installment_paid' => round($credit->installments->sum(fn ($installment) => (float) $installment->paid_amount), 2),
            'free_paid' => round($credit->freePayments->sum(fn ($payment) => (float) $payment->amount_applied), 2),
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
    @endphp
    <div class="card finance-credit-card" id="credit-{{ $credit->id }}" style="border-left: 4px solid {{ $creditorStyle['color'] }};" data-creditor-key="{{ $creditorKey }}" data-current-due="{{ $creditCurrentDue }}" data-balance="{{ $creditPending }}">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4 class="card-title mb-0">{{ $credit->name }}</h4>
                    <span class="badge" style="background: {{ $creditorStyle['soft'] }}; color: {{ $creditorStyle['text'] }}; border: 1px solid {{ $creditorStyle['color'] }};">
                        Se debe a {{ $creditorName }}
                    </span>
                </div>
                <p class="text-muted mb-0">
                    Total original {{ $money($credit->total_amount) }} - {{ $credit->months }} meses - {{ \App\Support\FinanceLabels::creditStatus($credit->status) }}
                    @if ($credit->notes)
                        <span class="ms-1">| {{ $credit->notes }}</span>
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="badge badge-soft-success">Pagado total {{ $money($creditPaid) }}</span>
                <span class="badge badge-soft-primary">Mensualidades {{ $money($creditInstallmentPaid) }}</span>
                <span class="badge badge-soft-info">Abonos libres {{ $money($creditFreePaid) }}</span>
                <span class="badge badge-soft-warning">Saldo real {{ $money($creditPending) }}</span>
                <a href="#free-payments-{{ $credit->id }}" class="btn btn-sm btn-outline-primary">Ver abonos</a>
                <form method="POST" action="{{ route('finance.credits.destroy', $credit) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar crédito completo">
                        <i data-lucide="trash-2"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form id="{{ $creditFormId }}" method="POST" action="{{ route('finance.credits.update', $credit) }}" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-2">
                    <label class="form-label">Compra</label>
                    <input type="date" name="purchase_date" class="form-control form-control-sm" value="{{ $credit->purchase_date->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $credit->name }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capturar por</label>
                    <select name="amount_mode" class="form-select form-select-sm">
                        <option value="total">Total</option>
                        <option value="monthly">Pago mensual</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total crédito</label>
                    <input type="number" name="total_amount" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ $credit->total_amount }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pago mensual</label>
                    <input type="number" name="monthly_amount" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ $monthlyAmount }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Meses</label>
                    <input type="number" name="months" class="form-control form-control-sm" min="1" max="60" value="{{ $credit->months }}" required>
                </div>
                <div class="col-md-2 js-cycle-field">
                    <label class="form-label">Primer mes</label>
                    <input type="month" name="first_due_month" class="form-control form-control-sm" value="{{ $credit->first_due_month->format('Y-m') }}">
                </div>
                <div class="col-md-1 js-cycle-field">
                    <label class="form-label">Día</label>
                    <input type="number" name="due_day" class="form-control form-control-sm" min="1" max="31" value="{{ $credit->due_day }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cuenta</label>
                    <select name="account_id" class="form-select form-select-sm" data-credit-account>
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                @if ($account->hasCreditCycle()) data-has-cycle="1" data-statement-day="{{ (int) $account->statement_day }}" data-payment-day="{{ (int) $account->payment_day }}" @endif
                                @selected($credit->account_id === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 js-cycle-note d-none">
                    <div class="alert alert-info py-2 px-3 mb-0 small">
                        <i data-lucide="info" class="me-1"></i><span class="js-cycle-note-text"></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($credit->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="{{ $credit->notes }}">
                </div>
                <div class="col-md-1 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-success" title="Guardar crédito completo">
                        <i data-lucide="save"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body border-bottom" id="free-payments-{{ $credit->id }}">
            <div class="row g-3">
                <div class="col-lg-5">
                    <h5 class="mb-3">Registrar abono libre</h5>
                    <form method="POST" action="{{ route('finance.credits.free-payments.store', $credit) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="paid_on" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto</label>
                            <input type="number" name="amount" class="form-control form-control-sm text-end" step="0.01" min="0.01" max="{{ max(0.01, $creditPending) }}" placeholder="220.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cuenta</label>
                            <select name="account_id" class="form-select form-select-sm">
                                <option value="">Cuenta del crédito</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected($credit->account_id === $account->id)>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Categoría del crédito</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($credit->category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notas</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Pago suelto, anticipo, transferencia...">
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <small class="text-muted">No marca mensualidades como pagadas; solo reduce el saldo real del crédito.</small>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i data-lucide="plus" class="me-1"></i>Registrar abono libre
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Ver abonos libres</h5>
                        <span class="badge badge-soft-info">{{ $money($creditFreePaid) }}</span>
                    </div>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Movimiento</th>
                                    <th class="text-end">Monto</th>
                                    <th>Notas</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($credit->freePayments->sortByDesc('paid_on') as $payment)
                                    <tr>
                                        <td>{{ $payment->paid_on->format('Y-m-d') }}</td>
                                        <td>
                                            @if ($payment->movement)
                                                {{ $payment->movement->description }}
                                                <div class="text-muted small">{{ $payment->movement->account?->name ?? $credit->account?->name ?? 'Sin cuenta' }}</div>
                                            @else
                                                <span class="badge badge-soft-warning">Sin movimiento ligado</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-danger">{{ $money($payment->amount_applied) }}</td>
                                        <td>{{ $payment->notes ?? '-' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('finance.credits.free-payments.destroy', $payment) }}" onsubmit="return confirm('¿Eliminar este abono libre? Podrás deshacerlo durante 2 minutos.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar abono">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Sin abonos libres</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-md-none finance-mobile-list">
                        @forelse ($credit->freePayments->sortByDesc('paid_on') as $payment)
                            <div class="finance-mobile-row d-flex justify-content-between align-items-start gap-2 py-2 border-bottom">
                                <div style="min-width: 0;">
                                    <div class="fw-semibold">
                                        {{ $payment->paid_on->format('Y-m-d') }}
                                        <span class="text-danger ms-1">{{ $money($payment->amount_applied) }}</span>
                                    </div>
                                    <div class="text-muted small">
                                        @if ($payment->movement)
                                            {{ $payment->movement->description }} · {{ $payment->movement->account?->name ?? $credit->account?->name ?? 'Sin cuenta' }}
                                        @else
                                            <span class="badge badge-soft-warning">Sin movimiento ligado</span>
                                        @endif
                                        @if ($payment->notes) · {{ $payment->notes }} @endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('finance.credits.free-payments.destroy', $payment) }}" onsubmit="return confirm('¿Eliminar este abono libre? Podrás deshacerlo durante 2 minutos.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar abono">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-center text-muted py-3 mb-0">Sin abonos libres</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Mes</th>
                            <th>Vence</th>
                            <th class="text-end">Monto</th>
                            <th>Estado</th>
                            <th>Pagado</th>
                            <th>Notas</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($credit->installments as $installment)
                            @php
                                $installmentFormId = 'installment-form-' . $installment->id;
                            @endphp
                            <tr>
                                <td>{{ $installment->installment_number }}</td>
                                <td style="min-width: 130px;">
                                    <form id="{{ $installmentFormId }}" method="POST" action="{{ route('finance.credits.installments.update', $installment) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <input form="{{ $installmentFormId }}" type="month" name="period_month" class="form-control form-control-sm" value="{{ $installment->period_month->format('Y-m') }}" required>
                                </td>
                                <td style="min-width: 150px;">
                                    <input form="{{ $installmentFormId }}" type="date" name="due_date" class="form-control form-control-sm" value="{{ $installment->due_date?->format('Y-m-d') }}">
                                </td>
                                <td style="min-width: 130px;">
                                    <input form="{{ $installmentFormId }}" type="number" name="amount" class="form-control form-control-sm text-end" step="0.01" min="0.01" value="{{ $installment->amount }}" required>
                                </td>
                                <td style="min-width: 130px;">
                                    <select form="{{ $installmentFormId }}" name="status" class="form-select form-select-sm">
                                        <option value="pending" @selected($installment->status !== 'paid')>Pendiente</option>
                                        <option value="paid" @selected($installment->status === 'paid')>Pagado</option>
                                    </select>
                                </td>
                                <td style="min-width: 150px;">
                                    <input form="{{ $installmentFormId }}" type="date" name="paid_on" class="form-control form-control-sm" value="{{ $installment->paid_on?->format('Y-m-d') }}">
                                </td>
                                <td style="min-width: 220px;">
                                    <input form="{{ $installmentFormId }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $installment->notes }}">
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button form="{{ $installmentFormId }}" type="submit" class="btn btn-sm btn-success" title="Guardar mensualidad">
                                            <i data-lucide="save"></i>
                                        </button>
                                        @if ($installment->status !== 'paid')
                                            <form method="POST" action="{{ route('finance.credits.installments.paid', $installment) }}">
                                                @csrf
                                                <input type="hidden" name="paid_on" value="{{ $installment->due_date?->format('Y-m-d') ?? now()->toDateString() }}">
                                                <button type="submit" class="btn btn-sm btn-primary" title="Pagado y crear movimiento">
                                                    <i data-lucide="check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('finance.credits.installments.destroy', $installment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar mensualidad">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vista móvil: una tarjeta por mensualidad (form propio por tarjeta). --}}
            <div class="d-md-none finance-mobile-list">
                @foreach ($credit->installments as $installment)
                    @php($installmentFormId = 'installment-form-m-' . $installment->id)
                    <div class="finance-mobile-row px-3 py-3 border-bottom">
                        <form id="{{ $installmentFormId }}" method="POST" action="{{ route('finance.credits.installments.update', $installment) }}">
                            @csrf
                            @method('PUT')
                        </form>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Mensualidad {{ $installment->installment_number }}</span>
                            <span class="badge {{ $installment->status === 'paid' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $installment->status === 'paid' ? 'Pagado' : 'Pendiente' }}
                            </span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">Mes</label>
                                <input form="{{ $installmentFormId }}" type="month" name="period_month" class="form-control form-control-sm" value="{{ $installment->period_month->format('Y-m') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Vence</label>
                                <input form="{{ $installmentFormId }}" type="date" name="due_date" class="form-control form-control-sm" value="{{ $installment->due_date?->format('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Monto</label>
                                <input form="{{ $installmentFormId }}" type="number" name="amount" class="form-control form-control-sm text-end" step="0.01" min="0.01" value="{{ $installment->amount }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Estado</label>
                                <select form="{{ $installmentFormId }}" name="status" class="form-select form-select-sm">
                                    <option value="pending" @selected($installment->status !== 'paid')>Pendiente</option>
                                    <option value="paid" @selected($installment->status === 'paid')>Pagado</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Pagado el</label>
                                <input form="{{ $installmentFormId }}" type="date" name="paid_on" class="form-control form-control-sm" value="{{ $installment->paid_on?->format('Y-m-d') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Notas</label>
                                <input form="{{ $installmentFormId }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $installment->notes }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button form="{{ $installmentFormId }}" type="submit" class="btn btn-sm btn-success flex-grow-1">
                                <i data-lucide="save" class="me-1"></i>Guardar
                            </button>
                            @if ($installment->status !== 'paid')
                                <form method="POST" action="{{ route('finance.credits.installments.paid', $installment) }}">
                                    @csrf
                                    <input type="hidden" name="paid_on" value="{{ $installment->due_date?->format('Y-m-d') ?? now()->toDateString() }}">
                                    <button type="submit" class="btn btn-sm btn-primary" title="Pagado y crear movimiento">
                                        <i data-lucide="check"></i>
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('finance.credits.installments.destroy', $installment) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar mensualidad">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-4">Sin créditos</div>
    </div>
@endforelse
@endsection

@section('scripts')
<script>
    (function () {
        var cards = Array.prototype.slice.call(document.querySelectorAll('.finance-credit-card'));
        if (cards.length === 0) {
            return;
        }

        var status = document.getElementById('credits-filter-status');
        var anchor = document.getElementById('credits-list-anchor');
        var statusButtons = Array.prototype.slice.call(document.querySelectorAll('[data-credit-status]'));
        // Incluye los botones de la barra Y los tiles de resumen de arriba.
        var creditorButtons = Array.prototype.slice.call(document.querySelectorAll('[data-credit-creditor]'));

        // Dos filtros independientes que se COMBINAN (Y): estado + acreedor.
        var activeStatus = 'owed';   // por defecto: lo que debo
        var activeCreditor = 'all';  // por defecto: todos los acreedores

        function matchesStatus(card) {
            var balance = parseFloat(card.getAttribute('data-balance') || '0');

            if (activeStatus === 'owed') {
                // Medio centavo de margen para no colar residuales por redondeo.
                return balance > 0.005;
            }
            if (activeStatus === 'paid') {
                return balance <= 0.005;
            }
            if (activeStatus === 'current-month') {
                return parseFloat(card.getAttribute('data-current-due') || '0') > 0;
            }

            return true; // 'all'
        }

        function matchesCreditor(card) {
            return activeCreditor === 'all'
                || card.getAttribute('data-creditor-key') === activeCreditor;
        }

        function setActive(buttons, attr, value) {
            buttons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute(attr) === value);
            });
        }

        function apply() {
            var shown = 0;

            cards.forEach(function (card) {
                var show = matchesStatus(card) && matchesCreditor(card);
                card.style.display = show ? '' : 'none';
                if (show) {
                    shown++;
                }
            });

            if (status) {
                status.textContent = 'Mostrando ' + shown + ' de ' + cards.length + ' créditos';
            }

            setActive(statusButtons, 'data-credit-status', activeStatus);
            setActive(creditorButtons, 'data-credit-creditor', activeCreditor);
        }

        statusButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeStatus = btn.getAttribute('data-credit-status');
                apply();
            });
        });

        creditorButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeCreditor = btn.getAttribute('data-credit-creditor');
                apply();

                // Los tiles de resumen están arriba; al elegir un acreedor
                // llevamos la vista a la lista filtrada.
                if (anchor) {
                    anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Al abrir: solo los que debo, de todos los acreedores. Sin scroll inicial.
        apply();
    })();

    // Pago por selección: suma en vivo del total de las mensualidades marcadas.
    (function () {
        var forms = Array.prototype.slice.call(document.querySelectorAll('[data-pay-select-form]'));

        forms.forEach(function (form) {
            var totalEl = form.querySelector('[data-pay-select-total]');
            var countEl = form.querySelector('[data-pay-select-count]');
            var submitEl = form.querySelector('[data-pay-select-submit]');

            function money(value) {
                return '$' + value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalc() {
                var total = 0;
                var count = 0;
                Array.prototype.slice.call(form.querySelectorAll('[data-pay-select-check]:checked')).forEach(function (cb) {
                    total += parseFloat(cb.getAttribute('data-amount')) || 0;
                    count++;
                });
                if (totalEl) { totalEl.textContent = money(total); }
                if (countEl) { countEl.textContent = count; }
                if (submitEl) { submitEl.disabled = count === 0; }
            }

            form.addEventListener('change', function (e) {
                if (e.target && e.target.matches('[data-pay-select-check]')) {
                    recalc();
                }
            });

            // Auto-seleccionar hasta un monto: marca las mensualidades por orden
            // (vencimiento más próximo) mientras quepan sin pasarse del presupuesto.
            var autoBtn = form.querySelector('[data-pay-select-auto]');
            var targetEl = form.querySelector('[data-pay-select-target]');
            var checks = Array.prototype.slice.call(form.querySelectorAll('[data-pay-select-check]'));

            function autoSelect() {
                var budget = parseFloat(targetEl && targetEl.value) || 0;
                if (budget <= 0) {
                    return;
                }
                var running = 0;
                checks.forEach(function (cb) {
                    var amount = parseFloat(cb.getAttribute('data-amount')) || 0;
                    if (running + amount <= budget + 0.005) {
                        cb.checked = true;
                        running += amount;
                    } else {
                        cb.checked = false;
                    }
                });
                recalc();
            }

            if (autoBtn) {
                autoBtn.addEventListener('click', autoSelect);
            }
            if (targetEl) {
                targetEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        autoSelect();
                    }
                });
            }

            recalc();
        });
    })();

    // Cuando la tarjeta seleccionada ya tiene su ciclo (corte/pago) en Cuentas,
    // el sistema calcula sola la fecha de pago: ocultamos "Primer mes" y "Día
    // pago" y mostramos cuándo se pagará. Si la cuenta no tiene ciclo, se siguen
    // capturando a mano. Misma regla que Account::firstDueDateFor en el servidor.
    (function () {
        var MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        function computeDue(purchaseStr, statementDay, paymentDay) {
            var parts = String(purchaseStr).split('-');
            if (parts.length < 3) {
                return null;
            }
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1; // 0-based
            var day = parseInt(parts[2], 10);

            var closeTotal = year * 12 + month;
            if (day > statementDay) {
                closeTotal += 1;
            }
            var dueTotal = closeTotal;
            if (paymentDay <= statementDay) {
                dueTotal += 1;
            }

            var dueYear = Math.floor(dueTotal / 12);
            var dueMonth = dueTotal % 12;
            var daysInMonth = new Date(dueYear, dueMonth + 1, 0).getDate();
            var dueDay = Math.min(paymentDay, daysInMonth);

            return { year: dueYear, month: dueMonth, day: dueDay };
        }

        function update(form) {
            var select = form.querySelector('[data-credit-account]');
            if (!select) {
                return;
            }
            var option = select.options[select.selectedIndex];
            var hasCycle = option && option.getAttribute('data-has-cycle') === '1';
            var fields = form.querySelectorAll('.js-cycle-field');
            var note = form.querySelector('.js-cycle-note');
            var noteText = form.querySelector('.js-cycle-note-text');

            for (var i = 0; i < fields.length; i++) {
                fields[i].classList.toggle('d-none', !!hasCycle);
            }

            if (!hasCycle) {
                if (note) { note.classList.add('d-none'); }
                return;
            }

            var statementDay = parseInt(option.getAttribute('data-statement-day'), 10);
            var paymentDay = parseInt(option.getAttribute('data-payment-day'), 10);
            var cardName = option.textContent.trim();
            var cycleText = ' (corte día ' + statementDay + ', pago día ' + paymentDay + ')';

            var purchase = form.querySelector('input[name="purchase_date"]');
            var text;
            var due = purchase && purchase.value ? computeDue(purchase.value, statementDay, paymentDay) : null;

            if (due) {
                text = 'Con el ciclo de ' + cardName + cycleText + ', este crédito se pagará el '
                    + due.day + ' de ' + MONTHS[due.month] + ' de ' + due.year
                    + '. No necesitas capturar primer mes ni día de pago.';
            } else {
                text = 'La fecha de pago se calcula con el ciclo de ' + cardName + cycleText
                    + '. No necesitas capturar primer mes ni día de pago.';
            }

            if (noteText) { noteText.textContent = text; }
            if (note) { note.classList.remove('d-none'); }
        }

        var selects = document.querySelectorAll('[data-credit-account]');
        Array.prototype.forEach.call(selects, function (select) {
            var form = select.closest('form');
            if (!form) {
                return;
            }
            select.addEventListener('change', function () { update(form); });
            var purchase = form.querySelector('input[name="purchase_date"]');
            if (purchase) {
                purchase.addEventListener('change', function () { update(form); });
            }
            update(form);
        });
    })();
</script>
@endsection
