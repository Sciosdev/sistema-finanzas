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
@endphp

<style>
    .finance-dashboard-grid .dashboard-widget {
        transition: opacity .15s ease, transform .15s ease;
    }

    .finance-dashboard-grid .dashboard-widget.is-dragging {
        opacity: .45;
        transform: scale(.99);
    }

    .finance-dashboard-grid .dashboard-widget > .card {
        position: relative;
        height: 100%;
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
</style>

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Finanzas</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.dashboard') }}" class="d-flex justify-content-md-end gap-2">
            <button class="btn btn-outline-secondary" type="button" id="resetDashboardOrder" title="Restablecer orden">
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

<div class="row g-3 finance-dashboard-grid" id="financeDashboardGrid" data-storage-key="finance-dashboard-order-{{ auth()->id() }}">
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
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="expected-leftover">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Sobrante esperado</p>
                    <h4 class="fw-bold mb-0">{{ $money($summary['expected_leftover']) }}</h4>
                    <small class="text-muted">Ingresos - egresos</small>
                </div>
                <i data-lucide="scale" class="fs-32 text-primary"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="real-total-cut">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-2 card-title">Total real corte</p>
                    <h4 class="fw-bold mb-0">{{ $summary['latest_cut'] ? $money($summary['real_total']) : '-' }}</h4>
                    <small class="{{ $isBalanced ? 'text-success' : 'text-danger' }}">
                        {{ $summary['latest_cut'] ? ($isBalanced ? 'Cuadra' : 'Revisar') : 'Sin corte' }}
                    </small>
                </div>
                <i data-lucide="wallet" class="fs-32 text-primary"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="cut-difference">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Resta corte</p>
                <h4 class="fw-bold mb-0 {{ $difference === null ? '' : ($isBalanced ? 'text-success' : 'text-danger') }}">
                    {{ $difference === null ? '-' : $money($difference) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="amount-missing">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Cuánto me falta</p>
                <h4 class="fw-bold mb-0 {{ ($summary['amount_missing'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                    {{ $summary['amount_missing'] === null ? '-' : $money($summary['amount_missing']) }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="san-juan-expenses">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Egresos San Juan</p>
                <h4 class="fw-bold mb-0 text-danger">{{ $money($summary['san_juan_expenses']) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 dashboard-widget" data-dashboard-widget="san-juan-profit">
        <div class="card">
            <div class="card-body">
                <p class="mb-2 card-title">Utilidad San Juan</p>
                <h4 class="fw-bold mb-0 {{ $summary['san_juan_utility'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($summary['san_juan_utility']) }}</h4>
            </div>
        </div>
    </div>

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
                            <p class="text-muted mb-1">No pagado visible</p>
                            <h5 class="fw-bold mb-0 text-danger">{{ $money($summary['obligation_totals']['skipped'] ?? 0) }}</h5>
                        </div>
                    </div>
                </div>
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
                                    <td>{{ $income['concept'] }}</td>
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
                                                            </div>
                                                        @elseif (! empty($payment['detail']))
                                                            <div class="text-muted small">{{ $payment['detail'] }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge {{ ($payment['status'] ?? null) === 'overdue' ? 'badge-soft-danger' : 'badge-soft-primary' }}">
                                                    {{ $payment['origin'] ?? 'Pago' }}
                                                </span>
                                                <div class="text-muted small">{{ $payment['origin_detail'] ?? 'Pendiente' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($payment['due_date'], $payment['status']) }}">
                                                    {{ \App\Support\FinanceLabels::dueLabel($payment['due_date'], $payment['status']) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $money($payment['amount_due']) }}</td>
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
                    <text x="{{ $chartLeft }}" y="172" fill="currentColor" opacity=".65" font-size="13">Dia 1</text>
                    <text x="{{ $chartWidth - 72 }}" y="172" fill="currentColor" opacity=".65" font-size="13">Dia {{ count($dailyValues) }}</text>
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

        const storageKey = grid.dataset.storageKey || 'finance-dashboard-order';
        const resetButton = document.getElementById('resetDashboardOrder');
        let draggedWidget = null;

        const widgets = () => Array.from(grid.querySelectorAll('[data-dashboard-widget]'));

        const saveOrder = () => {
            localStorage.setItem(storageKey, JSON.stringify(widgets().map((widget) => widget.dataset.dashboardWidget)));
        };

        const restoreOrder = () => {
            let order = [];

            try {
                order = JSON.parse(localStorage.getItem(storageKey) || '[]');
            } catch (error) {
                order = [];
            }

            order.forEach((id) => {
                const widget = grid.querySelector(`[data-dashboard-widget="${id}"]`);

                if (widget) {
                    grid.appendChild(widget);
                }
            });
        };

        const findInsertBefore = (clientX, clientY) => {
            return widgets()
                .filter((widget) => widget !== draggedWidget)
                .find((widget) => {
                    const box = widget.getBoundingClientRect();
                    const isSameRow = clientY >= box.top && clientY <= box.bottom;

                    return clientY < box.top + (box.height / 2)
                        || (isSameRow && clientX < box.left + (box.width / 2));
                });
        };

        restoreOrder();

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
        });

        document.addEventListener('mouseup', () => {
            widgets().forEach((widget) => {
                if (!widget.classList.contains('is-dragging')) {
                    widget.removeAttribute('draggable');
                }
            });
        });

        if (resetButton) {
            resetButton.addEventListener('click', () => {
                localStorage.removeItem(storageKey);
                window.location.reload();
            });
        }
    });
</script>
@endsection
