@extends('layouts.vertical', ['title' => 'Ingresos Esperados'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $defaultIncomeAccount = $accounts->firstWhere('name', 'NU') ?? $accounts->first();
    $nextMonthValue = \Carbon\Carbon::createFromFormat('Y-m', $monthValue)->addMonth()->format('Y-m');
    $editIncomeId = $editIncomeId ?? (int) request('edit');
    $incomeMovements = $incomeMovements ?? collect();
    $movementCandidatesForIncome = function (array $income) use ($incomeMovements) {
        return $incomeMovements
            ->sortBy(function ($movement) use ($income) {
                $amountDistance = abs((float) $movement->amount - (float) $income['amount']);
                $dateDistance = $income['due_date']
                    ? abs($movement->happened_on->diffInDays($income['due_date'], false))
                    : 0;

                return str_pad((string) round($amountDistance * 100), 12, '0', STR_PAD_LEFT)
                    . str_pad((string) $dateDistance, 6, '0', STR_PAD_LEFT)
                    . $movement->happened_on->format('Ymd');
            })
            ->take(8)
            ->values();
    };
    $isMatchingIncomeAmount = fn ($movement, array $income) => abs((float) $movement->amount - (float) $income['amount']) < 0.01;
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Ingresos esperados</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.expected-incomes.index') }}" class="d-flex justify-content-md-end gap-2">
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="calendar-search" class="me-1"></i>Ver
            </button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Ingresos esperados serán estos</p>
                <h4 class="fw-semibold text-primary mb-0">{{ $money($incomeTotals['expected'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Por cobrar todavía</p>
                <h4 class="fw-semibold text-warning mb-0">{{ $money($incomeTotals['pending'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Ya recibido</p>
                <h4 class="fw-semibold text-success mb-0">{{ $money($incomeTotals['received'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Copiar ingresos como plantilla</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.expected-incomes.copy') }}" class="row g-3 align-items-end">
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
        <h4 class="card-title mb-0">Nuevo ingreso esperado</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.expected-incomes.store') }}" class="needs-validation" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Mes</label>
                    <input type="month" name="period_month" class="form-control" value="{{ old('period_month', $monthValue) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha esperada</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ingreso</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Andrea comida, FESI, SCIOS..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Monto</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cuenta destino</label>
                    <select name="account_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Persona</label>
                    <select name="person_id" class="form-select">
                        <option value="">-</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}" @selected((string) old('person_id') === (string) $person->id)>{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nueva persona</label>
                    <input type="text" name="new_person_name" class="form-control" value="{{ old('new_person_name') }}" placeholder="ITTLA, cliente, escuela...">
                </div>
                <div class="col-md-11">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" name="is_rent" id="is_rent" @checked(old('is_rent'))>
                        <label class="form-check-label" for="is_rent">Renta</label>
                    </div>
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
        <h4 class="card-title mb-0">Ingresos del mes</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Ingreso</th>
                        <th>Categoría</th>
                        <th>Persona</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Recibido</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-end">Abonos</th>
                        <th>Pronto cobro</th>
                        <th>Estado</th>
                        <th>Movimiento</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incomeRows as $income)
                        @php
                            $overdue = in_array($income['status'], ['pending', 'partial', 'overdue'], true)
                                && $income['due_date']
                                && $income['due_date']->copy()->startOfDay()->lt(today()->startOfDay())
                                && (float) ($income['amount_due'] ?? 0) > 0;
                            $remaining = (float) ($income['amount_due'] ?? max(0, (float) $income['amount'] - (float) $income['received_amount']));
                            $paymentCount = (int) ($income['payment_count'] ?? 0);
                            $payments = $income['payments'] ?? collect();
                            $isLinked = $paymentCount > 0;
                            $movement = $income['movement'] ?? null;
                            $displayStatus = $income['status'] === 'received' ? 'paid' : ($overdue ? 'overdue' : $income['status']);
                        @endphp
                        <tr>
                            <td>{{ $income['due_date']?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                {{ $income['name'] }}
                                @if ($income['is_rent'])
                                    <span class="badge badge-soft-success ms-1">Renta</span>
                                @endif
                                @if ($income['kind'] === 'rental-contract')
                                    <span class="badge badge-soft-primary ms-1">Contrato</span>
                                @endif
                            </td>
                            <td>{{ $income['category'] }}</td>
                            <td>{{ $income['person'] }}</td>
                            <td class="text-end">{{ $money($income['amount']) }}</td>
                            <td class="text-end">{{ $money($income['received_amount']) }}</td>
                            <td class="text-end {{ $remaining > 0 ? 'text-warning' : 'text-success' }}">{{ $money($remaining) }}</td>
                            <td class="text-end">{{ $paymentCount }}</td>
                            <td>
                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($income['due_date'], $displayStatus) }}">
                                    {{ \App\Support\FinanceLabels::dueLabel($income['due_date'], $displayStatus) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $income['status'] === 'received' ? 'badge-soft-success' : ($overdue || $income['status'] === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $income['status'] === 'received' ? 'Recibido' : ($income['status'] === 'skipped' ? 'No recibido' : ($income['status'] === 'partial' ? ($overdue ? 'Parcial vencido' : 'Parcial') : ($overdue ? 'Vencido' : 'Pendiente'))) }}
                                </span>
                                @if ($isLinked)
                                    <span class="badge badge-soft-primary ms-1">Ligado</span>
                                    <span class="badge badge-soft-info ms-1">{{ $paymentCount }} abono(s)</span>
                                @endif
                            </td>
                            <td>
                                @if ($payments->isNotEmpty())
                                    @foreach ($payments->take(2) as $payment)
                                        <div>
                                            {{ $payment->paid_on?->format('Y-m-d') ?? '-' }} · {{ $money($payment->amount_applied) }}
                                            @if ($payment->movement)
                                                <span class="text-muted small">{{ $payment->movement->description }}</span>
                                            @else
                                                <span class="badge badge-soft-warning">Sin movimiento ligado</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if ($payments->count() > 2)
                                        <div class="text-muted small">+{{ $payments->count() - 2 }} abono(s) más</div>
                                    @endif
                                @elseif ($movement)
                                    <div>{{ $movement->happened_on->format('Y-m-d') }} · {{ $money($movement->amount) }}</div>
                                    <div class="text-muted small">{{ $movement->description }}</div>
                                @elseif ($isLinked)
                                    <span class="badge badge-soft-danger">Movimiento faltante</span>
                                @else
                                    <span class="text-muted">Sin ligar</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" style="min-width: 118px" data-bs-toggle="modal" data-bs-target="#expected-income-actions-{{ $income['kind'] }}-{{ $income['id'] }}">
                                    <i data-lucide="list-checks" class="me-1"></i>Acciones
                                </button>
                            </td>
                        </tr>
                        @if ($income['kind'] === 'expected' && $editIncomeId === $income['id'])
                            <tr>
                                <td colspan="12" class="bg-light-subtle">
                                    <form method="POST" action="{{ route('finance.expected-incomes.update', $income['id']) }}" class="p-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-2">
                                                <label class="form-label">Mes</label>
                                                <input type="month" name="period_month" class="form-control form-control-sm" value="{{ old('period_month', $income['period_month']?->format('Y-m') ?? $monthValue) }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Fecha esperada</label>
                                                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date', $income['due_date']?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Ingreso</label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $income['name']) }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Monto</label>
                                                <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ old('amount', $income['amount']) }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Cuenta</label>
                                                <select name="account_id" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected((string) old('account_id', $income['account_id']) === (string) $account->id)>{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Categoría</label>
                                                <select name="category_id" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" @selected((string) old('category_id', $income['category_id']) === (string) $category->id)>{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Persona</label>
                                                <select name="person_id" class="form-select form-select-sm">
                                                    <option value="">-</option>
                                                    @foreach ($people as $person)
                                                        <option value="{{ $person->id }}" @selected((string) old('person_id', $income['person_id']) === (string) $person->id)>{{ $person->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Nueva persona</label>
                                                <input type="text" name="new_person_name" class="form-control form-control-sm" value="{{ old('new_person_name') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label d-block">Marca</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" name="is_rent" id="income-rent-{{ $income['id'] }}" @checked(old('is_rent', $income['is_rent']))>
                                                    <label class="form-check-label" for="income-rent-{{ $income['id'] }}">Renta</label>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <label class="form-label">Notas</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $income['notes']) }}">
                                            </div>
                                            <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                                                <a href="{{ route('finance.expected-incomes.index', ['month' => $monthValue]) }}" class="btn btn-sm btn-outline-secondary">Cancelar</a>
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
                            <td colspan="12" class="text-center text-muted py-4">Sin ingresos esperados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach ($incomeRows as $income)
    @php
        $remaining = (float) ($income['amount_due'] ?? max(0, (float) $income['amount'] - (float) $income['received_amount']));
        $paymentCount = (int) ($income['payment_count'] ?? 0);
        $payments = $income['payments'] ?? collect();
        $isLinked = $paymentCount > 0;
        $overdue = in_array($income['status'], ['pending', 'partial', 'overdue'], true)
            && $income['due_date']
            && $income['due_date']->copy()->startOfDay()->lt(today()->startOfDay())
            && $remaining > 0;
        $statusLabel = $income['status'] === 'received'
            ? 'Recibido'
            : ($income['status'] === 'skipped' ? 'No recibido' : ($income['status'] === 'partial' ? ($overdue ? 'Parcial vencido' : 'Parcial') : ($overdue ? 'Vencido' : 'Pendiente')));
        $originLabel = $income['kind'] === 'rental-contract'
            ? 'Contrato San Juan'
            : ($isLinked ? 'Ingreso con abonos' : 'Ingreso esperado');
        $defaultReceivedOn = $income['due_date']?->format('Y-m-d') ?? now()->toDateString();
        $defaultAccountId = $income['account_id'] ?? $defaultIncomeAccount?->id;
        $canReceive = in_array($income['status'], ['pending', 'partial', 'overdue', 'skipped'], true) && $remaining > 0;
        $canSkip = $income['kind'] === 'expected' && in_array($income['status'], ['pending', 'partial', 'overdue'], true);
        $incomeCandidates = $income['kind'] === 'expected' ? $movementCandidatesForIncome($income) : collect();
    @endphp
    <div class="modal fade" id="expected-income-actions-{{ $income['kind'] }}-{{ $income['id'] }}" tabindex="-1" aria-labelledby="expected-income-actions-{{ $income['kind'] }}-{{ $income['id'] }}-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="expected-income-actions-{{ $income['kind'] }}-{{ $income['id'] }}-label">Acciones de ingreso</h5>
                        <div class="text-muted small">{{ $income['name'] }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="border rounded p-3 mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Monto esperado</span>
                                <span class="fw-semibold">{{ $money($income['amount']) }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Recibido</span>
                                <span class="fw-semibold text-success">{{ $money($income['received_amount']) }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Saldo</span>
                                <span class="fw-semibold {{ $remaining > 0 ? 'text-warning' : 'text-success' }}">{{ $money($remaining) }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Fecha esperada</span>
                                <span>{{ $income['due_date']?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Estado</span>
                                <span class="badge {{ $income['status'] === 'received' ? 'badge-soft-success' : ($overdue || $income['status'] === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Origen</span>
                                <span>{{ $originLabel }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Cuenta</span>
                                <span>{{ $accounts->firstWhere('id', $income['account_id'])?->name ?? '-' }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Categoria</span>
                                <span>{{ $income['category'] }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Persona</span>
                                <span>{{ $income['person'] }}</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Abonos</span>
                                <span>{{ $paymentCount }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($payments->isNotEmpty())
                        <div class="border rounded p-3 mb-3">
                            <h6 class="mb-3">Abonos registrados</h6>
                            <div class="list-group">
                                @foreach ($payments as $payment)
                                    <div class="list-group-item">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold">{{ $payment->paid_on?->format('Y-m-d') ?? '-' }} - {{ $money($payment->amount_applied) }}</div>
                                                <div class="text-muted small">{{ $payment->movement?->description ?? 'Marcado como registrado sin movimiento ligado' }}</div>
                                            </div>
                                            @if ($payment->movement)
                                                <span class="badge badge-soft-primary align-self-md-start">Ligado</span>
                                            @else
                                                <span class="badge badge-soft-warning align-self-md-start">Sin movimiento</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($income['kind'] === 'rental-contract')
                        @if ($canReceive)
                            <form method="POST" action="{{ route('finance.san-juan.rentals.received', $income['id']) }}" class="border rounded p-3 mb-3">
                                @csrf
                                <input type="hidden" name="month" value="{{ $monthValue }}">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Cuenta destino</label>
                                        <select name="account_id" class="form-select">
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}" @selected($defaultAccountId === $account->id)>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Monto recibido</label>
                                        <input type="number" name="amount" class="form-control text-end" step="0.01" min="0.01" value="{{ $remaining }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha real de cobro</label>
                                        <input type="date" name="received_on" class="form-control" value="{{ $defaultReceivedOn }}">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i data-lucide="check" class="me-1"></i>Registrar renta recibida
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                        <a href="{{ route('finance.san-juan.index', ['month' => $monthValue]) }}" class="btn btn-outline-primary w-100">
                            <i data-lucide="home" class="me-1"></i>Administrar contrato
                        </a>
                    @else
                        @if ($canReceive)
                            <div class="row g-3 mb-3">
                                <div class="col-lg-6">
                                    <form method="POST" action="{{ route('finance.expected-incomes.received', $income['id']) }}" class="border rounded p-3 h-100">
                                        @csrf
                                        <label class="form-label">Cuenta destino</label>
                                        <select name="account_id" class="form-select mb-2">
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}" @selected($defaultAccountId === $account->id)>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        <label class="form-label">Monto recibido</label>
                                        <input type="number" name="amount" class="form-control text-end mb-2" step="0.01" min="0.01" value="{{ $remaining }}">
                                        <label class="form-label">Fecha real de cobro</label>
                                        <input type="date" name="received_on" class="form-control mb-3" value="{{ $defaultReceivedOn }}">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i data-lucide="check" class="me-1"></i>Marcar como recibido
                                        </button>
                                    </form>
                                </div>
                                <div class="col-lg-6">
                                    <form method="POST" action="{{ route('finance.expected-incomes.registered', $income['id']) }}" class="border rounded p-3 h-100">
                                        @csrf
                                        <label class="form-label">Fecha ya capturada</label>
                                        <input type="date" name="received_on" class="form-control mb-3" value="{{ $defaultReceivedOn }}">
                                        <button type="submit" class="btn btn-outline-success w-100">
                                            <i data-lucide="link" class="me-1"></i>Ya lo capture como ingreso
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if (! $isLinked || $remaining > 0)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                    <div>
                                        <h6 class="mb-1">Vincular movimiento existente</h6>
                                        <div class="text-muted small">Usa la fecha ya capturada en el movimiento seleccionado.</div>
                                    </div>
                                    <a href="{{ route('finance.expected-incomes.link', $income['id']) }}" class="btn btn-sm btn-outline-secondary align-self-md-start">
                                        Pantalla completa
                                    </a>
                                </div>
                                <div class="list-group">
                                    @forelse ($incomeCandidates as $movement)
                                        @php
                                            $alreadyLinked = $payments->contains('movement_id', $movement->id);
                                            $defaultApplied = min((float) $movement->amount, $remaining > 0 ? $remaining : (float) $movement->amount);
                                        @endphp
                                        <div class="list-group-item">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                                <div>
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                        <span class="fw-semibold">{{ $movement->happened_on?->format('Y-m-d') ?? '-' }}</span>
                                                        @if ($isMatchingIncomeAmount($movement, $income))
                                                            <span class="badge badge-soft-success">Monto coincide</span>
                                                        @endif
                                                        @if ($movement->is_rent)
                                                            <span class="badge badge-soft-success">Renta</span>
                                                        @endif
                                                        @if ($alreadyLinked)
                                                            <span class="badge badge-soft-primary">Ya ligado</span>
                                                        @endif
                                                    </div>
                                                    <div>{{ $movement->description }}</div>
                                                    <div class="text-muted small">
                                                        {{ $movement->account?->name ?? 'Sin cuenta' }} | {{ $movement->category?->name ?? 'Sin categoria' }} | {{ $movement->person?->name ?? 'Sin persona' }}
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column align-items-lg-end gap-2">
                                                    <span class="fw-semibold text-success">{{ $money($movement->amount) }}</span>
                                                    <form method="POST" action="{{ route('finance.expected-incomes.link-movement', $income['id']) }}" class="d-flex flex-column gap-2">
                                                        @csrf
                                                        <input type="hidden" name="movement_id" value="{{ $movement->id }}">
                                                        <input type="number" name="amount_applied" class="form-control form-control-sm text-end" step="0.01" min="0.01" max="{{ $movement->amount }}" value="{{ $defaultApplied }}" @disabled($alreadyLinked)>
                                                        <button type="submit" class="btn btn-sm btn-outline-success w-100" @disabled($alreadyLinked)>
                                                            <i data-lucide="link" class="me-1"></i>Aplicar abono
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted small">No hay movimientos reales de ingreso en este mes.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        <div class="row g-2">
                            @if ($isLinked)
                                <div class="col-md-4">
                                    <form method="POST" action="{{ route('finance.expected-incomes.unlink-movement', $income['id']) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning w-100">
                                            <i data-lucide="unlink" class="me-1"></i>Desligar abonos
                                        </button>
                                    </form>
                                </div>
                            @endif
                            @if ($canSkip)
                                <div class="col-md-4">
                                    <form method="POST" action="{{ route('finance.expected-incomes.skip', $income['id']) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger w-100">
                                            <i data-lucide="x" class="me-1"></i>Marcar como no recibido
                                        </button>
                                    </form>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <a href="{{ route('finance.expected-incomes.index', ['month' => $monthValue, 'edit' => $income['id']]) }}" class="btn btn-outline-primary w-100">
                                    <i data-lucide="pencil" class="me-1"></i>Editar ingreso
                                </a>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" action="{{ route('finance.expected-incomes.destroy', $income['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i data-lucide="trash-2" class="me-1"></i>Eliminar ingreso
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
