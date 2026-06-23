@extends('layouts.vertical', ['title' => 'San Juan'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">San Juan</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.san-juan.index') }}" class="d-flex justify-content-md-end gap-2">
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="calendar-search" class="me-1"></i>Ver
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Rentas recibidas</p>
                <h4 class="fw-bold text-success mb-0">{{ $money($summary['rent_income']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Egresos San Juan</p>
                <h4 class="fw-bold text-danger mb-0">{{ $money($summary['san_juan_expenses']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Utilidad</p>
                <h4 class="fw-bold {{ $summary['san_juan_utility'] >= 0 ? 'text-success' : 'text-danger' }} mb-0">{{ $money($summary['san_juan_utility']) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Plantilla mensual de rentas</p>
                <h4 class="fw-bold text-primary mb-0">{{ $money($rentalTemplateTotals['monthly_expected'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Rentas activas</p>
                <h4 class="fw-bold text-success mb-0">{{ $rentalTemplateTotals['active_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Rentas inactivas</p>
                <h4 class="fw-bold text-muted mb-0">{{ $rentalTemplateTotals['inactive_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Detalle de rentas del mes</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Inquilino</th>
                                <th>Cuarto</th>
                                <th class="text-end">Esperado</th>
                                <th class="text-end">Recibido</th>
                                <th class="text-end">Pendiente</th>
                                <th>Relacionado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rentalDetailRows as $row)
                                <tr>
                                    <td>{{ $row['person'] }}</td>
                                    <td>{{ $row['room'] ?? '-' }}</td>
                                    <td class="text-end">{{ $money($row['expected']) }}</td>
                                    <td class="text-end text-success">{{ $money($row['received']) }}</td>
                                    <td class="text-end {{ $row['pending'] > 0 ? 'text-warning' : 'text-success' }}">{{ $money($row['pending']) }}</td>
                                    <td>
                                        @if ($row['related_movements']->isNotEmpty())
                                            <span class="badge badge-soft-success">{{ $row['related_movements']->count() }} movimiento(s)</span>
                                            <div class="text-muted small">
                                                {{ $row['related_movements']->take(2)->map(fn ($movement) => $movement->happened_on->format('Y-m-d') . ' ' . $money($movement->amount))->implode(' · ') }}
                                            </div>
                                        @else
                                            <span class="badge badge-soft-warning">Sin pago ligado por persona</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin rentas activas</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Egresos por concepto</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th class="text-end">Movs</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenseConceptRows as $row)
                                <tr>
                                    <td>{{ $row['concept'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end text-danger">{{ $money($row['amount']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Sin egresos San Juan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Movimientos anidados por relación</h4>
            </div>
            <div class="card-body">
                @forelse ($movementRelationGroups as $group)
                    <details class="border rounded p-3 mb-2" @if ($loop->first) open @endif>
                        <summary class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span class="fw-semibold">{{ $group['label'] }}</span>
                            <span class="d-flex flex-wrap gap-2">
                                <span class="badge badge-soft-secondary">{{ $group['count'] }} movs</span>
                                <span class="badge badge-soft-success">Ingresos {{ $money($group['income']) }}</span>
                                <span class="badge badge-soft-danger">Egresos {{ $money($group['expenses']) }}</span>
                                <span class="badge {{ $group['net'] >= 0 ? 'badge-soft-success' : 'badge-soft-danger' }}">Utilidad {{ $money($group['net']) }}</span>
                            </span>
                        </summary>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Movimiento</th>
                                        <th>Cuenta</th>
                                        <th>Categoría</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['movements'] as $movement)
                                        <tr>
                                            <td>{{ $movement->happened_on->format('Y-m-d') }}</td>
                                            <td>
                                                {{ $movement->description }}
                                                @if ($movement->notes)
                                                    <div class="text-muted small">{{ $movement->notes }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $movement->account?->name ?? '-' }}</td>
                                            <td>{{ $movement->category?->name ?? '-' }}</td>
                                            <td class="text-end {{ $movement->movement_type === 'expense' ? 'text-danger' : 'text-success' }}">{{ $money($movement->amount) }}</td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center gap-2">
                                                    <a href="{{ route('finance.movements.edit', ['movement' => $movement, 'month' => $monthValue]) }}" class="btn btn-sm btn-link text-primary p-0" title="Editar movimiento">
                                                        <i data-lucide="pencil"></i>
                                                    </a>
                                                    <form method="POST" action="{{ route('finance.movements.destroy', $movement) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar con deshacer">
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
                    </details>
                @empty
                    <p class="text-muted mb-0">Sin movimientos relacionados.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Resumen por persona</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                                <th class="text-end">Utilidad</th>
                                <th class="text-end">Movs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($personMovementRows as $row)
                                <tr>
                                    <td>{{ $row['person'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['income']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['expenses']) }}</td>
                                    <td class="text-end {{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($row['net']) }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sin movimientos por persona</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Nuevo contrato de renta</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.san-juan.rentals.store') }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Inquilino</label>
                            <input type="text" name="person_name" class="form-control" value="{{ old('person_name') }}" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Cuarto</label>
                            <input type="text" name="room" class="form-control" value="{{ old('room') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Renta</label>
                            <input type="number" name="expected_amount" class="form-control" step="0.01" min="0" value="{{ old('expected_amount') }}" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Dia</label>
                            <input type="number" name="due_day" class="form-control" min="1" max="31" value="{{ old('due_day') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Inicio</label>
                            <input type="date" name="starts_on" class="form-control" value="{{ old('starts_on') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fin</label>
                            <input type="date" name="ends_on" class="form-control" value="{{ old('ends_on') }}">
                        </div>
                        <div class="col-md-1 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="plus"></i>
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Plantilla de rentas</h4>
                <span class="badge badge-soft-primary">{{ $rentalContracts->count() }} contratos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Inquilino</th>
                                <th>Cuarto</th>
                                <th class="text-end">Renta</th>
                                <th>Dia cobro</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th>Notas</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rentalContracts as $contract)
                                @php
                                    $formId = 'rental-contract-' . $contract->id;
                                @endphp
                                <tr>
                                    <td style="min-width: 190px;">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('finance.san-juan.rentals.update', $contract) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_active" value="0">
                                        </form>
                                        <input form="{{ $formId }}" type="text" name="person_name" class="form-control form-control-sm" value="{{ old('person_name', $contract->person?->name) }}" required>
                                    </td>
                                    <td style="min-width: 90px;">
                                        <input form="{{ $formId }}" type="text" name="room" class="form-control form-control-sm" value="{{ old('room', $contract->room) }}">
                                    </td>
                                    <td style="min-width: 130px;">
                                        <input form="{{ $formId }}" type="number" name="expected_amount" class="form-control form-control-sm text-end" step="0.01" min="0" value="{{ old('expected_amount', $contract->expected_amount) }}" required>
                                    </td>
                                    <td style="min-width: 95px;">
                                        <input form="{{ $formId }}" type="number" name="due_day" class="form-control form-control-sm" min="1" max="31" value="{{ old('due_day', $contract->due_day) }}">
                                    </td>
                                    <td style="min-width: 150px;">
                                        <input form="{{ $formId }}" type="date" name="starts_on" class="form-control form-control-sm" value="{{ old('starts_on', $contract->starts_on?->format('Y-m-d')) }}">
                                    </td>
                                    <td style="min-width: 150px;">
                                        <input form="{{ $formId }}" type="date" name="ends_on" class="form-control form-control-sm" value="{{ old('ends_on', $contract->ends_on?->format('Y-m-d')) }}">
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input form="{{ $formId }}" class="form-check-input" type="checkbox" value="1" name="is_active" id="contract-active-{{ $contract->id }}" @checked($contract->is_active)>
                                            <label class="form-check-label" for="contract-active-{{ $contract->id }}">{{ $contract->is_active ? 'Activo' : 'Inactivo' }}</label>
                                        </div>
                                    </td>
                                    <td style="min-width: 220px;">
                                        <input form="{{ $formId }}" type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $contract->notes) }}">
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <button form="{{ $formId }}" type="submit" class="btn btn-sm btn-success" title="Guardar contrato">
                                                <i data-lucide="save"></i>
                                            </button>
                                            <form method="POST" action="{{ route('finance.san-juan.rentals.destroy', $contract) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar renta de la plantilla">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Sin contratos de renta</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Movimientos San Juan</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Persona</th>
                                <th>Tipo</th>
                                <th>Cuenta</th>
                                <th>Categoría</th>
                                <th>Relacionado</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td>{{ $movement->happened_on->format('Y-m-d') }}</td>
                                    <td>
                                        {{ $movement->description }}
                                        @if ($movement->notes)
                                            <div class="text-muted small">{{ $movement->notes }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $movement->person?->name ?? '-' }}</td>
                                    <td>
                                        @if ($movement->is_rent)
                                            <span class="badge badge-soft-success">Renta</span>
                                        @elseif ($movement->is_san_juan)
                                            <span class="badge badge-soft-danger">SNJ</span>
                                        @else
                                            {{ \App\Support\FinanceLabels::movementType($movement->movement_type) }}
                                        @endif
                                    </td>
                                    <td>{{ $movement->account?->name ?? '-' }}</td>
                                    <td>{{ $movement->category?->name ?? '-' }}</td>
                                    <td>
                                        @if ($movement->is_rent && $movement->person)
                                            <span class="badge badge-soft-success">Inquilino</span>
                                        @elseif ($movement->person)
                                            <span class="badge badge-soft-primary">Persona</span>
                                        @elseif ($movement->category)
                                            <span class="badge badge-soft-secondary">Concepto</span>
                                        @else
                                            <span class="text-muted">Manual</span>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $movement->movement_type === 'expense' ? 'text-danger' : 'text-success' }}">{{ $money($movement->amount) }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <a href="{{ route('finance.movements.edit', ['movement' => $movement, 'month' => $monthValue]) }}" class="btn btn-sm btn-link text-primary p-0" title="Editar movimiento">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('finance.movements.destroy', $movement) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar con deshacer">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Sin movimientos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
