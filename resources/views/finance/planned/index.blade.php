@extends('layouts.vertical', ['title' => 'Flujo Planeado'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $nextMonthValue = \Carbon\Carbon::createFromFormat('Y-m', $monthValue)->addMonth()->format('Y-m');
    $editPaymentId = (int) request('edit');
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Flujo planeado</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.planned.index') }}" class="d-flex justify-content-md-end gap-2">
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="calendar-search" class="me-1"></i>Ver
            </button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total a pagar este mes</p>
                <h4 class="fw-semibold text-primary mb-1">{{ $money($paymentTotals['total'] ?? 0) }}</h4>
                <small class="text-muted">
                    Pagos: {{ $money($paymentTotals['planned'] ?? 0) }} | Créditos: {{ $money($paymentTotals['credits'] ?? 0) }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Ya pagado</p>
                <h4 class="fw-semibold text-success mb-0">{{ $money($paymentTotals['paid'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Pendiente por pagar</p>
                <h4 class="fw-semibold text-warning mb-1">{{ $money($paymentTotals['pending'] ?? 0) }}</h4>
                <small class="text-muted">Vencido: {{ $money($paymentTotals['overdue'] ?? 0) }}</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">No pagado</p>
                <h4 class="fw-semibold text-danger mb-0">{{ $money($paymentTotals['skipped'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Copiar flujo como plantilla</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.planned.copy') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Mes origen</label>
                <input type="month" name="source_month" class="form-control" value="{{ $monthValue }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Copiar a</label>
                <input type="month" name="target_month" class="form-control" value="{{ $nextMonthValue }}" required>
            </div>
            <div class="col-md-6 d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary">
                    <i data-lucide="copy" class="me-1"></i>Copiar mes
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nuevo pago planeado</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.planned.store') }}" class="needs-validation" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Mes</label>
                    <input type="month" name="period_month" class="form-control" value="{{ old('period_month', $monthValue) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vence</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pago</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Monto</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cuenta</label>
                    <select name="account_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Persona</label>
                    <select name="person_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus" class="me-1"></i>Agregar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Pagos del mes</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Vence</th>
                        <th>Pago</th>
                        <th>Categoría</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Pagado</th>
                        <th>Pronto pago</th>
                        <th>Estado</th>
                        <th>Origen</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php
                            $overdue = in_array($payment->status, ['pending', 'overdue'], true)
                                && (
                                    $payment->status === 'overdue'
                                    || ($payment->due_date && $payment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
                                );
                            $displayStatus = $overdue ? 'overdue' : $payment->status;
                            $originLabel = match (true) {
                                $payment->status === 'skipped' => 'No pagado',
                                $payment->status === 'paid' && (bool) $payment->movement_id => 'Pagado/vinculado',
                                $payment->status === 'paid' => 'Pagado/registrado',
                                $overdue => 'Vencido pendiente',
                                default => 'Pago planeado',
                            };
                            $originClass = match (true) {
                                $payment->status === 'paid' => 'badge-soft-success',
                                $payment->status === 'skipped' || $overdue => 'badge-soft-danger',
                                default => 'badge-soft-primary',
                            };
                        @endphp
                        <tr>
                            <td>{{ $payment->due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                {{ $payment->name }}
                                @if ($payment->is_san_juan)
                                    <span class="badge badge-soft-danger ms-1">SNJ</span>
                                @endif
                            </td>
                            <td>{{ $payment->category?->name ?? '-' }}</td>
                            <td class="text-end">{{ $money($payment->amount) }}</td>
                            <td class="text-end">{{ $money($payment->paid_amount) }}</td>
                            <td>
                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($payment->due_date, $displayStatus) }}">
                                    {{ \App\Support\FinanceLabels::dueLabel($payment->due_date, $displayStatus) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $payment->status === 'paid' ? 'badge-soft-success' : ($overdue || $payment->status === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $payment->status === 'paid' ? 'Pagado' : ($payment->status === 'skipped' ? 'No pagado' : ($overdue ? 'Vencido' : 'Pendiente')) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $originClass }}">{{ $originLabel }}</span>
                            </td>
                            <td class="text-end">
                                @if (in_array($payment->status, ['pending', 'overdue'], true))
                                    <div class="d-flex flex-column flex-xxl-row align-items-end gap-2">
                                        <a href="{{ route('finance.planned.index', ['month' => $monthValue, 'edit' => $payment->id]) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i data-lucide="pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('finance.planned.paid', $payment) }}">
                                            @csrf
                                            <input type="date" name="paid_on" class="form-control form-control-sm mb-1" value="{{ $payment->due_date?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de pago">
                                            <button type="submit" class="btn btn-sm btn-success" title="Pagado">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('finance.planned.link', $payment) }}" class="btn btn-sm btn-outline-success" title="Vincular con movimiento">
                                            <i data-lucide="link"></i>
                                        </a>
                                        <form method="POST" action="{{ route('finance.planned.skip', $payment) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="No pagado">
                                                <i data-lucide="x"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('finance.planned.destroy', $payment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Quitar del flujo">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <a href="{{ route('finance.planned.index', ['month' => $monthValue, 'edit' => $payment->id]) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i data-lucide="pencil"></i>
                                        </a>
                                        @if ($payment->status === 'paid' && ! $payment->movement_id)
                                            <a href="{{ route('finance.planned.link', $payment) }}" class="btn btn-sm btn-outline-success" title="Vincular con movimiento">
                                                <i data-lucide="link"></i>
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('finance.planned.destroy', $payment) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Quitar del flujo">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @if ($editPaymentId === $payment->id)
                            <tr>
                                <td colspan="9" class="bg-light-subtle">
                                    <form method="POST" action="{{ route('finance.planned.update', $payment) }}" class="p-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-2">
                                                <label class="form-label">Vence</label>
                                                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date', $payment->due_date?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Pago</label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $payment->name) }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Monto</label>
                                                <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ old('amount', $payment->amount) }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Cuenta</label>
                                                <select name="account_id" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected((string) old('account_id', $payment->account_id) === (string) $account->id)>{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Categoría</label>
                                                <select name="category_id" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" @selected((string) old('category_id', $payment->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Persona</label>
                                                <select name="person_id" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach ($people as $person)
                                                        <option value="{{ $person->id }}" @selected((string) old('person_id', $payment->person_id) === (string) $person->id)>{{ $person->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Notas</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $payment->notes) }}">
                                            </div>
                                            <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                                                <a href="{{ route('finance.planned.index', ['month' => $monthValue]) }}" class="btn btn-sm btn-outline-secondary">Cancelar</a>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i data-lucide="save" class="me-1"></i>Guardar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Sin pagos planeados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0">Mensualidades de créditos</h4>
        <a href="{{ route('finance.credits.index') }}" class="btn btn-sm btn-outline-primary">
            <i data-lucide="credit-card" class="me-1"></i>Créditos
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Vence</th>
                        <th>Crédito</th>
                        <th>Mensualidad</th>
                        <th>Categoría</th>
                        <th class="text-end">Monto</th>
                        <th>Pronto pago</th>
                        <th>Estado</th>
                        <th>Origen</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($creditInstallments as $installment)
                        @php
                            $credit = $installment->creditPurchase;
                            $overdue = in_array($installment->status, ['pending', 'overdue'], true)
                                && (
                                    $installment->status === 'overdue'
                                    || ($installment->due_date && $installment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
                                );
                            $displayStatus = $overdue ? 'overdue' : $installment->status;
                            $originLabel = match (true) {
                                $installment->status === 'skipped' => 'No pagado',
                                $installment->status === 'paid' && (bool) $installment->movement_id => 'Pagado/vinculado',
                                $installment->status === 'paid' => 'Pagado/registrado',
                                $overdue => 'Crédito vencido',
                                default => 'Crédito',
                            };
                            $originClass = match (true) {
                                $installment->status === 'paid' => 'badge-soft-success',
                                $installment->status === 'skipped' || $overdue => 'badge-soft-danger',
                                default => 'badge-soft-primary',
                            };
                        @endphp
                        <tr>
                            <td>{{ $installment->due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $credit?->name ?? '-' }}</td>
                            <td>{{ $installment->installment_number }} / {{ $credit?->months ?? '-' }}</td>
                            <td>{{ $credit?->category?->name ?? '-' }}</td>
                            <td class="text-end">{{ $money($installment->amount) }}</td>
                            <td>
                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($installment->due_date, $displayStatus) }}">
                                    {{ \App\Support\FinanceLabels::dueLabel($installment->due_date, $displayStatus) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $installment->status === 'paid' ? 'badge-soft-success' : ($overdue ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $installment->status === 'paid' ? 'Pagado' : ($overdue ? 'Vencido' : 'Pendiente') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $originClass }}">{{ $originLabel }}</span>
                            </td>
                            <td class="text-end">
                                @if (in_array($installment->status, ['pending', 'overdue'], true))
                                    <div class="d-flex flex-column flex-xxl-row align-items-end gap-2">
                                        <form method="POST" action="{{ route('finance.credits.installments.paid', $installment) }}">
                                            @csrf
                                            <input type="date" name="paid_on" class="form-control form-control-sm mb-1" value="{{ $installment->due_date?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de pago">
                                            <button type="submit" class="btn btn-sm btn-success" title="Pagado">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('finance.credits.installments.registered', $installment) }}">
                                            @csrf
                                            <input type="date" name="paid_on" class="form-control form-control-sm mb-1" value="{{ $installment->due_date?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de pago">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Ya lo capture como gasto">
                                                <i data-lucide="link"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Sin mensualidades de créditos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
