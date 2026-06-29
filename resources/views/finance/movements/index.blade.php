@extends('layouts.vertical', ['title' => 'Movimientos'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-12">
        <h4 class="mb-0 fw-semibold">Movimientos</h4>
    </div>
</div>

@include('finance.partials.money-overview')

{{-- 1. Nuevo movimiento --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nuevo movimiento</h4>
    </div>
    <div class="card-body">
        @include('finance.partials.movement-form')
    </div>
</div>

{{-- 2. Filtros del historial --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Filtros del historial</h4>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('finance.movements.index') }}" class="d-flex flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text">
                    <i data-lucide="search"></i>
                </span>
                <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Buscar movimiento...">
            </div>
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <select name="type" class="form-select" style="max-width: 150px">
                <option value="">Todos</option>
                <option value="income" @selected(request('type') === 'income')>Ingresos</option>
                <option value="expense" @selected(request('type') === 'expense')>Egresos</option>
                <option value="yield" @selected(request('type') === 'yield')>Rendimientos</option>
            </select>
            <select name="account_id" class="form-select" style="max-width: 150px" title="Cuenta">
                <option value="">Toda cuenta</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="form-select" style="max-width: 160px" title="Categoría">
                <option value="">Toda categoría</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="person_id" class="form-select" style="max-width: 150px" title="Persona">
                <option value="">Toda persona</option>
                @foreach ($people as $person)
                    <option value="{{ $person->id }}" @selected((string) request('person_id') === (string) $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
            <select name="flag" class="form-select" style="max-width: 170px" title="Estado / marca">
                <option value="">Cualquier estado</option>
                <option value="uncategorized" @selected(request('flag') === 'uncategorized')>Sin categoría</option>
                <option value="no_account" @selected(request('flag') === 'no_account')>Sin cuenta</option>
                <option value="unknown" @selected(request('flag') === 'unknown')>Desconocidos (?)</option>
                <option value="san_juan" @selected(request('flag') === 'san_juan')>San Juan</option>
                <option value="rent" @selected(request('flag') === 'rent')>Renta</option>
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
            <a class="btn btn-outline-info" href="{{ route('finance.movements.suggestions.index', request()->only(['month', 'type', 'q'])) }}" title="Sugerencias de clasificación">
                <i data-lucide="wand-2" class="me-1"></i>Sugerencias
            </a>
            @if (auth()->user()?->isFinanceOwner())
            <a class="btn btn-outline-success" href="{{ route('finance.movements.export', request()->only(['month', 'type', 'q']) + ['format' => 'xlsx']) }}">
                <i data-lucide="file-spreadsheet" class="me-1"></i>Excel
            </a>
            <a class="btn btn-outline-success" href="{{ route('finance.movements.export', request()->only(['month', 'type', 'q']) + ['format' => 'csv']) }}">
                <i data-lucide="download" class="me-1"></i>Exportar CSV
            </a>
            @endif
            @if (request()->filled('q') || request()->filled('type') || request()->filled('account_id') || request()->filled('category_id') || request()->filled('person_id') || request()->filled('flag'))
                <a href="{{ route('finance.movements.index', ['month' => $monthValue]) }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </form>
    </div>
</div>

{{-- 3. Historial de movimientos --}}
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
        <div class="px-3 py-2 border-bottom d-flex flex-wrap gap-4 small align-items-center">
            <span class="text-muted">Totales del filtro:</span>
            <span>Ingresos <strong class="text-success">{{ $money($filterTotals['income']) }}</strong></span>
            <span>Egresos <strong class="text-danger">{{ $money($filterTotals['expense']) }}</strong></span>
            <span>Neto <strong class="{{ $filterTotals['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($filterTotals['net']) }}</strong></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 36px;">
                            <input type="checkbox" id="bulk-select-all" class="form-check-input" title="Seleccionar todos los visibles">
                        </th>
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
                    @forelse ($movements->groupBy(fn ($movement) => $movement->happened_on->format('Y-m-d')) as $day => $dayMovements)
                        @php
                            $dayIncome = $dayMovements->whereIn('movement_type', ['income', 'yield'])->sum(fn ($m) => (float) $m->amount);
                            $dayExpense = $dayMovements->where('movement_type', 'expense')->sum(fn ($m) => (float) $m->amount);
                            $dayNet = $dayIncome - $dayExpense;
                        @endphp
                        <tr class="table-active">
                            <td></td>
                            <td colspan="6" class="fw-semibold">
                                {{ \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::parse($day)->translatedFormat('l d M Y')) }}
                                <span class="text-muted fw-normal small ms-1">· {{ $dayMovements->count() }} mov.</span>
                            </td>
                            <td class="text-end fw-semibold {{ $dayNet >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($dayNet) }}</td>
                            <td></td>
                        </tr>
                        @foreach ($dayMovements as $movement)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $movement->id }}" form="bulk-movements-form" class="form-check-input bulk-row-check">
                            </td>
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
                                    <a href="{{ route('finance.movements.edit', ['movement' => $movement, 'month' => $monthValue, 'return_to' => request()->fullUrl()]) }}" class="btn btn-sm btn-link text-primary p-0" title="Editar">
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
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Sin movimientos</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($movements->count() > 0)
                    {{-- 4. Encabezados repetidos al final (solo nombres de columnas) --}}
                    <tfoot>
                        <tr>
                            <th style="width: 36px;">
                                <input type="checkbox" id="bulk-select-all-bottom" class="form-check-input" title="Seleccionar todos los visibles">
                            </th>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Cuenta</th>
                            <th>Categoría</th>
                            <th>Persona</th>
                            <th class="text-end">Monto</th>
                            <th>Acciones</th>
                        </tr>
                    </tfoot>
                @endif
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

{{-- 5. Aplicar cambios masivos (debajo del historial) --}}
<form id="bulk-movements-form" method="POST" action="{{ route('finance.movements.bulk-update') }}" onsubmit="return financeBulkConfirm(this);">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
    <div class="card border-primary-subtle">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="card-title mb-0">
                <i data-lucide="list-checks" class="me-1"></i>Aplicar cambios masivos
            </h4>
            <span class="badge badge-soft-primary">
                Seleccionados: <span id="bulk-selected-count">0</span>
            </span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Los cambios se aplican <strong>solo a los movimientos seleccionados</strong> en la lista de arriba.
                Deja un campo en <em>“No cambiar”</em> para no sobrescribirlo.
            </p>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Categoría</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->group ? $category->group . ' · ' : '' }}{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Persona</label>
                    <select name="person_id" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Cuenta</label>
                    <select name="account_id" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tipo</label>
                    <select name="movement_type" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        <option value="income">Ingreso</option>
                        <option value="expense">Egreso</option>
                        <option value="yield">Rendimiento</option>
                        <option value="transfer">Transferencia</option>
                        <option value="adjustment">Ajuste</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Desconocido</label>
                    <select name="is_unknown" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        <option value="1">Marcar como desconocido</option>
                        <option value="0">Quitar desconocido</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">San Juan</label>
                    <select name="is_san_juan" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        <option value="1">Marcar San Juan</option>
                        <option value="0">Quitar San Juan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Renta</label>
                    <select name="is_rent" class="form-select form-select-sm">
                        <option value="">No cambiar</option>
                        <option value="1">Marcar renta</option>
                        <option value="0">Quitar renta</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i data-lucide="save" class="me-1"></i>Aplicar a seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    (function () {
        function rowChecks() {
            return Array.prototype.slice.call(document.querySelectorAll('.bulk-row-check'));
        }

        function refreshCount() {
            var counter = document.getElementById('bulk-selected-count');
            if (counter) {
                counter.textContent = rowChecks().filter(function (c) { return c.checked; }).length;
            }
        }

        var selectAllToggles = Array.prototype.slice.call(document.querySelectorAll('#bulk-select-all, #bulk-select-all-bottom'));
        selectAllToggles.forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                rowChecks().forEach(function (c) { c.checked = toggle.checked; });
                selectAllToggles.forEach(function (other) { other.checked = toggle.checked; });
                refreshCount();
            });
        });

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('bulk-row-check')) {
                refreshCount();
            }
        });

        refreshCount();
    })();

    window.financeBulkConfirm = function () {
        var checked = document.querySelectorAll('.bulk-row-check:checked').length;
        if (checked === 0) {
            alert('Selecciona al menos un movimiento.');
            return false;
        }
        return confirm('Se aplicarán los cambios a ' + checked + ' movimiento(s) seleccionados. ¿Continuar?');
    };
</script>
@endsection
