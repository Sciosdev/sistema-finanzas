@extends('layouts.vertical', ['title' => 'Planificador'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $riskBadges = [
        'ok' => ['class' => 'badge-soft-success', 'label' => 'OK'],
        'medium' => ['class' => 'badge-soft-warning', 'label' => 'Medio'],
        'high' => ['class' => 'badge-soft-danger', 'label' => 'Alto'],
        'critical' => ['class' => 'bg-danger', 'label' => 'Crítico'],
    ];
    $limitBadges = [
        'ok' => ['class' => 'badge-soft-success', 'label' => 'OK'],
        'warning' => ['class' => 'badge-soft-warning', 'label' => 'Advertencia'],
        'exceeded' => ['class' => 'badge-soft-danger', 'label' => 'Excedido'],
        'blocked' => ['class' => 'bg-danger', 'label' => 'Bloqueado'],
    ];
    $periodLabels = [
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
    ];
    $meta = $projection['meta'];
    $summary = $projection['summary'];
    $warnings = $projection['warnings'];
    $recommendation = $paymentRecommendations ?? [
        'available' => ['safe_today' => 0, 'projected_today' => 0],
        'shortfall' => [
            'cash_needed_to_avoid_negative' => 0,
            'cash_needed_for_buffer' => 0,
            'first_risky_date' => null,
            'first_high_date' => null,
            'first_critical_date' => null,
            'min_safe_date' => null,
            'min_projected_date' => null,
        ],
        'recommendations' => [
            'pay_now' => [],
            'upcoming' => [],
            'wait_for_income' => [],
            'risky_payments' => [],
            'overdue_income_to_collect' => [],
        ],
        'messages' => [],
    ];
    $available = $recommendation['available'];
    $shortfall = $recommendation['shortfall'];
    $groups = $recommendation['recommendations'];
    $spendingLimitReport = $spendingLimits ?? [
        'available_safe_today' => 0,
        'limits' => [],
        'summary' => [
            'total_limits' => 0,
            'ok_count' => 0,
            'warning_count' => 0,
            'exceeded_count' => 0,
            'blocked_count' => 0,
        ],
        'messages' => [],
    ];
    $spendingLimitSummary = $spendingLimitReport['summary'];
    $spendingLimitRows = $spendingLimitReport['limits'];
    $expenseCategories = $expenseCategories ?? collect();
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Planificador de flujo</h4>
        <p class="text-muted mb-0 small">Proyección diaria del {{ $meta['start_date'] }} al {{ $meta['end_date'] }} sobre la misma base conciliada que los cortes.</p>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-md-end gap-2 mt-2 mt-md-0">
            @foreach ($horizons as $option)
                <a href="{{ route('finance.projection.index', ['horizonte' => $option]) }}"
                   class="btn {{ $horizon === $option ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $option }} días
                </a>
            @endforeach
        </div>
    </div>
</div>

@if (in_array('no_baseline_cut', $warnings, true))
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i data-lucide="flag" class="me-2"></i>
        Aún no tienes cortes: el saldo inicial usa el saldo de apertura de tus cuentas más los movimientos capturados. Haz tu primer corte para anclar la proyección a dinero contado.
    </div>
@endif

@if (in_array('stale_baseline', $warnings, true))
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i data-lucide="clock-alert" class="me-2"></i>
        Tu último corte es del {{ $meta['baseline_cut_date'] }} ({{ $meta['baseline_age_days'] }} días): la proyección sigue siendo válida, pero conviene hacer un corte nuevo.
    </div>
@endif

@if (in_array('next_month_flow_empty', $warnings, true))
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i data-lucide="calendar-x" class="me-2"></i>
        El horizonte entra al mes siguiente y ese mes aún no tiene flujo planeado: los días de ese mes pueden verse más optimistas de lo real.
    </div>
@endif

<div class="row">
    <div class="col-6 col-lg-2">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1 small">Saldo inicial (hoy)</p>
                <h5 class="mb-0">{{ $money($meta['starting_balance']) }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1 small">Colchón mínimo</p>
                <h5 class="mb-0">{{ $money($meta['buffer']) }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1 small">Saldo seguro final</p>
                <h5 class="mb-0 {{ $summary['end_balance_safe'] < $meta['buffer'] ? 'text-danger' : 'text-success' }}">{{ $money($summary['end_balance_safe']) }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1 small">Saldo proyectado final</p>
                <h5 class="mb-0 {{ $summary['end_balance_projected'] < $meta['buffer'] ? 'text-danger' : 'text-success' }}">{{ $money($summary['end_balance_projected']) }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1 small">Primer día con riesgo</p>
                <h5 class="mb-0">{{ $summary['first_risky_date'] ?? 'Sin riesgo' }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1 small">Peor riesgo</p>
                <h5 class="mb-0">
                    <span class="badge {{ $riskBadges[$summary['max_risk']]['class'] }}">{{ $riskBadges[$summary['max_risk']]['label'] }}</span>
                </h5>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-6 col-lg-3">
        <div class="card border-success">
            <div class="card-body">
                <p class="text-muted mb-1 small">Disponible seguro hoy</p>
                <h4 class="mb-0 text-success">{{ $money($available['safe_today']) }}</h4>
                <p class="text-muted small mb-0">Sin confiar en ingresos futuros.</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-info">
            <div class="card-body">
                <p class="text-muted mb-1 small">Disponible proyectado hoy</p>
                <h4 class="mb-0 text-info">{{ $money($available['projected_today']) }}</h4>
                <p class="text-muted small mb-0">Incluye ingresos esperados.</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card {{ $shortfall['cash_needed_to_avoid_negative'] > 0 ? 'border-danger' : 'border-success' }}">
            <div class="card-body">
                <p class="text-muted mb-1 small">Faltante para no quedar negativo</p>
                <h4 class="mb-0 {{ $shortfall['cash_needed_to_avoid_negative'] > 0 ? 'text-danger' : 'text-success' }}">{{ $money($shortfall['cash_needed_to_avoid_negative']) }}</h4>
                <p class="text-muted small mb-0">Según el mínimo proyectado.</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card {{ $shortfall['cash_needed_for_buffer'] > 0 ? 'border-warning' : 'border-success' }}">
            <div class="card-body">
                <p class="text-muted mb-1 small">Faltante para mantener colchón</p>
                <h4 class="mb-0 {{ $shortfall['cash_needed_for_buffer'] > 0 ? 'text-warning' : 'text-success' }}">{{ $money($shortfall['cash_needed_for_buffer']) }}</h4>
                <p class="text-muted small mb-0">Meta: {{ $money($meta['buffer']) }}.</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-2">
    <h5 class="mb-0 fw-semibold">Recomendaciones</h5>
    @if ($shortfall['first_high_date'] || $shortfall['first_critical_date'])
        <span class="badge badge-soft-danger">
            @if ($shortfall['first_critical_date'])
                Crítico: {{ $shortfall['first_critical_date'] }}
            @else
                Alto: {{ $shortfall['first_high_date'] }}
            @endif
        </span>
    @endif
</div>

@if (count($recommendation['messages']) > 0)
    <div class="row g-2 mb-3">
        @foreach ($recommendation['messages'] as $message)
            <div class="col-md-6 col-xl-4">
                <div class="alert alert-light border mb-0 py-2 small">{{ $message }}</div>
            </div>
        @endforeach
    </div>
@endif

<div class="row">
    <div class="col-lg-6 col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Paga / atiende hoy</h4>
            </div>
            <div class="card-body">
                @forelse ($groups['pay_now'] as $item)
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $item['name'] }}</div>
                            <div class="text-muted small">
                                {{ $item['reason'] }}
                                @if ($item['is_overdue'])<span class="badge badge-soft-danger ms-1">Vencido</span>@endif
                            </div>
                        </div>
                        <div class="text-end fw-semibold text-danger">−{{ $money($item['amount']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Sin pagos urgentes para hoy.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Próximos pagos</h4>
            </div>
            <div class="card-body">
                @forelse ($groups['upcoming'] as $group)
                    <div class="mb-3">
                        <div class="fw-semibold small text-muted mb-1">{{ $group['date'] }}</div>
                        @foreach ($group['items'] as $item)
                            <div class="d-flex justify-content-between gap-3 mb-1">
                                <span>{{ $item['name'] }}</span>
                                <span class="text-danger">−{{ $money($item['amount']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted small mb-0">Sin pagos próximos dentro del horizonte.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card border-warning">
            <div class="card-header">
                <h4 class="card-title mb-0">Dependen de ingresos</h4>
            </div>
            <div class="card-body">
                @forelse ($groups['wait_for_income'] as $item)
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $item['name'] }}</div>
                            <div class="text-muted small">{{ $item['date'] }}</div>
                        </div>
                        <div class="text-end text-warning fw-semibold">−{{ $money($item['amount']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No hay pagos condicionados a ingresos esperados.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card border-danger">
            <div class="card-header">
                <h4 class="card-title mb-0">Riesgosos</h4>
            </div>
            <div class="card-body">
                @forelse ($groups['risky_payments'] as $item)
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $item['name'] }}</div>
                            <div class="text-muted small">
                                {{ $item['date'] }}
                                <span class="badge {{ $riskBadges[$item['risk_after_payment']]['class'] }} ms-1">{{ $riskBadges[$item['risk_after_payment']]['label'] }}</span>
                            </div>
                        </div>
                        <div class="text-end text-danger fw-semibold">−{{ $money($item['amount']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No hay pagos riesgosos en este horizonte.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card border-warning">
            <div class="card-header">
                <h4 class="card-title mb-0">Ingresos vencidos por cobrar</h4>
            </div>
            <div class="card-body">
                @forelse ($groups['overdue_income_to_collect'] as $item)
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $item['name'] }}</div>
                            <div class="text-muted small">@if ($item['due_date'])Vencía el {{ $item['due_date'] }}@else Sin fecha registrada @endif</div>
                        </div>
                        <div class="text-end text-success fw-semibold">+{{ $money($item['amount']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No hay ingresos vencidos reportados.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card border-primary">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h4 class="card-title mb-0">Límites de gasto</h4>
            <p class="text-muted small mb-0">Control por categoría cruzado con tu disponible seguro de hoy.</p>
        </div>
        <span class="badge badge-soft-primary">Seguro hoy: {{ $money($spendingLimitReport['available_safe_today']) }}</span>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg">
                <div class="border rounded p-2">
                    <p class="text-muted small mb-1">Configurados</p>
                    <h5 class="mb-0">{{ $spendingLimitSummary['total_limits'] }}</h5>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-2">
                    <p class="text-muted small mb-1">OK</p>
                    <h5 class="mb-0 text-success">{{ $spendingLimitSummary['ok_count'] }}</h5>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-2">
                    <p class="text-muted small mb-1">Advertencia</p>
                    <h5 class="mb-0 text-warning">{{ $spendingLimitSummary['warning_count'] }}</h5>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-2">
                    <p class="text-muted small mb-1">Excedidos</p>
                    <h5 class="mb-0 text-danger">{{ $spendingLimitSummary['exceeded_count'] }}</h5>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-2">
                    <p class="text-muted small mb-1">Bloqueados</p>
                    <h5 class="mb-0 text-danger">{{ $spendingLimitSummary['blocked_count'] }}</h5>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Periodo</th>
                        <th class="text-end">Límite</th>
                        <th class="text-end">Gastado</th>
                        <th class="text-end">Restante</th>
                        <th class="text-end">Recomendado hoy</th>
                        <th class="text-end">% usado</th>
                        <th>Estado</th>
                        <th>Mensaje</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spendingLimitRows as $limit)
                        <tr>
                            <td class="fw-semibold">{{ $limit['category_name'] }}</td>
                            <td>{{ $periodLabels[$limit['period_type']] ?? $limit['period_type'] }}</td>
                            <td class="text-end">{{ $money($limit['limit_amount']) }}</td>
                            <td class="text-end text-danger">{{ $money($limit['spent_amount']) }}</td>
                            <td class="text-end {{ $limit['remaining_amount'] <= 0 ? 'text-danger' : 'text-success' }}">{{ $money($limit['remaining_amount']) }}</td>
                            <td class="text-end fw-semibold">{{ $money($limit['recommended_today']) }}</td>
                            <td class="text-end">{{ number_format((float) $limit['used_percent'], 2) }}%</td>
                            <td>
                                <span class="badge {{ $limitBadges[$limit['status']]['class'] }}">{{ $limitBadges[$limit['status']]['label'] }}</span>
                            </td>
                            <td class="small text-muted">{{ $limit['message'] }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <form method="POST" action="{{ route('finance.spending-limits.update', $limit['id']) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="category_id" value="{{ $limit['category_id'] }}">
                                        <input type="hidden" name="period_type" value="{{ $limit['period_type'] }}">
                                        <input type="hidden" name="limit_amount" value="{{ $limit['limit_amount'] }}">
                                        <input type="hidden" name="warning_threshold_percent" value="{{ $limit['warning_threshold_percent'] }}">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="hidden" name="notes" value="{{ $limit['notes'] }}">
                                        <button class="btn btn-sm btn-outline-warning" type="submit">Desactivar</button>
                                    </form>
                                    <form method="POST" action="{{ route('finance.spending-limits.destroy', $limit['id']) }}" onsubmit="return confirm('¿Eliminar este límite?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3">Aún no tienes límites de gasto activos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('finance.spending-limits.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="spending_limit_category_id">Categoría</label>
                <select class="form-select" id="spending_limit_category_id" name="category_id" required @disabled($expenseCategories->isEmpty())>
                    <option value="">Selecciona...</option>
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id') === (int) $category->id)>
                            {{ $category->name }}@if ($category->group) · {{ $category->group }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="spending_limit_period_type">Periodo</label>
                <select class="form-select" id="spending_limit_period_type" name="period_type" required>
                    <option value="daily" @selected(old('period_type') === 'daily')>Diario</option>
                    <option value="weekly" @selected(old('period_type', 'weekly') === 'weekly')>Semanal</option>
                    <option value="monthly" @selected(old('period_type') === 'monthly')>Mensual</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="spending_limit_amount">Monto límite</label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="spending_limit_amount" name="limit_amount" value="{{ old('limit_amount') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="spending_limit_warning">Aviso %</label>
                <input type="number" step="0.01" min="1" max="100" class="form-control" id="spending_limit_warning" name="warning_threshold_percent" value="{{ old('warning_threshold_percent', 80) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="spending_limit_notes">Notas</label>
                <input type="text" class="form-control" id="spending_limit_notes" name="notes" value="{{ old('notes') }}">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" type="submit" @disabled($expenseCategories->isEmpty())>
                    <i data-lucide="plus" class="me-1"></i>Crear
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Proyección diaria</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-sm align-middle">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Inicial seguro</th>
                        <th class="text-end">Inicial proyectado</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Pagos</th>
                        <th class="text-end">Mensualidades</th>
                        <th class="text-end">Final seguro</th>
                        <th class="text-end">Final proyectado</th>
                        <th class="text-end">vs. colchón</th>
                        <th>Riesgo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projection['days'] as $day)
                        @php($hasEvents = count($day['incomes']) + count($day['payments']) + count($day['installments']) > 0)
                        <tr>
                            <td>
                                @if ($hasEvents)
                                    <button type="button" class="btn btn-sm btn-link p-0 me-1 align-baseline" data-bs-toggle="collapse" data-bs-target="#day-detail-{{ $day['date'] }}" aria-expanded="false" title="Ver detalle del día">
                                        <i data-lucide="chevron-down" class="fs-16"></i>
                                    </button>
                                @endif
                                {{ $day['weekday_label'] }}
                            </td>
                            <td class="text-end">{{ $money($day['opening_safe']) }}</td>
                            <td class="text-end text-muted">{{ $money($day['opening_projected']) }}</td>
                            <td class="text-end {{ $day['income_total'] > 0 ? 'text-success' : 'text-muted' }}">{{ $day['income_total'] > 0 ? '+' . $money($day['income_total']) : '—' }}</td>
                            <td class="text-end {{ $day['payment_total'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $day['payment_total'] > 0 ? '−' . $money($day['payment_total']) : '—' }}</td>
                            <td class="text-end {{ $day['installment_total'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $day['installment_total'] > 0 ? '−' . $money($day['installment_total']) : '—' }}</td>
                            <td class="text-end fw-semibold {{ $day['closing_safe'] < $meta['buffer'] ? 'text-danger' : '' }}">{{ $money($day['closing_safe']) }}</td>
                            <td class="text-end {{ $day['closing_projected'] < $meta['buffer'] ? 'text-danger' : 'text-muted' }}">{{ $money($day['closing_projected']) }}</td>
                            <td class="text-end {{ $day['buffer_gap_safe'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ ($day['buffer_gap_safe'] < 0 ? '−' : '+') . $money(abs($day['buffer_gap_safe'])) }}
                            </td>
                            <td>
                                <span class="badge {{ $riskBadges[$day['risk']]['class'] }}">{{ $riskBadges[$day['risk']]['label'] }}</span>
                            </td>
                        </tr>
                        @if ($hasEvents)
                            <tr>
                                <td colspan="10" class="p-0 border-0">
                                    <div class="collapse" id="day-detail-{{ $day['date'] }}">
                                        <div class="p-3 bg-body-tertiary small">
                                            @foreach ($day['incomes'] as $income)
                                                <div class="d-flex justify-content-between">
                                                    <span>
                                                        <i data-lucide="arrow-down-left" class="me-1 text-success"></i>{{ $income['name'] }}
                                                        @if ($income['is_overdue'])<span class="badge badge-soft-danger ms-1">Vencido</span>@endif
                                                    </span>
                                                    <span class="text-success">+{{ $money($income['amount']) }}</span>
                                                </div>
                                            @endforeach
                                            @foreach ($day['payments'] as $payment)
                                                <div class="d-flex justify-content-between">
                                                    <span>
                                                        <i data-lucide="arrow-up-right" class="me-1 text-danger"></i>{{ $payment['name'] }}
                                                        @if ($payment['is_overdue'])<span class="badge badge-soft-danger ms-1">Vencido</span>@endif
                                                        @unless ($payment['has_due_date'])<span class="badge badge-soft-secondary ms-1">Sin fecha</span>@endunless
                                                    </span>
                                                    <span class="text-danger">−{{ $money($payment['amount']) }}</span>
                                                </div>
                                            @endforeach
                                            @foreach ($day['installments'] as $installment)
                                                <div class="d-flex justify-content-between">
                                                    <span>
                                                        <i data-lucide="credit-card" class="me-1 text-danger"></i>{{ $installment['credit_name'] }} ({{ $installment['installment_label'] }})
                                                        @if ($installment['is_overdue'])<span class="badge badge-soft-danger ms-1">Vencido</span>@endif
                                                    </span>
                                                    <span class="text-danger">−{{ $money($installment['amount']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Configuración</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.projection.settings') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="minimum_buffer">Colchón mínimo</label>
                <input type="number" step="0.01" min="0" class="form-control" id="minimum_buffer" name="minimum_buffer"
                       value="{{ old('minimum_buffer', $settings?->minimum_buffer ?? 0) }}" required>
                <div class="form-text">La proyección marca riesgo cuando el saldo cae debajo de este monto.</div>
            </div>
            <div class="col-md-5">
                <input type="hidden" name="count_overdue_income" value="0">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="count_overdue_income" name="count_overdue_income" value="1"
                           @checked(old('count_overdue_income', $settings?->count_overdue_income ?? false))>
                    <label class="form-check-label" for="count_overdue_income">
                        Contar ingresos vencidos en el saldo proyectado (día 1)
                    </label>
                </div>
                <div class="form-text">Nunca entran al saldo seguro.</div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" type="submit">
                    <i data-lucide="save" class="me-1"></i>Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
