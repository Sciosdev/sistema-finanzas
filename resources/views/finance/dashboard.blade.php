@extends('layouts.vertical', ['title' => 'Finanzas'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $difference = $summary['difference'];
    $isBalanced = $difference !== null && abs((float) $difference) <= 0.01;
    $defaultIncomeAccount = $accounts->firstWhere('name', 'NU') ?? $accounts->first();
    $dailyChart = $summary['daily_income_chart'];
    $monthlyChart = $summary['monthly_income_chart'];
    $chartWidth = 520;
    $chartHeight = 180;
    $chartLeft = 28;
    $chartBottom = 152;
    $chartTop = 24;
    $chartInnerWidth = $chartWidth - ($chartLeft * 2);
    $chartInnerHeight = $chartBottom - $chartTop;
    $dailyValues = $dailyChart['values'];
    $overduePaymentTotal = (float) ($summary['obligation_totals']['overdue'] ?? 0);
    $overdueExpectedTotal = (float) ($summary['overdue_expected_income'] ?? 0);
    $overduePaymentCount = $summary['next_payments']->where('is_overdue', true)->count();
    $overdueIncomeCount = $summary['next_expected_incomes']->where('status', 'overdue')->count();
    $dailyCount = max(1, count($dailyValues) - 1);
    $dailyPoints = collect($dailyValues)
        ->map(function ($value, $index) use ($chartLeft, $chartBottom, $chartInnerWidth, $chartInnerHeight, $dailyCount, $dailyChart) {
            $x = $chartLeft + (($index / $dailyCount) * $chartInnerWidth);
            $y = $chartBottom - (((float) $value / $dailyChart['max']) * $chartInnerHeight);

            return round($x, 2) . ',' . round($y, 2);
        })
        ->implode(' ');
    $detailKey = request('detail');
    $dashboardDetailUrl = fn (string $key) => route('finance.dashboard', ['month' => $summary['month_value'], 'detail' => $key]) . '#indicator-detail';
    $indicatorDetails = [
        'income-real' => [
            'title' => 'Ingresos reales',
            'amount' => $summary['total_income'],
            'explanation' => 'Suma de ingresos registrados y rendimientos capturados en movimientos del mes.',
            'items' => [
                ['label' => 'Ingresos registrados', 'value' => $summary['income']],
                ['label' => 'Rendimientos registrados', 'value' => $summary['yields']],
            ],
            'movements' => $summary['income_movements'],
        ],
        'projected-income' => [
            'title' => 'Ingresos proyectados',
            'amount' => $summary['projected_total_income'],
            'explanation' => 'Ingresos reales del mes más ingresos esperados que todavía están pendientes.',
            'items' => [
                ['label' => 'Ingresos reales', 'value' => $summary['total_income']],
                ['label' => 'Ingresos esperados pendientes', 'value' => $summary['pending_expected_income']],
            ],
            'expected_incomes' => $summary['next_expected_incomes'],
        ],
        'expenses-real' => [
            'title' => 'Egresos reales',
            'amount' => $summary['expenses'],
            'explanation' => 'Suma de egresos registrados en movimientos del mes.',
            'items' => [
                ['label' => 'Egresos registrados', 'value' => $summary['expenses']],
                ['label' => 'Egresos sin identificar (?)', 'value' => $summary['unknown_expenses']],
            ],
            'movements' => $summary['expense_movements'],
        ],
        'expected-leftover' => [
            'title' => 'Saldo proyectado antes de obligaciones',
            'amount' => $summary['expected_leftover'],
            'explanation' => 'Resultado contable del mes antes de restar pagos pendientes: ingresos reales menos egresos reales.',
            'items' => [
                ['label' => 'Ingresos reales', 'value' => $summary['total_income']],
                ['label' => 'Egresos reales', 'value' => -1 * (float) $summary['expenses']],
            ],
        ],
        'real-total-cut' => [
            'title' => 'Saldo real del corte',
            'amount' => $summary['real_total'],
            'explanation' => 'Dinero real que reportaste en el último corte: tarjetas más efectivo.',
            'items' => [
                ['label' => 'Saldo real en tarjetas', 'value' => $summary['latest_cut'] ? $summary['latest_cut']->cards_amount : 0],
                ['label' => 'Saldo real en efectivo', 'value' => $summary['latest_cut'] ? $summary['latest_cut']->cash_amount : 0],
            ],
        ],
        'cut-difference' => [
            'title' => 'Diferencia de conciliación',
            'amount' => $summary['difference'],
            'explanation' => 'Compara el saldo proyectado antes de obligaciones contra el saldo real del corte. Si queda en 0, tu corte cuadra.',
            'items' => [
                ['label' => 'Saldo proyectado antes de obligaciones', 'value' => $summary['expected_leftover']],
                ['label' => 'Saldo real del corte', 'value' => -1 * (float) $summary['real_total']],
            ],
        ],
        'amount-missing' => [
            'title' => 'Saldo disponible después de obligaciones',
            'amount' => $summary['amount_missing'],
            'explanation' => 'Saldo real del corte menos obligaciones pendientes del mes. Si sale negativo, falta dinero para cubrir lo pendiente.',
            'items' => [
                ['label' => 'Saldo real del corte', 'value' => $summary['real_total']],
                ['label' => 'Obligaciones pendientes', 'value' => -1 * (float) $summary['pending_payments']],
            ],
            'obligations' => $summary['next_payments'],
        ],
        'san-juan-expenses' => [
            'title' => 'Egresos San Juan',
            'amount' => $summary['san_juan_expenses'],
            'explanation' => 'Egresos marcados como San Juan durante el mes.',
            'items' => [
                ['label' => 'Egresos San Juan', 'value' => $summary['san_juan_expenses']],
            ],
        ],
        'san-juan-profit' => [
            'title' => 'Utilidad San Juan',
            'amount' => $summary['san_juan_utility'],
            'explanation' => 'Rentas recibidas menos egresos de San Juan.',
            'items' => [
                ['label' => 'Rentas recibidas', 'value' => $summary['rent_income']],
                ['label' => 'Egresos San Juan', 'value' => -1 * (float) $summary['san_juan_expenses']],
            ],
        ],
    ];
    $selectedIndicator = $indicatorDetails[$detailKey] ?? null;
@endphp

<style>
    .finance-dashboard-grid .dashboard-widget {
        transition: opacity .15s ease, transform .15s ease, width .15s ease;
    }

    .finance-dashboard-grid .dashboard-widget.is-dragging {
        opacity: .45;
        transform: scale(.99);
    }

    .finance-dashboard-grid .dashboard-widget > .card {
        position: relative;
        height: 100%;
    }

    .finance-dashboard-grid .dashboard-widget > .card .stretched-link {
        text-decoration: none;
    }

    .finance-dashboard-grid .dashboard-widget > .card:hover {
        border-color: rgba(34, 185, 86, .35);
    }

    .dashboard-widget-handle {
        align-items: center;
        background: rgba(17, 24, 39, .72);
        border: 1px solid rgba(148, 163, 184, .34);
        border-radius: 6px;
        color: #cbd5e1;
        cursor: grab;
        display: flex;
        height: 28px;
        justify-content: center;
        opacity: 0;
        padding: 0;
        position: absolute;
        right: .75rem;
        top: .75rem;
        transition: opacity .15s ease, background .15s ease;
        width: 28px;
        z-index: 5;
    }

    .dashboard-widget-size-panel {
        align-items: center;
        background: rgba(15, 23, 42, .92);
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 6px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, .22);
        display: none;
        gap: .35rem;
        left: .75rem;
        padding: .35rem;
        position: absolute;
        top: .75rem;
        z-index: 6;
    }

    .dashboard-widget-size-panel .btn {
        min-width: 34px;
    }

    .finance-dashboard-grid.is-layout-editing .dashboard-widget > .card {
        border-color: rgba(34, 185, 86, .28);
        padding-top: 2.4rem;
    }

    .finance-dashboard-grid.is-layout-editing .dashboard-widget-size-panel {
        display: flex;
    }

    .finance-dashboard-grid.is-layout-editing .dashboard-widget .stretched-link {
        pointer-events: none;
    }

    .finance-dashboard-grid.is-layout-editing .dashboard-widget-handle {
        opacity: 1;
    }

    .dashboard-widget-handle:active {
        cursor: grabbing;
    }

    .finance-dashboard-grid .dashboard-widget > .card:hover .dashboard-widget-handle,
    .dashboard-widget-handle:focus {
        opacity: 1;
    }

    @media (hover: none) {
        .dashboard-widget-handle {
            opacity: .78;
        }
    }

    .dashboard-widget-hide {
        align-items: center;
        background: rgba(127, 29, 29, .72);
        border: 1px solid rgba(248, 113, 113, .4);
        border-radius: 6px;
        color: #fecaca;
        cursor: pointer;
        display: none;
        height: 28px;
        justify-content: center;
        padding: 0;
        position: absolute;
        right: 3.4rem;
        top: .75rem;
        transition: background .15s ease;
        width: 28px;
        z-index: 6;
    }

    .dashboard-widget-hide:hover,
    .dashboard-widget-hide:focus {
        background: rgba(153, 27, 27, .92);
        color: #fff;
    }

    .finance-dashboard-grid.is-layout-editing .dashboard-widget-hide {
        display: flex;
    }

    @media (min-width: 1200px) {
        .finance-dashboard-grid.is-smart-layout .dashboard-widget[data-dashboard-smart-width="true"] {
            width: var(--dashboard-smart-width);
        }
    }

    /* Entre tablet y escritorio chico (768–1199px) los cuadros anchos
       (col-xl-5/6/7) no tienen ancho de Bootstrap propio: sin esto se encogen
       al contenido y se descuadran. Una tarjeta por fila en ese rango, igual
       que en móvil (< 768px ya lo resuelve finance-mobile.css). */
    @media (min-width: 768px) and (max-width: 1199.98px) {
        .finance-dashboard-grid .dashboard-widget:not([class*="col-md-"]):not(.col-12) {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .dashboard-widget-size-panel {
            left: .5rem;
            right: .5rem;
            top: .5rem;
        }

        .dashboard-widget-size-panel .btn {
            flex: 1;
        }
    }

    /* Hero de bienvenida: saluda, resume cómo vas y cambia de color según tu mes. */
    .finance-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--hero-soft) !important;
        background: linear-gradient(135deg, var(--hero-soft), transparent 72%);
        animation: financeHeroIn .5s ease both;
    }

    .finance-hero::after {
        content: "";
        position: absolute;
        inset: 0 0 auto auto;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle at top right, var(--hero-soft), transparent 70%);
        pointer-events: none;
    }

    .finance-hero-eyebrow {
        color: var(--hero-accent);
        letter-spacing: .04em;
    }

    .finance-hero-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        border-radius: 14px;
        color: var(--hero-accent);
        background: var(--hero-soft);
        flex: 0 0 auto;
    }

    .finance-hero-pill {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .85rem;
        padding: .6rem .9rem;
        min-width: 9.5rem;
    }

    .finance-hero-pill .finance-hero-pill-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    @keyframes financeHeroIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: none; }
    }

    /* Levante sutil de los cuadros al pasar el cursor (fuera del modo Diseño). */
    .finance-dashboard-grid:not(.is-layout-editing) .dashboard-widget > .card {
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .finance-dashboard-grid:not(.is-layout-editing) .dashboard-widget > .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
    }

    @media (prefers-reduced-motion: reduce) {
        .finance-hero { animation: none; }
        .finance-dashboard-grid:not(.is-layout-editing) .dashboard-widget > .card:hover {
            transform: none;
        }
    }
</style>

@include('finance.partials.flash')

@php
    $heroName = \Illuminate\Support\Str::of(auth()->user()->name ?? '')->trim()->explode(' ')->first() ?: 'por aquí';
    $heroHour = (int) now()->format('G');
    $heroGreeting = $heroHour < 12 ? 'Buenos días' : ($heroHour < 19 ? 'Buenas tardes' : 'Buenas noches');

    $heroLeftover = (float) $summary['expected_leftover'];
    $heroIncome = (float) $summary['total_income'];
    $heroSavings = $heroIncome > 0 ? round($heroLeftover / $heroIncome * 100, 1) : null;
    $heroHasOverdue = $overduePaymentTotal > 0 || $overdueExpectedTotal > 0;

    if ($heroHasOverdue) {
        $hero = ['accent' => '#ef4444', 'soft' => 'rgba(239, 68, 68, .16)', 'icon' => 'triangle-alert',
            'title' => 'Tienes cosas vencidas por registrar',
            'subtitle' => 'Hay pagos o ingresos vencidos. Revísalos para que tus números del mes cuadren.'];
    } elseif ($heroLeftover < 0) {
        $hero = ['accent' => '#f59e0b', 'soft' => 'rgba(245, 158, 11, .16)', 'icon' => 'trending-down',
            'title' => 'Vas gastando más de lo que entró',
            'subtitle' => 'Este mes los egresos superan a los ingresos. Buen momento para frenar un poco.'];
    } elseif ($heroSavings !== null && $heroSavings >= 20) {
        $hero = ['accent' => '#22c55e', 'soft' => 'rgba(34, 197, 94, .16)', 'icon' => 'sparkles',
            'title' => '¡Vas muy bien este mes, ' . $heroName . '!',
            'subtitle' => 'Llevas un ritmo de ahorro saludable. Sigue así.'];
    } else {
        $hero = ['accent' => '#6366f1', 'soft' => 'rgba(99, 102, 241, .16)', 'icon' => 'wallet',
            'title' => 'Vas en orden',
            'subtitle' => 'Tus números del mes están equilibrados. Aquí tienes el panorama.'];
    }

    $heroNextPayment = $summary['next_payments']->firstWhere('is_pending', true);
@endphp

<div class="finance-hero card border-0 mb-3" style="--hero-accent: {{ $hero['accent'] }}; --hero-soft: {{ $hero['soft'] }};">
    <div class="card-body d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="finance-hero-icon">
                <i data-lucide="{{ $hero['icon'] }}" class="fs-24"></i>
            </span>
            <div>
                <div class="finance-hero-eyebrow fw-semibold small mb-1">
                    {{ $heroGreeting }}, {{ $heroName }} · {{ \Illuminate\Support\Str::ucfirst($summary['period_label']) }}
                </div>
                <h4 class="fw-bold mb-1">{{ $hero['title'] }}</h4>
                <p class="mb-0 text-muted">{{ $hero['subtitle'] }}</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="finance-hero-pill">
                <div class="finance-hero-pill-label text-muted">Proyectado del mes</div>
                <div class="fw-bold fs-5 {{ $heroLeftover >= 0 ? 'text-success' : 'text-danger' }}"
                     data-countup="{{ $heroLeftover }}" data-countup-prefix="$" data-countup-decimals="2">{{ $money($heroLeftover) }}</div>
            </div>
            <a href="{{ route('finance.pending.index') }}" class="finance-hero-pill text-decoration-none">
                <div class="finance-hero-pill-label text-muted">Pendientes</div>
                <div class="fw-bold fs-5 {{ $pendingSummary['total'] > 0 ? 'text-warning' : 'text-success' }}"
                     data-countup="{{ $pendingSummary['total'] }}" data-countup-decimals="0">{{ number_format($pendingSummary['total']) }}</div>
            </a>
            <div class="finance-hero-pill">
                <div class="finance-hero-pill-label text-muted">Próximo pago</div>
                @if ($heroNextPayment)
                    <div class="fw-bold fs-5">{{ $money($heroNextPayment['amount_due']) }}</div>
                    <div class="text-muted small text-truncate" style="max-width: 11rem;">
                        {{ $heroNextPayment['name'] }}@if ($heroNextPayment['due_date']) · {{ $heroNextPayment['due_date']->translatedFormat('d M') }}@endif
                    </div>
                @else
                    <div class="fw-bold fs-5 text-success">—</div>
                    <div class="text-muted small">Sin pagos próximos</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Finanzas</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.dashboard') }}" class="d-flex justify-content-md-end gap-2 flex-wrap">
            <button class="btn btn-outline-secondary" type="button" id="toggleDashboardLayout" title="Editar diseño" aria-pressed="false">
                <i data-lucide="layout-grid" class="me-1"></i>Diseño
            </button>
            <button class="btn btn-outline-secondary" type="button" id="toggleDashboardAutoLayout" title="Ajuste inteligente" aria-pressed="true">
                <i data-lucide="wand-sparkles" class="me-1"></i>Auto ajuste
            </button>
            <button class="btn btn-outline-secondary" type="button" id="resetDashboardOrder" title="Restablecer a distribución de fábrica">
                <i data-lucide="rotate-ccw"></i>
            </button>
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $summary['month_value'] }}">
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="calendar-search" class="me-1"></i>Ver
            </button>
        </form>
    </div>
</div>

@if ($overduePaymentTotal > 0 || $overdueExpectedTotal > 0)
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h5 class="alert-heading mb-1">
                    <i data-lucide="triangle-alert" class="me-1"></i>Atención: hay obligaciones vencidas por registrar
                </h5>
                <div>
                    @if ($overduePaymentTotal > 0)
                        <span class="me-3">Pagos vencidos: <strong>{{ $money($overduePaymentTotal) }}</strong> en {{ $overduePaymentCount }} pendiente(s).</span>
                    @endif
                    @if ($overdueExpectedTotal > 0)
                        <span>Ingresos vencidos: <strong>{{ $money($overdueExpectedTotal) }}</strong> en {{ $overdueIncomeCount }} pendiente(s).</span>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('finance.planned.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-light">
                    <i data-lucide="calendar-check" class="me-1"></i>Revisar pagos
                </a>
                <a href="{{ route('finance.expected-incomes.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-light">
                    <i data-lucide="calendar-plus" class="me-1"></i>Revisar ingresos
                </a>
            </div>
        </div>
    </div>
@endif

<div class="row g-3 justify-content-center finance-dashboard-grid" id="financeDashboardGrid"
     data-save-url="{{ route('finance.dashboard.layout') }}"
     data-csrf="{{ csrf_token() }}"
     data-server-layout='@json($dashboardLayout)'>
    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="income-real">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Ingresos reales</p>
                    <h4 class="fw-bold mb-0 text-success">{{ $money($summary['total_income']) }}</h4>
                    <small class="text-muted">Rendimientos: {{ $money($summary['yields']) }}</small>
                </div>
                <i data-lucide="arrow-down-circle" class="fs-32 text-success"></i>
            </div>
            <a href="{{ $dashboardDetailUrl('income-real') }}" class="stretched-link" aria-label="Ver detalle de ingresos reales"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="projected-income">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Ingresos proyectados</p>
                    <h4 class="fw-bold mb-0 text-success">{{ $money($summary['projected_total_income']) }}</h4>
                    <small class="text-muted">Recibidos + esperados pendientes</small>
                </div>
                <i data-lucide="trending-up" class="fs-32 text-success"></i>
            </div>
            <a href="{{ $dashboardDetailUrl('projected-income') }}" class="stretched-link" aria-label="Ver detalle de ingresos proyectados"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="expenses-real">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Egresos reales</p>
                    <h4 class="fw-bold mb-0 text-danger">{{ $money($summary['expenses']) }}</h4>
                    <small class="text-muted">?: {{ $money($summary['unknown_expenses']) }}</small>
                </div>
                <i data-lucide="arrow-up-circle" class="fs-32 text-danger"></i>
            </div>
            <a href="{{ $dashboardDetailUrl('expenses-real') }}" class="stretched-link" aria-label="Ver detalle de egresos reales"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="expected-leftover">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Saldo proyectado antes de obligaciones</p>
                    <h4 class="fw-bold mb-0">{{ $money($summary['expected_leftover']) }}</h4>
                    <small class="text-muted">Ingresos - egresos</small>
                </div>
                <i data-lucide="scale" class="fs-32 text-primary"></i>
            </div>
            <a href="{{ $dashboardDetailUrl('expected-leftover') }}" class="stretched-link" aria-label="Ver detalle del saldo proyectado"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="real-total-cut">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Saldo real del corte</p>
                    <h4 class="fw-bold mb-0">{{ $summary['latest_cut'] ? $money($summary['real_total']) : '-' }}</h4>
                    <small class="{{ $isBalanced ? 'text-success' : 'text-danger' }}">
                        {{ $summary['latest_cut'] ? ($isBalanced ? 'Cuadra' : 'Revisar') : 'Sin corte' }}
                    </small>
                </div>
                <i data-lucide="wallet" class="fs-32 text-primary"></i>
            </div>
            <a href="{{ $dashboardDetailUrl('real-total-cut') }}" class="stretched-link" aria-label="Ver detalle del saldo real del corte"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="cut-difference">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Diferencia de conciliación</p>
                <h4 class="fw-bold mb-0 {{ $difference === null ? '' : ($isBalanced ? 'text-success' : 'text-danger') }}">
                    {{ $difference === null ? '-' : $money($difference) }}
                </h4>
            </div>
            <a href="{{ $dashboardDetailUrl('cut-difference') }}" class="stretched-link" aria-label="Ver detalle de la diferencia de conciliación"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="amount-missing">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Saldo disponible después de obligaciones</p>
                <h4 class="fw-bold mb-0 {{ ($summary['amount_missing'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                    {{ $summary['amount_missing'] === null ? '-' : $money($summary['amount_missing']) }}
                </h4>
            </div>
            <a href="{{ $dashboardDetailUrl('amount-missing') }}" class="stretched-link" aria-label="Ver detalle del saldo disponible después de obligaciones"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="san-juan-expenses">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Egresos San Juan</p>
                <h4 class="fw-bold mb-0 text-danger">{{ $money($summary['san_juan_expenses']) }}</h4>
            </div>
            <a href="{{ $dashboardDetailUrl('san-juan-expenses') }}" class="stretched-link" aria-label="Ver detalle de egresos San Juan"></a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="san-juan-profit">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Utilidad San Juan</p>
                <h4 class="fw-bold mb-0 {{ $summary['san_juan_utility'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($summary['san_juan_utility']) }}</h4>
            </div>
            <a href="{{ $dashboardDetailUrl('san-juan-profit') }}" class="stretched-link" aria-label="Ver detalle de utilidad San Juan"></a>
        </div>
    </div>

    @if ($creditLine['has_limits'])
        <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="credit-available">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-2 card-title">Crédito disponible</p>
                        <h4 class="fw-bold mb-0 text-info">{{ $money($creditLine['available']) }}</h4>
                        <small class="text-muted">Usado {{ $money($creditLine['used']) }} de {{ $money($creditLine['limit']) }}</small>
                    </div>
                    <i data-lucide="credit-card" class="fs-32 text-info"></i>
                </div>
                <a href="{{ route('finance.credits.index') }}" class="stretched-link" aria-label="Ver créditos y tarjetas"></a>
            </div>
        </div>
    @endif

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="pending-summary">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Pendientes por resolver</p>
                    <h4 class="fw-bold mb-0 {{ $pendingSummary['total'] > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($pendingSummary['total']) }}</h4>
                    <small class="text-muted">{{ $pendingSummary['total'] > 0 ? 'Cosas por revisar' : 'Todo al día' }}</small>
                </div>
                <i data-lucide="list-checks" class="fs-32 {{ $pendingSummary['total'] > 0 ? 'text-warning' : 'text-success' }}"></i>
            </div>
            <a href="{{ route('finance.pending.index') }}" class="stretched-link" aria-label="Ver pendientes por resolver"></a>
        </div>
    </div>

    @php
        $savingsRate = $summary['total_income'] > 0
            ? round($summary['expected_leftover'] / $summary['total_income'] * 100, 1)
            : null;
        $savingsPositive = ($savingsRate ?? 0) >= 0;
    @endphp
    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="savings-rate">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Tasa de ahorro del mes</p>
                <h4 class="fw-bold mb-1 {{ $savingsPositive ? 'text-success' : 'text-danger' }}">{{ $savingsRate === null ? '—' : $savingsRate . '%' }}</h4>
                <div class="progress mb-1" style="height: 6px;">
                    <div class="progress-bar {{ $savingsPositive ? 'bg-success' : 'bg-danger' }}" role="progressbar"
                         style="width: {{ max(0, min(100, $savingsRate ?? 0)) }}%"></div>
                </div>
                <small class="text-muted">De lo que entró, esto te queda</small>
            </div>
        </div>
    </div>

    @php
        $cmpFormat = function (?float $change, bool $upIsGood) {
            if ($change === null) {
                return ['icon' => 'minus', 'class' => 'text-muted', 'text' => 'sin mes previo'];
            }

            $up = $change > 0;
            $flat = abs($change) < 0.05;
            $good = $flat ? true : ($up === $upIsGood);

            return [
                'icon' => $flat ? 'minus' : ($up ? 'arrow-up-right' : 'arrow-down-right'),
                'class' => $flat ? 'text-muted' : ($good ? 'text-success' : 'text-danger'),
                'text' => ($up ? '+' : '') . $change . '%',
            ];
        };
        $incomeCmp = $cmpFormat($monthComparison['income_change'], true);
        $expenseCmp = $cmpFormat($monthComparison['expenses_change'], false);
    @endphp
    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="month-comparison">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Vs {{ $monthComparison['previous_label'] }}</p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Ingresos</span>
                    <span class="fw-semibold {{ $incomeCmp['class'] }}">
                        <i data-lucide="{{ $incomeCmp['icon'] }}" class="fs-14"></i> {{ $incomeCmp['text'] }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Egresos</span>
                    <span class="fw-semibold {{ $expenseCmp['class'] }}">
                        <i data-lucide="{{ $expenseCmp['icon'] }}" class="fs-14"></i> {{ $expenseCmp['text'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if ($selectedIndicator)
        <div class="col-12 dashboard-widget" data-dashboard-widget="indicator-detail" id="indicator-detail">
            <div class="card border-primary">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h4 class="card-title mb-1">Detalle del indicador: {{ $selectedIndicator['title'] }}</h4>
                        <p class="text-muted mb-0">{{ $selectedIndicator['explanation'] }}</p>
                    </div>
                    <span class="badge badge-soft-primary fs-6">{{ $selectedIndicator['amount'] === null ? '-' : $money($selectedIndicator['amount']) }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($selectedIndicator['items'] ?? [] as $item)
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <p class="text-muted mb-1">{{ $item['label'] }}</p>
                                    <h5 class="fw-semibold mb-0 {{ (float) $item['value'] < 0 ? 'text-danger' : '' }}">{{ $money($item['value']) }}</h5>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if (! empty($selectedIndicator['movements']) && $selectedIndicator['movements']->isNotEmpty())
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Movimiento</th>
                                        <th>Cuenta</th>
                                        <th>Categoría</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedIndicator['movements'] as $movement)
                                        <tr>
                                            <td>{{ $movement->happened_on->format('Y-m-d') }}</td>
                                            <td>{{ $movement->description }}</td>
                                            <td>{{ $movement->account?->name ?? '-' }}</td>
                                            <td>{{ $movement->category?->name ?? '-' }}</td>
                                            <td class="text-end {{ $movement->movement_type === 'expense' ? 'text-danger' : 'text-success' }}">{{ $money($movement->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (! empty($selectedIndicator['expected_incomes']) && $selectedIndicator['expected_incomes']->isNotEmpty())
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Ingreso esperado</th>
                                        <th>Concepto</th>
                                        <th>Estado</th>
                                        <th class="text-end">Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedIndicator['expected_incomes']->take(10) as $income)
                                        <tr>
                                            <td>{{ $income['due_date']?->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ $income['name'] }}</td>
                                            <td>{{ $income['concept'] }}</td>
                                            <td>
                                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($income['due_date'], $income['status']) }}">
                                                    {{ \App\Support\FinanceLabels::dueLabel($income['due_date'], $income['status']) }}
                                                </span>
                                            </td>
                                            <td class="text-end text-success">{{ $money($income['amount_due']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (! empty($selectedIndicator['obligations']) && $selectedIndicator['obligations']->isNotEmpty())
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Vence</th>
                                        <th>Obligación</th>
                                        <th>Origen</th>
                                        <th>Estado</th>
                                        <th class="text-end">Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedIndicator['obligations']->take(10) as $obligation)
                                        <tr>
                                            <td>{{ $obligation['due_date']?->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ $obligation['name'] }}</td>
                                            <td>{{ $obligation['origin'] }}</td>
                                            <td>
                                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($obligation['due_date'], $obligation['status']) }}">
                                                    {{ \App\Support\FinanceLabels::dueLabel($obligation['due_date'], $obligation['status']) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $money($obligation['amount_due']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="col-12 dashboard-widget" data-dashboard-widget="payment-obligations-summary">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Obligaciones del mes</h4>
                <a href="{{ route('finance.planned.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="clipboard-list" class="me-1"></i>Flujo
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <p class="text-muted mb-1">Pendiente por pagar este mes</p>
                            <h5 class="fw-bold mb-0 text-warning">{{ $money($summary['obligation_totals']['pending'] ?? 0) }}</h5>
                        </div>
                    </div>
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <p class="text-muted mb-1">Pagado este mes</p>
                            <h5 class="fw-bold mb-0 text-success">{{ $money($summary['obligation_totals']['paid'] ?? 0) }}</h5>
                        </div>
                    </div>
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <p class="text-muted mb-1">Vencido pendiente</p>
                            <h5 class="fw-bold mb-0 text-danger">{{ $money($summary['obligation_totals']['overdue'] ?? 0) }}</h5>
                        </div>
                    </div>
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <p class="text-muted mb-1">Créditos del mes</p>
                            <h5 class="fw-bold mb-0">{{ $money($summary['obligation_totals']['credits'] ?? 0) }}</h5>
                        </div>
                    </div>
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <p class="text-muted mb-1">Pagos planeados del mes</p>
                            <h5 class="fw-bold mb-0">{{ $money($summary['obligation_totals']['planned'] ?? 0) }}</h5>
                        </div>
                    </div>
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="border rounded p-3 h-100">
                            <p class="text-muted mb-1">Obligaciones no pagadas / pendientes de decisión</p>
                            <h5 class="fw-bold mb-0 text-danger">{{ $money($summary['obligation_totals']['skipped'] ?? 0) }}</h5>
                        </div>
                    </div>
                </div>

                @if ($summary['skipped_obligations']->isNotEmpty())
                    <div class="alert alert-warning mt-3 mb-0">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-2">
                            <div>
                                <strong>Obligaciones no pagadas / pendientes de decisión</strong>
                                <div class="small">No se suman como pendiente normal ni como pagado. Quedan visibles para que decidas si se reprograman, se eliminan o se registran después.</div>
                            </div>
                            <span class="badge badge-soft-danger">{{ $money($summary['obligation_totals']['skipped'] ?? 0) }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Obligación</th>
                                        <th>Origen</th>
                                        <th>Estado</th>
                                        <th class="text-end">Monto original</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($summary['skipped_obligations']->take(8) as $obligation)
                                        <tr>
                                            <td>{{ $obligation['due_date']?->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ $obligation['name'] }}</td>
                                            <td>{{ $obligation['origin'] }}</td>
                                            <td><span class="badge badge-soft-danger">No pagado / pendiente de decisión</span></td>
                                            <td class="text-end">{{ $money($obligation['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 dashboard-widget" data-dashboard-widget="security-backups">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Seguridad de datos</h4>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
                    <div class="d-flex flex-column flex-md-row gap-2">
                        <form method="POST" action="{{ route('finance.security.backups.database') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                <i data-lucide="database-backup" class="me-1"></i>Backup solo BD
                            </button>
                        </form>
                        <form method="POST" action="{{ route('finance.security.backups.full') }}" class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">
                                <i data-lucide="archive" class="me-1"></i>Backup completo
                            </button>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" value="1" name="include_env" id="backup-include-env">
                                <label class="form-check-label" for="backup-include-env">Incluir .env</label>
                            </div>
                        </form>
                    </div>
                    <small class="text-warning">.env contiene credenciales. Inclúyelo solo si necesitas restaurar el sistema completo.</small>
                </div>

                @php
                    $backupDownload = session('backup_download');
                @endphp

                @if ($backupDownload)
                    <div class="alert alert-primary d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-3 mb-0">
                        <span>Backup listo: {{ $backupDownload['name'] }}</span>
                        <a class="btn btn-sm btn-primary" href="{{ route('finance.security.backups.download', ['type' => $backupDownload['type'], 'filename' => $backupDownload['name']]) }}">
                            <i data-lucide="download" class="me-1"></i>Descargar
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-6 dashboard-widget" data-dashboard-widget="upcoming-reminders">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Próximos recordatorios</h4>
                <a href="{{ route('finance.reminders.index') }}" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="bell-ring" class="me-1"></i>Gestionar
                </a>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="border rounded p-2 h-100">
                            <small class="text-muted d-block">Pendientes</small>
                            <strong>{{ $reminderSummary['pending_total'] }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 h-100">
                            <small class="text-muted d-block">Vencidos</small>
                            <strong class="text-danger">{{ $reminderSummary['overdue_total'] }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 h-100">
                            <small class="text-muted d-block">En aviso</small>
                            <strong class="text-warning">{{ $reminderSummary['soon_total'] }}</strong>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Recordatorio</th>
                                <th>Tipo</th>
                                <th>Pronto aviso</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reminderSummary['upcoming'] as $reminder)
                                <tr>
                                    <td>{{ $reminder->due_date->format('Y-m-d') }}</td>
                                    <td>
                                        {{ $reminder->title }}
                                        @if ($reminder->vehicle_type)
                                            <span class="badge badge-soft-secondary ms-1">{{ $reminderVehicles[$reminder->vehicle_type] ?? $reminder->vehicle_type }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $reminderTypes[$reminder->reminder_type] ?? $reminder->reminder_type }}</td>
                                    <td>
                                        <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($reminder->due_date, $reminder->status) }}">
                                            {{ \App\Support\FinanceLabels::dueLabel($reminder->due_date, $reminder->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $reminder->amount !== null ? $money($reminder->amount) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Sin recordatorios pendientes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 dashboard-widget" data-dashboard-widget="cut-balances">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Resumen de saldos del corte</h4>
                @if ($summary['latest_cut'])
                    <span class="badge badge-soft-primary">{{ $summary['latest_cut']->cut_date->format('Y-m-d') }}</span>
                @endif
            </div>
            <div class="card-body">
                @if ($summary['latest_cut'])
                    @php
                        $cutBalances = $summary['latest_cut']->balances
                            ->sortBy(fn ($balance) => str_pad((string) ($balance->account?->display_order ?? 999), 4, '0', STR_PAD_LEFT) . ($balance->account?->name ?? ''));
                    @endphp
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">En tarjetas tienes</p>
                                <h4 class="fw-bold mb-0">{{ $money($summary['latest_cut']->cards_amount) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">En efectivo tienes</p>
                                <h4 class="fw-bold mb-0">{{ $money($summary['latest_cut']->cash_amount) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Total real tienes</p>
                                <h4 class="fw-bold mb-0">{{ $money($summary['latest_cut']->real_total) }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Cuenta</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Saldo real</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cutBalances as $balance)
                                    <tr>
                                        <td>{{ $balance->account?->name ?? 'Sin cuenta' }}</td>
                                        <td>
                                            <span class="badge {{ $balance->account?->type === 'cash' ? 'badge-soft-success' : 'badge-soft-primary' }}">
                                                {{ $balance->account?->type === 'cash' ? 'Efectivo' : 'Tarjeta' }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ $money($balance->balance) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Sin corte diario para mostrar saldos.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-6 dashboard-widget" data-dashboard-widget="next-expected-incomes">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Próximos ingresos</h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-soft-success">{{ $money($summary['pending_expected_income']) }}</span>
                    <a href="{{ route('finance.expected-incomes.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-outline-primary">
                        <i data-lucide="calendar-plus" class="me-1"></i>Agregar
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Persona</th>
                                <th>Concepto</th>
                                <th>Pronto cobro</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Cobrar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary['next_expected_incomes']->take(8) as $income)
                                <tr>
                                    <td>{{ $income['due_date']?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $income['name'] }}</td>
                                    <td>
                                        {{ $income['concept'] }}
                                        @if (($income['payment_count'] ?? 0) > 0)
                                            <div class="text-muted small">
                                                Recibido: {{ $money($income['received_amount'] ?? 0) }} · {{ $income['payment_count'] }} abono(s)
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($income['due_date'], $income['status']) }}">
                                            {{ \App\Support\FinanceLabels::dueLabel($income['due_date'], $income['status']) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-success">{{ $money($income['amount_due']) }}</td>
                                    <td class="text-end">
                                        @if (($income['source'] ?? null) === 'manual')
                                            <form method="POST" action="{{ route('finance.expected-incomes.received', $income['id']) }}" class="d-flex flex-column flex-xxl-row align-items-end justify-content-end gap-1">
                                                @csrf
                                                <select name="account_id" class="form-select form-select-sm" style="width: 135px;" title="Cuenta destino">
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected(($income['account_id'] ?? $defaultIncomeAccount?->id) === $account->id)>{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="number" name="amount" class="form-control form-control-sm text-end" style="width: 115px;" step="0.01" min="0.01" value="{{ $income['amount_due'] }}" title="Monto recibido">
                                                <input type="date" name="received_on" class="form-control form-control-sm" style="width: 145px;" value="{{ $income['due_date']?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de cobro">
                                                <button type="submit" class="btn btn-sm btn-success" title="Recibido y crear movimiento">
                                                    <i data-lucide="check"></i>
                                                </button>
                                            </form>
                                        @elseif (($income['source'] ?? null) === 'rental-contract')
                                            <form method="POST" action="{{ route('finance.san-juan.rentals.received', $income['contract_id']) }}" class="d-flex flex-column flex-xxl-row align-items-end justify-content-end gap-1">
                                                @csrf
                                                <input type="hidden" name="month" value="{{ $summary['month_value'] }}">
                                                <select name="account_id" class="form-select form-select-sm" style="width: 135px;" title="Cuenta destino">
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected($defaultIncomeAccount && $defaultIncomeAccount->id === $account->id)>{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="number" name="amount" class="form-control form-control-sm text-end" style="width: 115px;" step="0.01" min="0.01" value="{{ $income['amount_due'] }}" title="Monto recibido">
                                                <input type="date" name="received_on" class="form-control form-control-sm" style="width: 145px;" value="{{ $income['due_date']?->format('Y-m-d') ?? now()->toDateString() }}" title="Fecha real de cobro">
                                                <button type="submit" class="btn btn-sm btn-success" title="Cobrar renta y crear movimiento">
                                                    <i data-lucide="check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Sin ingresos pendientes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 dashboard-widget" data-dashboard-widget="next-payments">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Próximos pagos</h4>
                <a href="{{ route('finance.planned.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="clipboard-list" class="me-1"></i>Flujo
                </a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                    <span>Total pendiente</span>
                    <strong>{{ $money($summary['pending_payments']) }}</strong>
                </div>
                @php
                    $nextPaymentGroups = $summary['next_payments']->groupBy('source');
                    $nextPaymentSections = [
                        'planned' => ['label' => 'Pagos planeados', 'class' => 'badge-soft-primary'],
                        'credit' => ['label' => 'Créditos / tarjetas', 'class' => 'badge-soft-warning'],
                    ];
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Vence</th>
                                <th>Pago</th>
                                <th>Origen</th>
                                <th>Pronto pago</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($nextPaymentSections as $source => $section)
                                @php
                                    $sectionRows = $nextPaymentGroups->get($source, collect());
                                @endphp
                                @if ($sectionRows->isNotEmpty())
                                    <tr>
                                        <td colspan="5" class="bg-body-tertiary text-muted fw-semibold small text-uppercase">
                                            <span class="badge {{ $section['class'] }} me-1">{{ $sectionRows->count() }}</span>{{ $section['label'] }}
                                        </td>
                                    </tr>
                                    @foreach ($sectionRows as $payment)
                                        <tr class="{{ $source === 'credit' ? 'table-active' : '' }}">
                                            <td>{{ $payment['due_date']?->format('Y-m-d') ?? '-' }}</td>
                                            <td>
                                                <div class="d-flex align-items-start gap-2">
                                                    @if ($source === 'credit')
                                                        <i data-lucide="corner-down-right" class="text-warning mt-1"></i>
                                                    @endif
                                                    <div>
                                                        {{ $payment['name'] }}
                                                        <span class="badge {{ $source === 'credit' ? 'badge-soft-warning' : 'badge-soft-primary' }} ms-1">{{ $payment['kind'] }}</span>
                                                        @if ($source === 'credit')
                                                            <div class="ms-2 mt-1 ps-2 border-start border-warning text-muted small">
                                                                Compra a meses: {{ $payment['credit_name'] ?? 'Crédito' }}<br>
                                                                {{ $payment['detail'] }}
                                                                @if (! empty($payment['account']) || ! empty($payment['category']))
                                                                    <br>{{ $payment['account'] ?? 'Sin cuenta' }} · {{ $payment['category'] ?? 'Sin categoría' }}
                                                                @endif
                                                                <br>Saldo real del crédito: {{ $money($payment['credit_balance_due'] ?? $payment['amount_due']) }}
                                                                @if (($payment['credit_free_paid'] ?? 0) > 0)
                                                                    <br>Abonos libres aplicados: {{ $money($payment['credit_free_paid']) }}
                                                                @endif
                                                            </div>
                                                        @elseif (! empty($payment['detail']))
                                                            <div class="text-muted small">{{ $payment['detail'] }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge {{ in_array(($payment['status'] ?? null), ['overdue', 'skipped'], true) ? 'badge-soft-danger' : 'badge-soft-primary' }}">
                                                    {{ $payment['origin'] ?? 'Pago' }}
                                                </span>
                                                <div class="text-muted small">{{ $payment['origin_detail'] ?? 'Pendiente' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($payment['due_date'], $payment['status']) }}">
                                                    {{ \App\Support\FinanceLabels::dueLabel($payment['due_date'], $payment['status']) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $money($payment['is_skipped'] ? $payment['amount'] : $payment['amount_due']) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            @if ($summary['next_payments']->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Sin pagos pendientes</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 dashboard-widget" data-dashboard-widget="daily-income-chart">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Ingresos acumulados del mes</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Acumulado</span>
                    <strong>{{ $money(end($dailyValues)) }}</strong>
                </div>
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-100" role="img" aria-label="Ingresos acumulados del mes">
                    <line x1="{{ $chartLeft }}" y1="{{ $chartBottom }}" x2="{{ $chartWidth - $chartLeft }}" y2="{{ $chartBottom }}" stroke="currentColor" opacity=".18" />
                    <line x1="{{ $chartLeft }}" y1="{{ $chartTop }}" x2="{{ $chartLeft }}" y2="{{ $chartBottom }}" stroke="currentColor" opacity=".18" />
                    <polyline points="{{ $dailyPoints }}" fill="none" stroke="#22b956" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    @foreach ($dailyValues as $index => $value)
                        @if ($value > 0 && ($index === 0 || $index === count($dailyValues) - 1 || $index % 5 === 0))
                            @php
                                $x = $chartLeft + (($index / $dailyCount) * $chartInnerWidth);
                                $y = $chartBottom - (((float) $value / $dailyChart['max']) * $chartInnerHeight);
                            @endphp
                            <circle cx="{{ round($x, 2) }}" cy="{{ round($y, 2) }}" r="4" fill="#22b956" />
                        @endif
                    @endforeach
                    <text x="{{ $chartLeft }}" y="172" fill="currentColor" opacity=".65" font-size="13">Día 1</text>
                    <text x="{{ $chartWidth - 72 }}" y="172" fill="currentColor" opacity=".65" font-size="13">Día {{ count($dailyValues) }}</text>
                </svg>
            </div>
        </div>
    </div>

    <div class="col-xl-6 dashboard-widget" data-dashboard-widget="monthly-income-chart">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Ingresos por mes</h4>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-2" style="height: 190px;">
                    @foreach ($monthlyChart['values'] as $index => $value)
                        @php
                            $height = max(6, ((float) $value / $monthlyChart['max']) * 140);
                        @endphp
                        <div class="flex-fill text-center">
                            <div class="d-flex align-items-end justify-content-center" style="height: 148px;">
                                <div class="rounded-top bg-success" style="width: 100%; max-width: 42px; height: {{ round($height, 2) }}px;"></div>
                            </div>
                            <small class="d-block text-muted mt-2">{{ $monthlyChart['labels'][$index] }}</small>
                            <small class="d-block fw-semibold">{{ $money($value) }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7 dashboard-widget" data-dashboard-widget="new-movement">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Nuevo movimiento</h4>
                <a href="{{ route('finance.movements.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="list" class="me-1"></i>Ver todos
                </a>
            </div>
            <div class="card-body">
                @include('finance.partials.movement-form')
            </div>
        </div>
    </div>

    <div class="col-xl-5 dashboard-widget" data-dashboard-widget="daily-cut">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Corte diario</h4>
                <a href="{{ route('finance.cuts.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="history" class="me-1"></i>Cortes
                </a>
            </div>
            <div class="card-body">
                @include('finance.partials.cut-form')
            </div>
        </div>
    </div>

    <div class="col-xl-7 dashboard-widget" data-dashboard-widget="recent-movements">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Últimos movimientos</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary['recent_movements'] as $movement)
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
                                    <td>{{ $movement->category?->name ?? '-' }}</td>
                                    <td class="text-end {{ $movement->movement_type === 'expense' ? 'text-danger' : 'text-success' }}">{{ $money($movement->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sin movimientos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5 dashboard-widget" data-dashboard-widget="expenses-by-category">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Egresos por categoría</h4>
            </div>
            <div class="card-body">
                @forelse ($summary['expenses_by_category']->take(6) as $category)
                    @php
                        $percent = $summary['expenses'] > 0 ? min(100, (($category['amount'] / $summary['expenses']) * 100)) : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $category['name'] }}</span>
                            <strong>{{ $money($category['amount']) }}</strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin egresos</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-7 dashboard-widget" data-dashboard-widget="spending-opportunities">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Oportunidades de mejora</h4>
                <a href="{{ route('finance.reports.index', ['month' => $summary['month_value']]) }}" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="pie-chart" class="me-1"></i>Reportes
                </a>
            </div>
            <div class="card-body">
                @forelse ($summary['spending_opportunities'] as $opportunity)
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background: {{ $opportunity['color'] }}"></span>
                                <strong>{{ $opportunity['name'] }}</strong>
                                <span class="badge badge-soft-secondary">{{ $opportunity['count'] }} movs</span>
                            </div>
                            <div class="text-end">
                                <strong class="text-danger">{{ $money($opportunity['amount']) }}</strong>
                                <div class="small text-muted">{{ $opportunity['percentage'] }}% de tus egresos</div>
                            </div>
                        </div>
                        <div class="small text-muted">{{ $opportunity['suggestion'] }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin egresos suficientes para detectar focos de gasto este mes.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-5 dashboard-widget" data-dashboard-widget="gasoline">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Gasolina</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                    <span>Total gasolina</span>
                    <strong>{{ $money($summary['gasoline_expenses']) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Carro</span>
                    <strong>{{ $money($summary['car_gasoline_expenses']) }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Moto</span>
                    <strong>{{ $money($summary['motorcycle_gasoline_expenses']) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const grid = document.getElementById('financeDashboardGrid');

        if (!grid) {
            return;
        }

        const saveUrl = grid.dataset.saveUrl || '';
        const csrfToken = grid.dataset.csrf || '';
        const resetButton = document.getElementById('resetDashboardOrder');
        const layoutButton = document.getElementById('toggleDashboardLayout');
        const autoLayoutButton = document.getElementById('toggleDashboardAutoLayout');
        let draggedWidget = null;
        let layoutEditing = false;

        // Distribución guardada en el servidor (orden, tamaños, ocultos,
        // auto-ajuste). Es la fuente de verdad: te sigue en cualquier equipo y no
        // se borra al limpiar el caché del navegador.
        let layout = { order: [], sizes: {}, hidden: [], autoLayout: true };
        try {
            const parsed = JSON.parse(grid.dataset.serverLayout || 'null');
            if (parsed && typeof parsed === 'object') {
                layout.order = Array.isArray(parsed.order) ? parsed.order : [];
                layout.sizes = (parsed.sizes && typeof parsed.sizes === 'object') ? parsed.sizes : {};
                layout.hidden = Array.isArray(parsed.hidden) ? parsed.hidden : [];
                layout.autoLayout = parsed.autoLayout !== false;
            }
        } catch (error) {
            layout = { order: [], sizes: {}, hidden: [], autoLayout: true };
        }

        let persistTimer = null;
        let pendingSave = false;

        // Envía la distribución al servidor. `keepalive` permite que el POST
        // sobreviva a un F5 o a cerrar la pestaña (se usa al vaciar pendientes).
        const sendLayout = (keepalive) => {
            pendingSave = false;
            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                keepalive: !!keepalive,
                body: JSON.stringify({ layout: layout }),
            }).then((response) => {
                // Si el guardado falla (p. ej. 419 sesión/CSRF caducada) dejamos
                // la marca de pendiente para reintentar al salir, y avisamos.
                if (!response.ok) {
                    pendingSave = true;
                    console.warn('No se pudo guardar la distribución del Resumen (HTTP ' + response.status + ').');
                }
            }).catch(() => {
                pendingSave = true;
            });
        };

        const persistLayout = (immediate) => {
            if (!saveUrl) {
                return;
            }

            clearTimeout(persistTimer);
            pendingSave = true;

            if (immediate) {
                sendLayout(false);
            } else {
                persistTimer = setTimeout(() => sendLayout(false), 400);
            }
        };

        // Vacía cualquier guardado pendiente antes de que la página se descargue
        // (F5, navegar, cerrar pestaña). Sin esto, cambiar un tamaño y recargar
        // rápido perdía el cambio porque el guardado iba con retardo de 400 ms.
        const flushLayout = () => {
            if (!saveUrl || !pendingSave) {
                return;
            }

            clearTimeout(persistTimer);
            sendLayout(true);
        };

        window.addEventListener('pagehide', flushLayout);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                flushLayout();
            }
        });

        let smartLayoutEnabled = layout.autoLayout !== false;
        const sizeOptions = {
            4: { label: '4', classes: ['col-xl-3', 'col-md-6'] },
            3: { label: '3', classes: ['col-xl-4', 'col-md-6'] },
            2: { label: '2', classes: ['col-xl-6', 'col-md-6'] },
            1: { label: '1', classes: ['col-12'] },
        };
        const managedClasses = ['col-xl-3', 'col-xl-4', 'col-xl-5', 'col-xl-6', 'col-xl-7', 'col-12', 'col-md-6'];

        const widgets = () => Array.from(grid.querySelectorAll('[data-dashboard-widget]'));
        const resizableWidgets = () => widgets();

        const classColumnsFor = (widget) => {
            if (widget.classList.contains('col-12')) {
                return 12;
            }

            if (widget.classList.contains('col-xl-7')) {
                return 7;
            }

            if (widget.classList.contains('col-xl-6')) {
                return 6;
            }

            if (widget.classList.contains('col-xl-5')) {
                return 5;
            }

            if (widget.classList.contains('col-xl-4')) {
                return 4;
            }

            return 3;
        };

        const saveOrder = () => {
            layout.order = widgets().map((widget) => widget.dataset.dashboardWidget);
            persistLayout();
        };

        // --- Cuadros ocultos -------------------------------------------------
        const readHidden = () => (Array.isArray(layout.hidden) ? layout.hidden : []);

        const saveHidden = (ids) => {
            layout.hidden = ids;
            persistLayout(true);
        };

        const isHidden = (widget) => widget.classList.contains('dashboard-widget-hidden');

        const widgetLabel = (widget) => {
            const title = widget.querySelector('.card-title');
            const text = title ? title.textContent.trim() : '';

            return text || widget.dataset.dashboardWidget;
        };

        // Bandeja con los cuadros ocultos (solo visible en modo Diseño).
        const hiddenTray = document.createElement('div');
        hiddenTray.id = 'dashboardHiddenTray';
        hiddenTray.className = 'mb-3';
        hiddenTray.style.display = 'none';
        hiddenTray.innerHTML = '<div class="card border-secondary border-opacity-50 mb-0">'
            + '<div class="card-body py-2">'
            + '<div class="d-flex align-items-center flex-wrap gap-2">'
            + '<span class="text-muted small"><i data-lucide="eye-off" class="fs-14 me-1"></i>Cuadros ocultos (toca para restaurar):</span>'
            + '<span data-hidden-chips class="d-flex flex-wrap gap-2"></span>'
            + '<button type="button" id="restoreAllHidden" class="btn btn-sm btn-outline-secondary ms-auto">Mostrar todos</button>'
            + '</div></div></div>';
        grid.parentNode.insertBefore(hiddenTray, grid);
        const hiddenChips = hiddenTray.querySelector('[data-hidden-chips]');

        const updateHiddenTray = () => {
            const ids = readHidden();
            hiddenChips.innerHTML = '';

            ids.forEach((id) => {
                const widget = grid.querySelector(`[data-dashboard-widget="${id}"]`);

                if (!widget) {
                    return;
                }

                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'btn btn-sm btn-outline-secondary';
                chip.dataset.restore = id;
                chip.innerHTML = '<i data-lucide="eye" class="fs-14 me-1"></i>' + widgetLabel(widget);
                chip.addEventListener('click', () => restoreWidget(id));
                hiddenChips.appendChild(chip);
            });

            hiddenTray.style.display = (layoutEditing && ids.length) ? '' : 'none';

            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        const applyHidden = () => {
            const ids = readHidden();

            widgets().forEach((widget) => {
                const hidden = ids.includes(widget.dataset.dashboardWidget);
                widget.classList.toggle('dashboard-widget-hidden', hidden);
                widget.classList.toggle('d-none', hidden);
            });

            updateHiddenTray();
        };

        const hideWidget = (widget) => {
            const id = widget.dataset.dashboardWidget;
            const ids = readHidden();

            if (!ids.includes(id)) {
                ids.push(id);
                saveHidden(ids);
            }

            widget.classList.add('dashboard-widget-hidden', 'd-none');
            applySmartLayout();
            updateHiddenTray();
        };

        const restoreWidget = (id) => {
            saveHidden(readHidden().filter((value) => value !== id));

            const widget = grid.querySelector(`[data-dashboard-widget="${id}"]`);

            if (widget) {
                widget.classList.remove('dashboard-widget-hidden', 'd-none');
            }

            applySmartLayout();
            updateHiddenTray();
        };

        hiddenTray.querySelector('#restoreAllHidden').addEventListener('click', () => {
            readHidden().slice().forEach((id) => restoreWidget(id));
        });

        const readSizes = () => (layout.sizes && typeof layout.sizes === 'object' ? layout.sizes : {});

        const saveSize = (widget, size) => {
            layout.sizes[widget.dataset.dashboardWidget] = Number(size);
            persistLayout(true);
        };

        // Un cuadro queda "fijado" cuando el usuario le eligió un tamaño con los
        // botones 1/2/3/4. En ese caso el tamaño manual gana: el auto-ajuste ya
        // no lo estira y conserva exactamente el ancho que pediste.
        const isPinned = (widget) =>
            Object.prototype.hasOwnProperty.call(readSizes(), widget.dataset.dashboardWidget);

        // Tamaño estándar (1–4) que corresponde al ancho actual del cuadro, o 0
        // si su ancho no es uno de los estándar (los pares col-xl-7 / col-xl-5
        // no son representables con los botones, así que no se resalta ninguno).
        const columnsToSize = { 12: 1, 6: 2, 4: 3, 3: 4 };
        const activeSizeFor = (widget) => columnsToSize[classColumnsFor(widget)] || 0;

        const setSizeButtonState = (widget, size) => {
            widget.querySelectorAll('[data-dashboard-size]').forEach((button) => {
                const isActive = Number(button.dataset.dashboardSize) === Number(size);
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-secondary', !isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const applySize = (widget, size, shouldPersist = true) => {
            const option = sizeOptions[size] || sizeOptions[4];

            managedClasses.forEach((className) => widget.classList.remove(className));
            option.classes.forEach((className) => widget.classList.add(className));
            widget.dataset.dashboardSize = String(size);
            setSizeButtonState(widget, size);

            if (shouldPersist) {
                saveSize(widget, size);
                applySmartLayout();
            }
        };

        const clearSmartWidths = () => {
            widgets().forEach((widget) => {
                widget.style.removeProperty('--dashboard-smart-width');
                delete widget.dataset.dashboardSmartWidth;
            });
        };

        const balanceSmartRow = (row, columns) => {
            if (columns >= 12 || row.length <= 1) {
                return;
            }

            // El tamaño manual gana: los cuadros fijados conservan su ancho
            // exacto y no se estiran. El espacio sobrante de la fila se reparte
            // solo entre los cuadros que siguen en automático.
            const flexible = row.filter((item) => !item.pinned);

            if (!flexible.length) {
                return;
            }

            const extraColumns = (12 - columns) / flexible.length;

            flexible.forEach((item) => {
                const width = ((item.columns + extraColumns) / 12) * 100;
                item.widget.style.setProperty('--dashboard-smart-width', `${width.toFixed(4)}%`);
                item.widget.dataset.dashboardSmartWidth = 'true';
            });
        };

        const applySmartLayout = () => {
            clearSmartWidths();
            grid.classList.toggle('is-smart-layout', smartLayoutEnabled);

            if (!smartLayoutEnabled) {
                return;
            }

            let row = [];
            let columns = 0;

            widgets().filter((widget) => !isHidden(widget)).forEach((widget) => {
                const widgetColumns = Math.min(12, Math.max(1, classColumnsFor(widget)));

                if (row.length && columns + widgetColumns > 12) {
                    balanceSmartRow(row, columns);
                    row = [];
                    columns = 0;
                }

                row.push({ widget, columns: widgetColumns, pinned: isPinned(widget) });
                columns += widgetColumns;

                if (columns >= 12) {
                    balanceSmartRow(row, columns);
                    row = [];
                    columns = 0;
                }
            });

            balanceSmartRow(row, columns);
        };

        const updateAutoLayoutButton = () => {
            if (!autoLayoutButton) {
                return;
            }

            autoLayoutButton.classList.toggle('btn-primary', smartLayoutEnabled);
            autoLayoutButton.classList.toggle('btn-outline-secondary', !smartLayoutEnabled);
            autoLayoutButton.setAttribute('aria-pressed', smartLayoutEnabled ? 'true' : 'false');
        };

        const restoreSizes = () => {
            const sizes = readSizes();

            resizableWidgets().forEach((widget) => {
                const savedSize = sizes[widget.dataset.dashboardWidget];

                if (savedSize) {
                    applySize(widget, savedSize, false);
                } else {
                    const natural = activeSizeFor(widget);

                    if (natural) {
                        widget.dataset.dashboardSize = String(natural);
                    } else {
                        delete widget.dataset.dashboardSize;
                    }
                }
            });
        };

        const restoreOrder = () => {
            const saved = (Array.isArray(layout.order) ? layout.order : [])
                .filter((id) => grid.querySelector(`[data-dashboard-widget="${id}"]`));

            if (!saved.length) {
                return;
            }

            // Los cuadros que aparecen/desaparecen según los datos del mes
            // (crédito disponible, detalle del indicador, San Juan, etc.) no
            // siempre están en el orden guardado. En vez de empujarlos al frente,
            // reordenamos solo los cuadros guardados entre sí y dejamos a los
            // demás en su posición original.
            const originalIds = widgets().map((widget) => widget.dataset.dashboardWidget);
            const known = new Set(saved);
            let savedIndex = 0;

            const finalOrder = originalIds.map((id) => (known.has(id) ? saved[savedIndex++] : id));

            finalOrder.forEach((id) => {
                const widget = grid.querySelector(`[data-dashboard-widget="${id}"]`);

                if (widget) {
                    grid.appendChild(widget);
                }
            });
        };

        const findInsertBefore = (clientX, clientY) => {
            return widgets()
                .filter((widget) => widget !== draggedWidget && !isHidden(widget))
                .find((widget) => {
                    const box = widget.getBoundingClientRect();
                    const isSameRow = clientY >= box.top && clientY <= box.bottom;

                    return clientY < box.top + (box.height / 2)
                        || (isSameRow && clientX < box.left + (box.width / 2));
                });
        };

        restoreOrder();
        restoreSizes();
        applyHidden();
        applySmartLayout();
        updateAutoLayoutButton();

        widgets().forEach((widget) => {
            const card = widget.querySelector(':scope > .card');

            if (!card) {
                return;
            }

            const handle = document.createElement('button');
            handle.type = 'button';
            handle.className = 'dashboard-widget-handle';
            handle.title = 'Mover cuadro';
            handle.setAttribute('aria-label', 'Mover cuadro');
            handle.innerHTML = '<i data-lucide="grip" class="fs-16"></i>';
            card.appendChild(handle);

            handle.addEventListener('mousedown', () => {
                widget.setAttribute('draggable', 'true');
            });

            handle.addEventListener('touchstart', () => {
                widget.setAttribute('draggable', 'true');
            }, { passive: true });

            const hideButton = document.createElement('button');
            hideButton.type = 'button';
            hideButton.className = 'dashboard-widget-hide';
            hideButton.title = 'Ocultar cuadro';
            hideButton.setAttribute('aria-label', 'Ocultar cuadro');
            hideButton.innerHTML = '<i data-lucide="eye-off" class="fs-16"></i>';
            hideButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                hideWidget(widget);
            });
            card.appendChild(hideButton);

            const sizePanel = document.createElement('div');
            sizePanel.className = 'dashboard-widget-size-panel';
            sizePanel.setAttribute('aria-label', 'Tamaño del cuadro');

            Object.entries(sizeOptions).forEach(([size, option]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-secondary';
                button.dataset.dashboardSize = size;
                button.textContent = option.label;
                button.title = `${option.label} por fila`;
                button.setAttribute('aria-pressed', 'false');
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    applySize(widget, Number(size));
                });
                sizePanel.appendChild(button);
            });

            card.appendChild(sizePanel);
            setSizeButtonState(widget, Number(widget.dataset.dashboardSize) || activeSizeFor(widget));
        });

        if (window.lucide) {
            window.lucide.createIcons();
        }

        grid.addEventListener('dragstart', (event) => {
            const widget = event.target.closest('[data-dashboard-widget]');

            if (!widget || !widget.hasAttribute('draggable')) {
                event.preventDefault();
                return;
            }

            draggedWidget = widget;
            draggedWidget.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', widget.dataset.dashboardWidget);
        });

        grid.addEventListener('dragover', (event) => {
            if (!draggedWidget) {
                return;
            }

            event.preventDefault();
            const before = findInsertBefore(event.clientX, event.clientY);

            if (before) {
                grid.insertBefore(draggedWidget, before);
            } else {
                grid.appendChild(draggedWidget);
            }
        });

        grid.addEventListener('dragend', () => {
            if (!draggedWidget) {
                return;
            }

            draggedWidget.classList.remove('is-dragging');
            draggedWidget.removeAttribute('draggable');
            draggedWidget = null;
            saveOrder();
            applySmartLayout();
        });

        document.addEventListener('mouseup', () => {
            widgets().forEach((widget) => {
                if (!widget.classList.contains('is-dragging')) {
                    widget.removeAttribute('draggable');
                }
            });
        });

        if (layoutButton) {
            layoutButton.addEventListener('click', () => {
                layoutEditing = !layoutEditing;
                grid.classList.toggle('is-layout-editing', layoutEditing);
                layoutButton.classList.toggle('btn-primary', layoutEditing);
                layoutButton.classList.toggle('btn-outline-secondary', !layoutEditing);
                layoutButton.setAttribute('aria-pressed', layoutEditing ? 'true' : 'false');
                updateHiddenTray();
            });
        }

        if (autoLayoutButton) {
            autoLayoutButton.addEventListener('click', () => {
                smartLayoutEnabled = !smartLayoutEnabled;
                layout.autoLayout = smartLayoutEnabled;
                persistLayout(true);
                applySmartLayout();
                updateAutoLayoutButton();
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', () => {
                if (!window.confirm('¿Restablecer el Resumen a su distribución de fábrica? Se perderá tu orden, tamaños y cuadros ocultos.')) {
                    return;
                }

                // Restablecer = borrar la distribución guardada en el servidor.
                if (saveUrl) {
                    fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ layout: null }),
                    }).finally(() => window.location.reload());
                } else {
                    window.location.reload();
                }
            });
        }
    });

    // Animación count-up de los números del hero (mejora progresiva: si el JS no
    // corre o el usuario pidió menos movimiento, se queda el valor ya renderizado).
    document.addEventListener('DOMContentLoaded', function () {
        const targets = document.querySelectorAll('[data-countup]');

        if (!targets.length) {
            return;
        }

        const numberFormat = (value, decimals) => value
            .toFixed(decimals)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        const reduceMotion = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        targets.forEach((el) => {
            const target = parseFloat(el.dataset.countup);

            if (!isFinite(target)) {
                return;
            }

            const decimals = parseInt(el.dataset.countupDecimals || '0', 10);
            const prefix = el.dataset.countupPrefix || '';
            const negative = target < 0;
            const magnitude = Math.abs(target);

            if (reduceMotion) {
                return; // deja el valor ya impreso por el servidor
            }

            const duration = 850;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = magnitude * eased;
                el.textContent = (negative ? '-' : '') + prefix + numberFormat(current, decimals);

                if (progress < 1) {
                    requestAnimationFrame(tick);
                } else {
                    el.textContent = (negative ? '-' : '') + prefix + numberFormat(magnitude, decimals);
                }
            };

            el.textContent = (negative ? '-' : '') + prefix + numberFormat(0, decimals);
            requestAnimationFrame(tick);
        });
    });
</script>
@endsection
