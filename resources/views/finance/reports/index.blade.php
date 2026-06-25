@extends('layouts.vertical', ['title' => 'Reportes'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $netClass = fn ($value) => (float) $value < 0 ? 'text-danger' : 'text-success';
    $reportChartData = $reportChartData ?? [];
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
            <a class="btn btn-outline-success" href="{{ route('finance.reports.export', request()->only(['month', 'year', 'category_id']) + ['format' => 'xlsx']) }}">
                <i data-lucide="file-spreadsheet" class="me-1"></i>Excel
            </a>
            <a class="btn btn-outline-success" href="{{ route('finance.reports.export', request()->only(['month', 'year', 'category_id']) + ['format' => 'csv']) }}">
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

<script type="application/json" id="finance-report-chart-data">{!! json_encode($reportChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Distribucion real del mes</h4>
            </div>
            <div class="card-body">
                <div id="reports-real-distribution-donut" class="apex-charts" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Egresos por categoria</h4>
            </div>
            <div class="card-body">
                <div id="reports-expense-category-donut" class="apex-charts" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Obligaciones del mes</h4>
            </div>
            <div class="card-body">
                <div id="reports-obligation-mix-donut" class="apex-charts" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Top ingresos</h4>
            </div>
            <div class="card-body">
                <div id="reports-top-income-bar" class="apex-charts" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Top egresos</h4>
            </div>
            <div class="card-body">
                <div id="reports-top-expenses-bar" class="apex-charts" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Cobertura del mes</h4>
            </div>
            <div class="card-body">
                <div id="reports-coverage-bar" class="apex-charts" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Ano en perspectiva</h4>
            </div>
            <div class="card-body">
                <div id="reports-year-perspective-column" class="apex-charts" style="min-height: 320px;"></div>
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
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <div class="text-muted small">Egresos analizados</div>
                            <h4 class="text-danger mb-0">{{ $money($monthTotals['expenses']) }}</h4>
                        </div>
                        <div class="w-100">
                            @foreach ($expenseCategoryRows->take(8) as $row)
                                <div class="d-flex align-items-center justify-content-between gap-2 py-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background: {{ $row['color'] }}"></span>
                                        <span>{{ $row['name'] }}</span>
                                    </div>
                                    <span class="text-muted">{{ $money($row['amount']) }} · {{ number_format($row['percentage'], 1) }}%</span>
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

@section('scripts')
    @php
        $financeReportsEntry = 'resources/js/pages/finance-reports.js';
        $viteManifestPath = public_path('build/manifest.json');
        $viteManifest = is_file($viteManifestPath)
            ? json_decode((string) file_get_contents($viteManifestPath), true)
            : [];
        $financeReportsAssetAvailable = is_file(public_path('hot'))
            || (is_array($viteManifest) && isset($viteManifest[$financeReportsEntry]));
    @endphp

    @if ($financeReportsAssetAvailable)
        @vite([$financeReportsEntry])
    @else
        <script>
            (function () {
                const moneyFormatter = new Intl.NumberFormat('es-MX', {
                    style: 'currency',
                    currency: 'MXN',
                    maximumFractionDigits: 2,
                });
                const chartTextColor = '#b8c5d8';
                const gridColor = 'rgba(148, 163, 184, 0.18)';
                const fallbackChartIds = [
                    'reports-real-distribution-donut',
                    'reports-expense-category-donut',
                    'reports-obligation-mix-donut',
                    'reports-top-income-bar',
                    'reports-top-expenses-bar',
                    'reports-coverage-bar',
                    'reports-year-perspective-column',
                ];

                function money(value) {
                    return moneyFormatter.format(Number(value) || 0);
                }

                function chartElement(id) {
                    return document.getElementById(id);
                }

                function readChartData() {
                    const source = document.getElementById('finance-report-chart-data');

                    if (!source) {
                        return {};
                    }

                    try {
                        return JSON.parse(source.textContent || '{}');
                    } catch (error) {
                        return {};
                    }
                }

                function rowsFrom(section) {
                    return Array.isArray(section?.rows)
                        ? section.rows.filter((row) => Number(row.amount) > 0)
                        : [];
                }

                function hasSeriesData(series) {
                    return Array.isArray(series)
                        && series.some((item) => Array.isArray(item.data)
                            && item.data.some((value) => Number(value) !== 0));
                }

                function renderEmpty(id, message) {
                    const element = chartElement(id);

                    if (!element) {
                        return;
                    }

                    element.innerHTML = '<div class="d-flex align-items-center justify-content-center text-muted h-100 py-5">'
                        + (message || 'Sin datos para graficar.')
                        + '</div>';
                }

                function renderAllEmpty(message) {
                    fallbackChartIds.forEach((id) => renderEmpty(id, message));
                }

                function renderDonut(id, section) {
                    const element = chartElement(id);
                    const rows = rowsFrom(section);

                    if (!element) {
                        return;
                    }

                    if (rows.length === 0) {
                        renderEmpty(id);
                        return;
                    }

                    new ApexCharts(element, {
                        chart: {
                            type: 'donut',
                            height: 280,
                            toolbar: { show: false },
                            foreColor: chartTextColor,
                        },
                        series: rows.map((row) => Number(row.amount) || 0),
                        labels: rows.map((row) => row.name),
                        colors: rows.map((row) => row.color || '#64748b'),
                        stroke: {
                            width: 2,
                            colors: ['#1f2933'],
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: (value) => `${Number(value).toFixed(1)}%`,
                        },
                        legend: {
                            position: 'bottom',
                            fontSize: '13px',
                            markers: { radius: 12 },
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '64%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            formatter: (w) => money(w.globals.seriesTotals.reduce((sum, value) => sum + value, 0)),
                                        },
                                        value: {
                                            formatter: (value) => money(value),
                                        },
                                    },
                                },
                            },
                        },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (value) => money(value) },
                        },
                        responsive: [{
                            breakpoint: 576,
                            options: {
                                chart: { height: 240 },
                                legend: { show: false },
                            },
                        }],
                    }).render();
                }

                function renderHorizontalBar(id, section) {
                    const element = chartElement(id);
                    const rows = rowsFrom(section);

                    if (!element) {
                        return;
                    }

                    if (rows.length === 0) {
                        renderEmpty(id);
                        return;
                    }

                    new ApexCharts(element, {
                        chart: {
                            type: 'bar',
                            height: Math.max(260, rows.length * 42 + 90),
                            toolbar: { show: false },
                            foreColor: chartTextColor,
                        },
                        series: [{
                            name: 'Monto',
                            data: rows.map((row) => Number(row.amount) || 0),
                        }],
                        colors: rows.map((row) => row.color || '#64748b'),
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                distributed: true,
                                borderRadius: 4,
                                barHeight: '68%',
                            },
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: (value) => money(value),
                            style: { colors: ['#e5edf8'] },
                        },
                        xaxis: {
                            categories: rows.map((row) => row.name),
                            labels: { formatter: (value) => money(value) },
                        },
                        yaxis: {
                            labels: { maxWidth: 170 },
                        },
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 4,
                        },
                        legend: { show: false },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (value) => money(value) },
                        },
                    }).render();
                }

                function renderCoverage(section) {
                    const element = chartElement('reports-coverage-bar');

                    if (!element) {
                        return;
                    }

                    if (!hasSeriesData(section?.series)) {
                        renderEmpty('reports-coverage-bar');
                        return;
                    }

                    new ApexCharts(element, {
                        chart: {
                            type: 'bar',
                            height: 280,
                            stacked: true,
                            stackType: 'normal',
                            toolbar: { show: false },
                            foreColor: chartTextColor,
                        },
                        series: section.series,
                        colors: section.colors || ['#22c55e', '#f59e0b', '#dc2626'],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 4,
                                barHeight: '58%',
                            },
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: (value) => (Number(value) > 0 ? money(value) : ''),
                        },
                        xaxis: {
                            categories: section.labels || [],
                            labels: { formatter: (value) => money(value) },
                        },
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 4,
                        },
                        legend: { position: 'bottom' },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (value) => money(value) },
                        },
                    }).render();
                }

                function renderYearPerspective(section) {
                    const element = chartElement('reports-year-perspective-column');

                    if (!element) {
                        return;
                    }

                    if (!hasSeriesData(section?.series)) {
                        renderEmpty('reports-year-perspective-column');
                        return;
                    }

                    new ApexCharts(element, {
                        chart: {
                            type: 'bar',
                            height: 320,
                            toolbar: { show: false },
                            foreColor: chartTextColor,
                        },
                        series: section.series,
                        colors: section.colors || ['#22c55e', '#ef4444', '#60a5fa'],
                        plotOptions: {
                            bar: {
                                columnWidth: '58%',
                                borderRadius: 3,
                            },
                        },
                        dataLabels: { enabled: false },
                        xaxis: { categories: section.labels || [] },
                        yaxis: { labels: { formatter: (value) => money(value) } },
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 4,
                        },
                        legend: { position: 'bottom' },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (value) => money(value) },
                        },
                    }).render();
                }

                function renderCharts() {
                    const data = readChartData();

                    renderDonut('reports-real-distribution-donut', data.realDistribution);
                    renderDonut('reports-expense-category-donut', data.expenseCategories);
                    renderDonut('reports-obligation-mix-donut', data.obligationMix);
                    renderHorizontalBar('reports-top-income-bar', data.topIncome);
                    renderHorizontalBar('reports-top-expenses-bar', data.topExpenses);
                    renderCoverage(data.coverage || {});
                    renderYearPerspective(data.yearPerspective || {});
                }

                function loadApexCharts(callback) {
                    if (window.ApexCharts) {
                        callback();
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@4.4.0/dist/apexcharts.min.js';
                    script.onload = callback;
                    script.onerror = function () {
                        renderAllEmpty('No se pudo cargar la libreria de graficas.');
                    };
                    document.head.appendChild(script);
                }

                function boot() {
                    loadApexCharts(renderCharts);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot);
                } else {
                    boot();
                }
            })();
        </script>
    @endif
@endsection
