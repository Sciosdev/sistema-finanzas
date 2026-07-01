@extends('layouts.vertical', ['title' => 'Flujo Planeado'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $nextMonthValue = \Carbon\Carbon::createFromFormat('Y-m', $monthValue)->addMonth()->format('Y-m');
    $editPaymentId = (int) request('edit');
    $expenseMovements = $expenseMovements ?? collect();
    $movementCandidatesForPayment = function ($payment) use ($expenseMovements) {
        return $expenseMovements
            ->sortBy(function ($movement) use ($payment) {
                $amountDistance = abs((float) $movement->amount - (float) $payment->amount);
                $dateDistance = $payment->due_date
                    ? abs($movement->happened_on->diffInDays($payment->due_date, false))
                    : 0;

                return str_pad((string) round($amountDistance * 100), 12, '0', STR_PAD_LEFT)
                    . str_pad((string) $dateDistance, 6, '0', STR_PAD_LEFT)
                    . $movement->happened_on->format('Ymd');
            })
            ->take(8)
            ->values();
    };
    $isMatchingAmount = fn ($movement, $payment) => abs((float) $movement->amount - (float) $payment->amount) < 0.01;
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
                <p class="text-muted mb-1">Obligaciones no pagadas / pendientes de decisión</p>
                <h4 class="fw-semibold text-danger mb-0">{{ $money($paymentTotals['skipped'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
</div>

@foreach ($payments as $payment)
    @php
        $isCreditPaid = $payment->status === 'paid' && (bool) $payment->is_credit;
        $linkedCredit = $payment->creditPurchase;
        $overdue = in_array($payment->status, ['pending', 'overdue'], true)
            && (
                $payment->status === 'overdue'
                || ($payment->due_date && $payment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
            );
        $displayStatus = $overdue ? 'overdue' : $payment->status;
        $statusLabel = $payment->status === 'paid'
            ? 'Pagado'
            : ($payment->status === 'skipped' ? 'No pagado / pendiente de decision' : ($overdue ? 'Vencido' : 'Pendiente'));
        $originLabel = match (true) {
            $isCreditPaid && $linkedCredit => 'Pagado con credito: ' . $linkedCredit->name,
            $isCreditPaid => 'Pagado con credito',
            $payment->status === 'skipped' => 'No pagado / pendiente de decision',
            $payment->status === 'paid' && (bool) $payment->movement_id => 'Pagado/vinculado',
            $payment->status === 'paid' => 'Pagado/registrado',
            $overdue => 'Vencido pendiente',
            default => 'Pago planeado',
        };
        $paymentCandidates = $movementCandidatesForPayment($payment);
        $defaultPaidOn = $payment->paid_on?->format('Y-m-d')
            ?? $payment->due_date?->format('Y-m-d')
            ?? now()->toDateString();
        $canActAsUnpaid = in_array($payment->status, ['pending', 'overdue', 'skipped'], true);
        $canLinkMovement = $payment->status !== 'paid' || (! $payment->movement_id && ! $payment->is_credit);
        $canUseCreditPayment = $payment->status !== 'paid' || (! $payment->movement_id && ! $payment->is_credit);
    @endphp
    <div class="modal fade" id="planned-payment-actions-{{ $payment->id }}" tabindex="-1" aria-labelledby="planned-payment-actions-{{ $payment->id }}-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="planned-payment-actions-{{ $payment->id }}-label">Acciones de pago</h5>
                        <div class="text-muted small">{{ $payment->name }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Monto</span>
                                <span class="fw-semibold">{{ $money($payment->amount) }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Vencimiento</span>
                                <span class="fw-semibold">{{ $payment->due_date?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Estado</span>
                                <span class="badge {{ $payment->status === 'paid' ? 'badge-soft-success' : ($overdue || $payment->status === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Cuenta</span>
                                <span>{{ $payment->account?->name ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Categoria</span>
                                <span>{{ $payment->category?->name ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Origen</span>
                                <span>{{ $originLabel }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($canActAsUnpaid || $canUseCreditPayment)
                        <div class="row g-3 mb-3">
                            @if ($canActAsUnpaid)
                                <div class="col-lg-6">
                                    <form method="POST" action="{{ route('finance.planned.paid', $payment) }}" class="border rounded p-3 h-100">
                                        @csrf
                                        <label class="form-label">Fecha real de pago</label>
                                        <input type="date" name="paid_on" class="form-control mb-3" value="{{ $defaultPaidOn }}">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i data-lucide="check" class="me-1"></i>Marcar como pagado
                                        </button>
                                    </form>
                                </div>
                            @endif
                            @if ($canUseCreditPayment)
                                <div class="col-lg-6">
                                    <form method="POST" action="{{ route('finance.planned.credit-paid', $payment) }}" class="border rounded p-3 h-100">
                                        @csrf
                                        <label class="form-label">Fecha de compra con tarjeta/credito</label>
                                        <input type="date" name="paid_on" class="form-control mb-2" value="{{ $defaultPaidOn }}">
                                        <select name="account_id" class="form-select mb-2">
                                            <option value="">Tarjeta o cuenta de credito</option>
                                            @foreach ($creditAccounts as $account)
                                                <option value="{{ $account->id }}" @selected($payment->account_id === $account->id)>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="credit_purchase_id" class="form-select mb-3">
                                            <option value="">Credito existente</option>
                                            @foreach ($creditPurchases as $creditPurchase)
                                                <option value="{{ $creditPurchase->id }}">{{ $creditPurchase->name }}{{ $creditPurchase->account ? ' - ' . $creditPurchase->account->name : '' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline-warning w-100">
                                            <i data-lucide="credit-card" class="me-1"></i>Pagar con tarjeta/credito
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        @if ($canUseCreditPayment)
                            <div class="border rounded p-3 mb-3">
                                <h6 class="mb-1">Pagar y crear crédito automáticamente</h6>
                                <div class="text-muted small mb-3">
                                    Crea el crédito con este nombre y monto, genera sus mensualidades y deja el pago cubierto. No tienes que crear el crédito antes.
                                </div>
                                <form method="POST" action="{{ route('finance.planned.credit-new', $payment) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-md-3">
                                        <label class="form-label">Fecha de compra</label>
                                        <input type="date" name="paid_on" class="form-control" value="{{ $defaultPaidOn }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tarjeta / crédito</label>
                                        <select name="account_id" class="form-select">
                                            <option value="">Cuenta del pago</option>
                                            @foreach ($creditAccounts as $account)
                                                <option value="{{ $account->id }}" @selected($payment->account_id === $account->id)>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Meses</label>
                                        <input type="number" name="months" class="form-control" min="1" max="60" value="1">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i data-lucide="credit-card" class="me-1"></i>Pagar y crear crédito
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endif

                    @if ($canLinkMovement)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1">Vincular movimiento existente</h6>
                                    <div class="text-muted small">Usa la fecha ya capturada en el movimiento seleccionado.</div>
                                </div>
                                <a href="{{ route('finance.planned.link', $payment) }}" class="btn btn-sm btn-outline-secondary align-self-md-start">
                                    Pantalla completa
                                </a>
                            </div>
                            <div class="list-group">
                                @forelse ($paymentCandidates as $movement)
                                    <div class="list-group-item">
                                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                            <div>
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                    <span class="fw-semibold">{{ $movement->happened_on?->format('Y-m-d') ?? '-' }}</span>
                                                    @if ($isMatchingAmount($movement, $payment))
                                                        <span class="badge badge-soft-success">Monto coincide</span>
                                                    @endif
                                                </div>
                                                <div>{{ $movement->description }}</div>
                                                <div class="text-muted small">
                                                    {{ $movement->account?->name ?? 'Sin cuenta' }} | {{ $movement->category?->name ?? 'Sin categoria' }}
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column align-items-lg-end gap-2">
                                                <span class="fw-semibold">{{ $money($movement->amount) }}</span>
                                                <form method="POST" action="{{ route('finance.planned.link-movement', $payment) }}">
                                                    @csrf
                                                    <input type="hidden" name="movement_id" value="{{ $movement->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                                        <i data-lucide="link" class="me-1"></i>Vincular este movimiento
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted small">No hay movimientos reales de gasto en este mes.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    <div class="row g-2">
                        @if (in_array($payment->status, ['pending', 'overdue'], true))
                            <div class="col-md-4">
                                <form method="POST" action="{{ route('finance.planned.skip', $payment) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i data-lucide="x" class="me-1"></i>Marcar como no pagado
                                    </button>
                                </form>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <a href="{{ route('finance.planned.index', ['month' => $monthValue, 'edit' => $payment->id]) }}" class="btn btn-outline-primary w-100">
                                <i data-lucide="pencil" class="me-1"></i>Editar pago
                            </a>
                        </div>
                        <div class="col-md-4">
                            <form method="POST" action="{{ route('finance.planned.destroy', $payment) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i data-lucide="trash-2" class="me-1"></i>Eliminar del flujo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

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
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="card-title mb-0">Pagos del mes</h4>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small"><i data-lucide="filter" class="me-1"></i>Estado:</span>
            <button type="button" class="btn btn-sm btn-outline-warning" data-planned-filter="pending">Pendiente</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-planned-filter="paid">Pagado</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-planned-filter="all">Todos</button>
        </div>
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
                            $isCreditPaid = $payment->status === 'paid' && (bool) $payment->is_credit;
                            $linkedCredit = $payment->creditPurchase;
                            $overdue = in_array($payment->status, ['pending', 'overdue'], true)
                                && (
                                    $payment->status === 'overdue'
                                    || ($payment->due_date && $payment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
                                );
                            $displayStatus = $overdue ? 'overdue' : $payment->status;
                            $originLabel = match (true) {
                                $isCreditPaid && $linkedCredit => 'Pagado con credito: ' . $linkedCredit->name,
                                $isCreditPaid => 'Pagado con credito',
                                $payment->status === 'skipped' => 'No pagado / pendiente de decisión',
                                $payment->status === 'paid' && (bool) $payment->movement_id => 'Pagado/vinculado',
                                $payment->status === 'paid' => 'Pagado/registrado',
                                $overdue => 'Vencido pendiente',
                                default => 'Pago planeado',
                            };
                            $originClass = match (true) {
                                $isCreditPaid => 'badge-soft-warning',
                                $payment->status === 'paid' => 'badge-soft-success',
                                $payment->status === 'skipped' || $overdue => 'badge-soft-danger',
                                default => 'badge-soft-primary',
                            };
                        @endphp
                        <tr data-planned-row data-paid="{{ $payment->status === 'paid' ? '1' : '0' }}">
                            <td>{{ $payment->due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                {{ $payment->name }}
                                @if ($payment->is_san_juan)
                                    <span class="badge badge-soft-danger ms-1">SNJ</span>
                                @endif
                                @if ($isCreditPaid)
                                    <span class="badge badge-soft-warning ms-1">Tarjeta</span>
                                    <div class="text-muted small">
                                        {{ $payment->account?->name ? 'Tarjeta: ' . $payment->account->name : 'Tarjeta sin cuenta asignada' }}
                                        @if ($linkedCredit)
                                            | Credito: {{ $linkedCredit->name }}
                                        @endif
                                    </div>
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
                                    {{ $payment->status === 'paid' ? 'Pagado' : ($payment->status === 'skipped' ? 'No pagado / pendiente de decisión' : ($overdue ? 'Vencido' : 'Pendiente')) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $originClass }}">{{ $originLabel }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" style="min-width: 118px" data-bs-toggle="modal" data-bs-target="#planned-payment-actions-{{ $payment->id }}">
                                    <i data-lucide="list-checks" class="me-1"></i>Acciones
                                </button>
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
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="card-title mb-0">Mensualidades de créditos</h4>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small"><i data-lucide="filter" class="me-1"></i>Estado:</span>
            <button type="button" class="btn btn-sm btn-outline-warning" data-planned-filter="pending">Pendiente</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-planned-filter="paid">Pagado</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-planned-filter="all">Todos</button>
            <a href="{{ route('finance.credits.index') }}" class="btn btn-sm btn-outline-primary">
                <i data-lucide="credit-card" class="me-1"></i>Créditos
            </a>
        </div>
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
                                $installment->status === 'skipped' => 'No pagado / pendiente de decisión',
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
                        <tr data-planned-row data-paid="{{ $installment->status === 'paid' ? '1' : '0' }}">
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
                                <span class="badge {{ $installment->status === 'paid' ? 'badge-soft-success' : ($overdue || $installment->status === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $installment->status === 'paid' ? 'Pagado' : ($installment->status === 'skipped' ? 'No pagado / pendiente de decisión' : ($overdue ? 'Vencido' : 'Pendiente')) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $originClass }}">{{ $originLabel }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" style="min-width: 118px" data-bs-toggle="modal" data-bs-target="#credit-installment-actions-{{ $installment->id }}">
                                    <i data-lucide="list-checks" class="me-1"></i>Acciones
                                </button>
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

@foreach ($creditInstallments as $installment)
    @php
        $credit = $installment->creditPurchase;
        $overdue = in_array($installment->status, ['pending', 'overdue'], true)
            && (
                $installment->status === 'overdue'
                || ($installment->due_date && $installment->due_date->copy()->startOfDay()->lt(today()->startOfDay()))
            );
        $statusLabel = $installment->status === 'paid'
            ? 'Pagado'
            : ($installment->status === 'skipped' ? 'No pagado / pendiente de decision' : ($overdue ? 'Vencido' : 'Pendiente'));
        $originLabel = match (true) {
            $installment->status === 'skipped' => 'No pagado / pendiente de decision',
            $installment->status === 'paid' && (bool) $installment->movement_id => 'Pagado/vinculado',
            $installment->status === 'paid' => 'Pagado/registrado',
            $overdue => 'Credito vencido',
            default => 'Credito',
        };
        $defaultPaidOn = $installment->paid_on?->format('Y-m-d')
            ?? $installment->due_date?->format('Y-m-d')
            ?? now()->toDateString();
    @endphp
    <div class="modal fade" id="credit-installment-actions-{{ $installment->id }}" tabindex="-1" aria-labelledby="credit-installment-actions-{{ $installment->id }}-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="credit-installment-actions-{{ $installment->id }}-label">Acciones de mensualidad</h5>
                        <div class="text-muted small">{{ $credit?->name ?? 'Credito' }} - {{ $installment->installment_number }} / {{ $credit?->months ?? '-' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Monto</span>
                                <span class="fw-semibold">{{ $money($installment->amount) }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Vencimiento</span>
                                <span class="fw-semibold">{{ $installment->due_date?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Estado</span>
                                <span class="badge {{ $installment->status === 'paid' ? 'badge-soft-success' : ($overdue || $installment->status === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Cuenta</span>
                                <span>{{ $credit?->account?->name ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Categoria</span>
                                <span>{{ $credit?->category?->name ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Origen</span>
                                <span>{{ $originLabel }}</span>
                            </div>
                        </div>
                    </div>

                    @if (in_array($installment->status, ['pending', 'overdue'], true))
                        <div class="row g-3 mb-3">
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('finance.credits.installments.paid', $installment) }}" class="border rounded p-3 h-100">
                                    @csrf
                                    <label class="form-label">Fecha real de pago</label>
                                    <input type="date" name="paid_on" class="form-control mb-3" value="{{ $defaultPaidOn }}">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i data-lucide="check" class="me-1"></i>Marcar como pagado
                                    </button>
                                </form>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('finance.credits.installments.registered', $installment) }}" class="border rounded p-3 h-100">
                                    @csrf
                                    <label class="form-label">Fecha ya capturada</label>
                                    <input type="date" name="paid_on" class="form-control mb-3" value="{{ $defaultPaidOn }}">
                                    <button type="submit" class="btn btn-outline-success w-100">
                                        <i data-lucide="link" class="me-1"></i>Ya lo capture como gasto
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success mb-3">
                            Esta mensualidad ya esta marcada como pagada.
                        </div>
                    @endif

                    <a href="{{ route('finance.credits.index') }}" class="btn btn-outline-primary w-100">
                        <i data-lucide="credit-card" class="me-1"></i>Administrar credito
                    </a>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('scripts')
<script>
    // Filtro por estado (Pendiente / Pagado / Todos) para las dos tablas de
    // Flujo planeado. Los botones viven en ambos encabezados y se sincronizan
    // porque compartimos la misma lógica sobre todas las filas [data-planned-row].
    (function () {
        var rows = Array.prototype.slice.call(document.querySelectorAll('[data-planned-row]'));
        var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-planned-filter]'));

        if (rows.length === 0 || buttons.length === 0) {
            return;
        }

        function applyFilter(filter) {
            rows.forEach(function (row) {
                var paid = row.getAttribute('data-paid') === '1';
                var show = filter === 'all' || (filter === 'paid' ? paid : !paid);
                row.style.display = show ? '' : 'none';
            });

            buttons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-planned-filter') === filter);
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyFilter(btn.getAttribute('data-planned-filter'));
            });
        });

        // Por defecto mostramos solo los pendientes (lo que falta por pagar este mes).
        applyFilter('pending');
    })();
</script>
@endsection
