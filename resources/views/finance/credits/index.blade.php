@extends('layouts.vertical', ['title' => 'Créditos'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $creditorSummaries = collect($creditorSummaries ?? []);
    $summaryWithoutOnix = $summaryWithoutOnix ?? [];
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-12">
        <h4 class="mb-0 fw-semibold">Créditos manuales</h4>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Deuda total en créditos</p>
                <h4 class="fw-semibold text-primary mb-0">{{ $money($summary['total'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Ya pagado</p>
                <h4 class="fw-semibold text-success mb-0">{{ $money($summary['paid'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Pendiente total</p>
                <h4 class="fw-semibold text-warning mb-0">{{ $money($summary['pending'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
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
                        <button
                            type="button"
                            class="w-100 text-start border-0 rounded-2 p-3 h-100"
                            style="background: {{ $style['soft'] }}; border-left: 4px solid {{ $style['color'] }} !important; cursor: pointer;"
                            title="Filtrar la lista por {{ $creditor['name'] }}"
                            data-credit-filter="creditor"
                            data-creditor-key="{{ $creditor['key'] }}"
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
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
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
                <div class="col-md-2">
                    <label class="form-label">Primer mes</label>
                    <input type="month" name="first_due_month" class="form-control" value="{{ old('first_due_month', now()->format('Y-m')) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Día pago</label>
                    <input type="number" name="due_day" class="form-control" min="1" max="31" value="{{ old('due_day') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cuenta</label>
                    <select name="account_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
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
        <div class="card-body d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small me-1"><i data-lucide="filter" class="me-1"></i>Filtrar lista de créditos:</span>
            <button type="button" class="btn btn-sm btn-primary" data-credit-filter="all">Todos</button>
            <button type="button" class="btn btn-sm btn-outline-warning" data-credit-filter="current-month">Este mes</button>
            @foreach ($creditorSummaries as $creditor)
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    data-credit-filter="creditor"
                    data-creditor-key="{{ $creditor['key'] }}"
                >
                    {{ $creditor['name'] }}
                    <span class="badge text-bg-secondary ms-1">{{ $creditor['count'] }}</span>
                </button>
            @endforeach
            <span class="ms-auto small text-muted" id="credits-filter-status">Mostrando {{ $credits->count() }} de {{ $credits->count() }} créditos</span>
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
    <div class="card finance-credit-card" id="credit-{{ $credit->id }}" style="border-left: 4px solid {{ $creditorStyle['color'] }};" data-creditor-key="{{ $creditorKey }}" data-current-due="{{ $creditCurrentDue }}">
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
                <div class="col-md-2">
                    <label class="form-label">Primer mes</label>
                    <input type="month" name="first_due_month" class="form-control form-control-sm" value="{{ $credit->first_due_month->format('Y-m') }}" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Día</label>
                    <input type="number" name="due_day" class="form-control form-control-sm" min="1" max="31" value="{{ $credit->due_day }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cuenta</label>
                    <select name="account_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected($credit->account_id === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
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
                    <div class="table-responsive">
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
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
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
        var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-credit-filter]'));

        function setActive(filter, key) {
            buttons.forEach(function (btn) {
                var matches = btn.getAttribute('data-credit-filter') === filter
                    && (filter !== 'creditor' || btn.getAttribute('data-creditor-key') === key);
                btn.classList.toggle('active', matches);
            });
        }

        function applyFilter(filter, key) {
            var shown = 0;

            cards.forEach(function (card) {
                var show = true;

                if (filter === 'creditor') {
                    show = card.getAttribute('data-creditor-key') === key;
                } else if (filter === 'current-month') {
                    show = parseFloat(card.getAttribute('data-current-due') || '0') > 0;
                }

                card.style.display = show ? '' : 'none';
                if (show) {
                    shown++;
                }
            });

            if (status) {
                status.textContent = 'Mostrando ' + shown + ' de ' + cards.length + ' créditos';
            }

            setActive(filter, key);
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var filter = btn.getAttribute('data-credit-filter');
                var key = btn.getAttribute('data-creditor-key');

                applyFilter(filter, key);

                if (filter !== 'all' && anchor) {
                    anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    })();
</script>
@endsection
