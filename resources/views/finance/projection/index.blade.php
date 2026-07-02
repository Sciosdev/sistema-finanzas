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
    $meta = $projection['meta'];
    $summary = $projection['summary'];
    $warnings = $projection['warnings'];
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

@if ($summary['overdue_income_total'] > 0)
    <div class="card border-warning">
        <div class="card-body">
            <h5 class="card-title mb-1">
                <i data-lucide="alert-triangle" class="me-1"></i>
                Tienes {{ $money($summary['overdue_income_total']) }} vencidos por cobrar que NO están contados en la proyección
            </h5>
            <p class="text-muted small mb-2">Un ingreso vencido no cobrado es un riesgo, no dinero. Puedes activarlos abajo para verlos solo en el saldo proyectado.</p>
            <ul class="mb-0 small">
                @foreach ($summary['overdue_income_items'] as $item)
                    <li>{{ $item['name'] }} — {{ $money($item['amount']) }}@if ($item['due_date']) (vencía el {{ $item['due_date'] }})@endif</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

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
