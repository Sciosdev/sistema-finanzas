@extends('layouts.vertical', ['title' => 'Créditos'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
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
</div>

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

@forelse ($credits as $credit)
    @php
        $creditPaid = round($credit->installments->sum(fn ($installment) => (float) $installment->paid_amount), 2);
        $creditPending = round($credit->installments->sum(fn ($installment) => max(0, (float) $installment->amount - (float) $installment->paid_amount)), 2);
        $firstInstallment = $credit->installments->first();
        $monthlyAmount = $firstInstallment ? (float) $firstInstallment->amount : 0;
        $creditFormId = 'credit-form-' . $credit->id;
    @endphp
    <div class="card">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h4 class="card-title mb-1">{{ $credit->name }}</h4>
                <p class="text-muted mb-0">
                    {{ $money($credit->total_amount) }} - {{ $credit->months }} meses - {{ \App\Support\FinanceLabels::creditStatus($credit->status) }}
                    @if ($credit->notes)
                        <span class="ms-1">| {{ $credit->notes }}</span>
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-soft-success">Pagado {{ $money($creditPaid) }}</span>
                <span class="badge badge-soft-warning">Pendiente {{ $money($creditPending) }}</span>
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
