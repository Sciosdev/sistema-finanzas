@extends('layouts.vertical', ['title' => 'Ingresos Esperados'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $defaultIncomeAccount = $accounts->firstWhere('name', 'NU') ?? $accounts->first();
    $nextMonthValue = \Carbon\Carbon::createFromFormat('Y-m', $monthValue)->addMonth()->format('Y-m');
    $editIncomeId = $editIncomeId ?? (int) request('edit');
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
                <p class="text-muted mb-1">Ingresos esperados seran estos</p>
                <h4 class="fw-semibold text-primary mb-0">{{ $money($incomeTotals['expected'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Por cobrar todavia</p>
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
                        <th>Pronto cobro</th>
                        <th>Estado</th>
                        <th>Movimiento</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incomeRows as $income)
                        @php
                            $overdue = $income['status'] === 'pending'
                                && $income['due_date']
                                && $income['due_date']->copy()->startOfDay()->lt(today()->startOfDay());
                            $remaining = max(0, (float) $income['amount'] - (float) $income['received_amount']);
                            $isLinked = ! empty($income['movement_id']);
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
                            <td>
                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($income['due_date'], $displayStatus) }}">
                                    {{ \App\Support\FinanceLabels::dueLabel($income['due_date'], $displayStatus) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $income['status'] === 'received' ? 'badge-soft-success' : ($overdue || $income['status'] === 'skipped' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $income['status'] === 'received' ? 'Recibido' : ($income['status'] === 'skipped' ? 'No recibido' : ($overdue ? 'Vencido' : 'Pendiente')) }}
                                </span>
                                @if ($isLinked)
                                    <span class="badge badge-soft-primary ms-1">Ligado</span>
                                @endif
                            </td>
                            <td>
                                @if ($movement)
                                    <div>{{ $movement->happened_on->format('Y-m-d') }} · {{ $money($movement->amount) }}</div>
                                    <div class="text-muted small">{{ $movement->description }}</div>
                                @elseif ($isLinked)
                                    <span class="badge badge-soft-danger">Movimiento faltante</span>
                                @else
                                    <span class="text-muted">Sin ligar</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (in_array($income['status'], ['pending', 'overdue'], true))
                                    <div class="d-flex flex-column flex-xxl-row align-items-end gap-2">
                                        @if ($income['kind'] === 'expected')
                                            <a href="{{ route('finance.expected-incomes.index', ['month' => $monthValue, 'edit' => $income['id']]) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ $income['kind'] === 'rental-contract' ? route('finance.san-juan.rentals.received', $income['id']) : route('finance.expected-incomes.received', $income['id']) }}">
                                            @csrf
                                            @if ($income['kind'] === 'rental-contract')
                                                <input type="hidden" name="month" value="{{ $monthValue }}">
                                            @endif
                                            <select name="account_id" class="form-select form-select-sm mb-1" title="Cuenta destino">
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}" @selected(($income['account_id'] ?? $defaultIncomeAccount?->id) === $account->id)>{{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" name="amount" class="form-control form-control-sm text-end mb-1" step="0.01" min="0.01" value="{{ $remaining }}" title="Monto recibido">
                                            <input type="date" name="received_on" class="form-control form-control-sm mb-1" value="{{ $income['due_date']?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de cobro">
                                            <button type="submit" class="btn btn-sm btn-success" title="Recibido y crear movimiento">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        @if ($income['kind'] === 'expected')
                                            <a href="{{ route('finance.expected-incomes.link', $income['id']) }}" class="btn btn-sm btn-outline-success" title="Vincular con movimiento">
                                                <i data-lucide="link"></i>
                                            </a>
                                            <form method="POST" action="{{ route('finance.expected-incomes.registered', $income['id']) }}">
                                                @csrf
                                                <input type="date" name="received_on" class="form-control form-control-sm mb-1" value="{{ $income['due_date']?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de cobro">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Ya lo capture como ingreso">
                                                    <i data-lucide="link"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('finance.expected-incomes.skip', $income['id']) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="No recibido">
                                                    <i data-lucide="x"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('finance.expected-incomes.destroy', $income['id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('finance.san-juan.index', ['month' => $monthValue]) }}" class="btn btn-sm btn-outline-primary" title="Editar contrato">
                                                <i data-lucide="home"></i>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    @if ($income['kind'] === 'expected')
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <a href="{{ route('finance.expected-incomes.index', ['month' => $monthValue, 'edit' => $income['id']]) }}" class="btn btn-sm btn-link text-primary p-0" title="Editar">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                            @if ($isLinked)
                                                <form method="POST" action="{{ route('finance.expected-incomes.unlink-movement', $income['id']) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-link text-warning p-0" title="Desligar movimiento">
                                                        <i data-lucide="unlink"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('finance.expected-incomes.link', $income['id']) }}" class="btn btn-sm btn-link text-success p-0" title="Vincular con movimiento">
                                                    <i data-lucide="link"></i>
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('finance.expected-incomes.destroy', $income['id']) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @if ($income['kind'] === 'expected' && $editIncomeId === $income['id'])
                            <tr>
                                <td colspan="10" class="bg-light-subtle">
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
                            <td colspan="10" class="text-center text-muted py-4">Sin ingresos esperados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
