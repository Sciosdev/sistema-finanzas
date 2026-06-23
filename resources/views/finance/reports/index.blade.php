@extends('layouts.vertical', ['title' => 'Reportes'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $netClass = fn ($value) => (float) $value < 0 ? 'text-danger' : 'text-success';
    $donutGradient = function ($rows) {
        $cursor = 0;
        $segments = [];

        foreach ($rows->take(10) as $row) {
            $percentage = max(0, (float) ($row['percentage'] ?? 0));

            if ($percentage <= 0) {
                continue;
            }

            $end = min(100, $cursor + $percentage);
            $segments[] = ($row['color'] ?? '#64748b') . " {$cursor}% {$end}%";
            $cursor = $end;
        }

        if ($cursor < 100) {
            $segments[] = "#334155 {$cursor}% 100%";
        }

        return $segments ? implode(', ', $segments) : '#334155 0% 100%';
    };
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Reportes financieros</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.reports.index') }}" class="d-flex justify-content-md-end gap-2 flex-wrap">
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <input type="number" name="year" class="form-control" style="max-width: 120px" min="2000" max="2100" value="{{ $yearValue }}">
            <select name="category_id" class="form-select" style="max-width: 230px">
                <option value="">Todas las categorías</option>
                @foreach ($expenseCategoryRows as $row)
                    @if ($row['category_id'])
                        <option value="{{ $row['category_id'] }}" @selected($selectedCategory?->id === $row['category_id'])>
                            {{ $row['name'] }}
                        </option>
                    @endif
                @endforeach
            </select>
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="calendar-search" class="me-1"></i>Ver
            </button>
            <a class="btn btn-outline-success" href="{{ route('finance.reports.export', request()->only(['month', 'year', 'category_id'])) }}">
                <i data-lucide="download" class="me-1"></i>Exportar CSV
            </a>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Ingresos del mes</p>
                <h4 class="fw-semibold text-success mb-0">{{ $money($monthTotals['income']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Egresos del mes</p>
                <h4 class="fw-semibold text-danger mb-0">{{ $money($monthTotals['expenses']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Utilidad del mes</p>
                <h4 class="fw-semibold {{ $netClass($monthTotals['net']) }} mb-0">{{ $money($monthTotals['net']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Utilidad anual</p>
                <h4 class="fw-semibold {{ $netClass($yearTotals['net']) }} mb-0">{{ $money($yearTotals['net']) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Dónde bajarle al gasto</h4>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse ($spendingOpportunityRows as $row)
                <div class="col-xl-4 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background: {{ $row['color'] }}"></span>
                                <strong>{{ $row['name'] }}</strong>
                            </div>
                            <span class="badge badge-soft-secondary">{{ number_format($row['percentage'], 1) }}%</span>
                        </div>
                        <h5 class="text-danger mb-1">{{ $money($row['amount']) }}</h5>
                        <div class="text-muted small mb-2">{{ $row['count'] }} movimiento(s)</div>
                        <div class="small">{{ $row['recommendation'] }}</div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted mb-0">Sin egresos suficientes para sugerir ajustes este mes.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Egresos por categoría</h4>
            </div>
            <div class="card-body">
                @if ($expenseCategoryRows->isEmpty())
                    <p class="text-muted mb-0">No hay egresos registrados en este mes.</p>
                @else
                    <div class="d-flex flex-column align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 220px; height: 220px; background: conic-gradient({{ $donutGradient($expenseCategoryRows) }});">
                            <div class="rounded-circle bg-body d-flex align-items-center justify-content-center text-center"
                                 style="width: 128px; height: 128px;">
                                <div>
                                    <div class="text-muted small">Egresos</div>
                                    <div class="fw-semibold">{{ $money($monthTotals['expenses']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="w-100">
                            @foreach ($expenseCategoryRows->take(8) as $row)
                                <div class="d-flex align-items-center justify-content-between gap-2 py-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background: {{ $row['color'] }}"></span>
                                        <span>{{ $row['name'] }}</span>
                                    </div>
                                    <span class="text-muted">{{ number_format($row['percentage'], 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h4 class="card-title mb-0">Categorías con más egresos</h4>
                @if ($selectedCategory)
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('finance.reports.index', ['month' => $monthValue, 'year' => $yearValue]) }}">
                        Ver todas
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Grupo</th>
                                <th class="text-end">Movs.</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Peso</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenseCategoryRows as $row)
                                <tr>
                                    <td>
                                        <span class="rounded-circle d-inline-block me-2" style="width: 10px; height: 10px; background: {{ $row['color'] }}"></span>
                                        {{ $row['name'] }}
                                    </td>
                                    <td>{{ $row['group'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end text-danger">{{ $money($row['amount']) }}</td>
                                    <td class="text-end">{{ number_format($row['percentage'], 1) }}%</td>
                                    <td class="text-end">
                                        @if ($row['category_id'])
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="{{ route('finance.reports.index', ['month' => $monthValue, 'year' => $yearValue, 'category_id' => $row['category_id']]) }}">
                                                Detalle
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin egresos para analizar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    Detalle por concepto{{ $selectedCategory ? ': ' . $selectedCategory->name : '' }}
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Categoría</th>
                                <th class="text-end">Veces</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Peso</th>
                                <th>Ultimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenseConceptRows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['category'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end text-danger">{{ $money($row['amount']) }}</td>
                                    <td class="text-end">{{ number_format($row['percentage'], 1) }}%</td>
                                    <td>{{ $row['last_date'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin conceptos para este filtro.</td>
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
                <h4 class="card-title mb-0">Conceptos importantes</h4>
            </div>
            <div class="card-body">
                @forelse ($importantConceptRows as $row)
                    @php
                        $bar = $monthTotals['expenses'] > 0 ? min(100, ((float) $row['amount'] / (float) $monthTotals['expenses']) * 100) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between gap-2 mb-1">
                            <span>{{ $row['name'] }}</span>
                            <span class="text-danger fw-semibold">{{ $money($row['amount']) }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: {{ $bar }}%; background: {{ $row['color'] }}"></div>
                        </div>
                        <div class="text-muted small mt-1">{{ $row['count'] }} movimiento(s)</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Todavía no hay focos de gasto para este mes.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Por día</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 520px;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Día</th>
                                <th>Rango</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dailyRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['range'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['income']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['expenses']) }}</td>
                                    <td class="text-end {{ $netClass($row['net']) }}">{{ $money($row['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Por semana</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Semana</th>
                                <th>Rango</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeklyRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['range'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['income']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['expenses']) }}</td>
                                    <td class="text-end {{ $netClass($row['net']) }}">{{ $money($row['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Por quincena</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Quincena</th>
                                <th>Rango</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fortnightRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['range'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['income']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['expenses']) }}</td>
                                    <td class="text-end {{ $netClass($row['net']) }}">{{ $money($row['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Por mes</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Periodo</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthlyRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['range'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['income']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['expenses']) }}</td>
                                    <td class="text-end {{ $netClass($row['net']) }}">{{ $money($row['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Por año</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Año</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-end">Egresos</th>
                                <th class="text-end">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($yearlyRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['income']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['expenses']) }}</td>
                                    <td class="text-end {{ $netClass($row['net']) }}">{{ $money($row['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
