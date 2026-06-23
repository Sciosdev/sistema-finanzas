@extends('layouts.vertical', ['title' => 'Movimientos'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Movimientos</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.movements.index') }}" class="d-flex flex-wrap justify-content-md-end gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text">
                    <i data-lucide="search"></i>
                </span>
                <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Buscar movimiento...">
            </div>
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <select name="type" class="form-select" style="max-width: 170px">
                <option value="">Todos</option>
                <option value="income" @selected(request('type') === 'income')>Ingresos</option>
                <option value="expense" @selected(request('type') === 'expense')>Egresos</option>
                <option value="yield" @selected(request('type') === 'yield')>Rendimientos</option>
            </select>
            <select class="form-select" style="max-width: 160px" title="Resultados por página" onchange="this.form.per_page.value = this.value">
                <option value="{{ $perPage }}" @selected(! in_array(($perPage ?? 30), [30, 50, 100, 200], true))>Personalizado</option>
                @foreach ([30, 50, 100, 200] as $size)
                    <option value="{{ $size }}" @selected(($perPage ?? 30) === $size)>{{ $size }} por página</option>
                @endforeach
            </select>
            <input type="number" name="per_page" class="form-control" style="max-width: 135px" min="10" max="500" value="{{ $perPage ?? 30 }}" title="Cantidad personalizada de resultados por página">
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="filter" class="me-1"></i>Filtrar
            </button>
            <a class="btn btn-outline-success" href="{{ route('finance.movements.export', request()->only(['month', 'type', 'q']) + ['format' => 'xlsx']) }}">
                <i data-lucide="file-spreadsheet" class="me-1"></i>Excel
            </a>
            <a class="btn btn-outline-success" href="{{ route('finance.movements.export', request()->only(['month', 'type', 'q']) + ['format' => 'csv']) }}">
                <i data-lucide="download" class="me-1"></i>Exportar CSV
            </a>
            @if (request()->filled('q') || request()->filled('type'))
                <a href="{{ route('finance.movements.index', ['month' => $monthValue]) }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nuevo movimiento</h4>
    </div>
    <div class="card-body">
        @include('finance.partials.movement-form')
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="card-title mb-0">Historial</h4>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if (request()->filled('q'))
                <span class="badge badge-soft-primary">Búsqueda: {{ request('q') }}</span>
            @endif
            <span class="badge badge-soft-secondary">{{ $movements->total() }} movimientos</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Cuenta</th>
                        <th>Categoría</th>
                        <th>Persona</th>
                        <th class="text-end">Monto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td>{{ $movement->happened_on->format('Y-m-d') }}</td>
                            <td>
                                {{ $movement->description }}
                                @if ($movement->is_unknown)
                                    <span class="badge badge-soft-dark ms-1">?</span>
                                @endif
                                @if ($movement->is_san_juan)
                                    <span class="badge badge-soft-danger ms-1">SNJ</span>
                                @endif
                                @if ($movement->is_rent)
                                    <span class="badge badge-soft-success ms-1">Renta</span>
                                @endif
                            </td>
                            <td>{{ \App\Support\FinanceLabels::movementType($movement->movement_type) }}</td>
                            <td>{{ $movement->account?->name ?? '-' }}</td>
                            <td>{{ $movement->category?->name ?? '-' }}</td>
                            <td>{{ $movement->person?->name ?? '-' }}</td>
                            <td class="text-end {{ $movement->movement_type === 'expense' ? 'text-danger' : 'text-success' }}">{{ $money($movement->amount) }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <a href="{{ route('finance.movements.edit', ['movement' => $movement, 'month' => $monthValue]) }}" class="btn btn-sm btn-link text-primary p-0" title="Editar">
                                        <i data-lucide="pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('finance.movements.destroy', $movement) }}" onsubmit="return confirm('¿Eliminar este movimiento? Podrás deshacerlo durante 2 minutos.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Sin movimientos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($movements->hasPages())
        <div class="card-footer d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div class="text-muted small">
                Mostrando {{ $movements->firstItem() }} a {{ $movements->lastItem() }} de {{ $movements->total() }} movimientos
            </div>
            {{ $movements->links() }}
        </div>
    @elseif ($movements->total() > 0)
        <div class="card-footer text-muted small">
            Mostrando {{ $movements->firstItem() }} a {{ $movements->lastItem() }} de {{ $movements->total() }} movimientos
        </div>
    @endif
</div>
@endsection
