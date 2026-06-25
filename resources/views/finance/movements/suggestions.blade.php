@extends('layouts.vertical', ['title' => 'Sugerencias de clasificación'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $confidenceClass = [
        'alta' => 'badge-soft-success',
        'media' => 'badge-soft-warning',
        'baja' => 'badge-soft-secondary',
    ];
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Sugerencias de clasificación</h4>
        <div class="text-muted">Propuestas según tus catálogos e historial. Nada se aplica solo: revisa y elige.</div>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <a href="{{ route('finance.movements.index', ['month' => $monthValue]) }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" class="me-1"></i>Volver a movimientos
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('finance.movements.suggestions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Mes</label>
                <input type="month" name="month" class="form-control form-control-sm" value="{{ $monthValue }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Búsqueda</label>
                <input type="search" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}" placeholder="Concepto...">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Tipo</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="income" @selected($filters['type'] === 'income')>Ingresos</option>
                    <option value="expense" @selected($filters['type'] === 'expense')>Egresos</option>
                    <option value="yield" @selected($filters['type'] === 'yield')>Rendimientos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Confianza</label>
                <select name="confidence" class="form-select form-select-sm">
                    <option value="">Cualquiera</option>
                    <option value="alta" @selected($filters['confidence'] === 'alta')>Alta</option>
                    <option value="media" @selected($filters['confidence'] === 'media')>Media</option>
                    <option value="baja" @selected($filters['confidence'] === 'baja')>Baja</option>
                </select>
            </div>
            <div class="col-md-3 d-flex flex-wrap gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="only_without_category" value="1" id="f_no_cat" @checked($filters['only_without_category'])>
                    <label class="form-check-label small" for="f_no_cat">Sin categoría</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="only_unknown" value="1" id="f_unknown" @checked($filters['only_unknown'])>
                    <label class="form-check-label small" for="f_unknown">Desconocidos</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="only_with_suggestion" value="1" id="f_with_sug" @checked($filters['only_with_suggestion'])>
                    <label class="form-check-label small" for="f_with_sug">Con sugerencia</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="filter" class="me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<form id="suggestions-apply-form" method="POST" action="{{ route('finance.movements.suggestions.apply') }}" onsubmit="return financeSuggestionsConfirm();">
    @csrf
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
    <div class="card border-primary-subtle mb-3">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0"><i data-lucide="wand-2" class="me-1"></i>Aplicar sugerencias seleccionadas</h5>
            <span class="badge badge-soft-primary">Seleccionados: <span id="sug-selected-count">0</span></span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Se aplican <strong>solo a los movimientos seleccionados</strong> y solo donde haya sugerencia. Elige qué campos aplicar:</p>
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apply_category" value="1" id="apply_category" checked>
                    <label class="form-check-label" for="apply_category">Categoría sugerida</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apply_person" value="1" id="apply_person">
                    <label class="form-check-label" for="apply_person">Persona sugerida</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apply_account" value="1" id="apply_account">
                    <label class="form-check-label" for="apply_account">Cuenta sugerida</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apply_flags" value="1" id="apply_flags">
                    <label class="form-check-label" for="apply_flags">Marcas (San Juan / Renta)</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm ms-auto">
                    <i data-lucide="check" class="me-1"></i>Aplicar a seleccionados
                </button>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="card-title mb-0">Movimientos</h4>
        <span class="badge badge-soft-secondary">{{ $movements->total() }} en el periodo</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 36px;">
                            <input type="checkbox" id="sug-select-all" class="form-check-input" title="Seleccionar todos los visibles">
                        </th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th class="text-end">Monto</th>
                        <th>Actual</th>
                        <th>Sugerencia</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php($sug = $suggestions[$movement->id] ?? null)
                        @if ($filters['only_with_suggestion'] && ! ($sug && $sug['has_any']))
                            @continue
                        @endif
                        @if ($filters['confidence'])
                            @php($matchesConfidence = $sug && (
                                (! empty($sug['category']) && $sug['category']['confidence'] === $filters['confidence'])
                                || (! empty($sug['person']) && $sug['person']['confidence'] === $filters['confidence'])
                            ))
                            @if (! $matchesConfidence)
                                @continue
                            @endif
                        @endif
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $movement->id }}" form="suggestions-apply-form" class="form-check-input sug-row-check">
                            </td>
                            <td class="text-nowrap">{{ $movement->happened_on->format('Y-m-d') }}</td>
                            <td>
                                {{ $movement->description }}
                                @if ($movement->is_unknown)
                                    <span class="badge badge-soft-dark ms-1">?</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap {{ $movement->movement_type === 'expense' ? 'text-danger' : 'text-success' }}">{{ $money($movement->amount) }}</td>
                            <td class="small">
                                <div>Cat: {{ $movement->category?->name ?? '—' }}</div>
                                <div>Per: {{ $movement->person?->name ?? '—' }}</div>
                                <div>Cta: {{ $movement->account?->name ?? '—' }}</div>
                            </td>
                            <td class="small">
                                @if ($sug && $sug['category'])
                                    <div>
                                        <span class="badge {{ $confidenceClass[$sug['category']['confidence']] ?? 'badge-soft-secondary' }}">{{ ucfirst($sug['category']['confidence']) }}</span>
                                        <strong>{{ $sug['category']['name'] }}</strong>
                                        <div class="text-muted">{{ $sug['category']['reason'] }}</div>
                                    </div>
                                @endif
                                @if ($sug && $sug['person'])
                                    <div class="mt-1">
                                        <span class="badge {{ $confidenceClass[$sug['person']['confidence']] ?? 'badge-soft-secondary' }}">{{ ucfirst($sug['person']['confidence']) }}</span>
                                        Persona: <strong>{{ $sug['person']['name'] }}</strong>
                                        <div class="text-muted">{{ $sug['person']['reason'] }}</div>
                                    </div>
                                @endif
                                @if ($sug && $sug['account'])
                                    <div class="mt-1 text-muted">Cuenta sugerida: {{ $sug['account']['name'] }}</div>
                                @endif
                                @if (! $sug || ! $sug['has_any'])
                                    <span class="text-muted">Sin sugerencia</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('finance.movements.edit', ['movement' => $movement, 'month' => $monthValue, 'return_to' => $returnTo]) }}" class="btn btn-sm btn-link text-primary p-0" title="Editar">
                                    <i data-lucide="pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Sin movimientos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($movements->hasPages())
        <div class="card-footer">
            {{ $movements->links() }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    (function () {
        function rowChecks() {
            return Array.prototype.slice.call(document.querySelectorAll('.sug-row-check'));
        }
        function refreshCount() {
            var counter = document.getElementById('sug-selected-count');
            if (counter) {
                counter.textContent = rowChecks().filter(function (c) { return c.checked; }).length;
            }
        }
        var selectAll = document.getElementById('sug-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                rowChecks().forEach(function (c) { c.checked = selectAll.checked; });
                refreshCount();
            });
        }
        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('sug-row-check')) {
                refreshCount();
            }
        });
        refreshCount();
    })();

    window.financeSuggestionsConfirm = function () {
        var checked = document.querySelectorAll('.sug-row-check:checked').length;
        if (checked === 0) {
            alert('Selecciona al menos un movimiento.');
            return false;
        }
        return confirm('Se aplicarán las sugerencias elegidas a ' + checked + ' movimiento(s). ¿Continuar?');
    };
</script>
@endsection
