@extends('layouts.vertical', ['title' => 'Diagnóstico de finanzas'])

@section('content')
@php
    $statusMeta = [
        'ok' => ['label' => 'OK', 'class' => 'badge-soft-success', 'icon' => 'check-circle'],
        'warning' => ['label' => 'Warning', 'class' => 'badge-soft-warning', 'icon' => 'alert-triangle'],
        'fail' => ['label' => 'Fail', 'class' => 'badge-soft-danger', 'icon' => 'x-circle'],
    ];
@endphp

<div class="row align-items-center mb-3">
    <div class="col-md-8">
        <h4 class="mb-0 fw-semibold">Diagnóstico de finanzas</h4>
        <div class="text-muted">Validaciones locales de configuración, dependencias y permisos sin llamadas HTTP internas.</div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="{{ route('finance.health.index') }}" class="btn btn-outline-primary">
            <i data-lucide="refresh-cw" class="me-1"></i>Actualizar checks
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="card mb-0 border-0 bg-success-subtle">
            <div class="card-body">
                <p class="text-muted mb-1">OK</p>
                <h3 class="mb-0">{{ $summary['ok'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0 border-0 bg-warning-subtle">
            <div class="card-body">
                <p class="text-muted mb-1">Warnings</p>
                <h3 class="mb-0">{{ $summary['warning'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0 border-0 bg-danger-subtle">
            <div class="card-body">
                <p class="text-muted mb-1">Fails</p>
                <h3 class="mb-0">{{ $summary['fail'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0 border-0 bg-primary-subtle">
            <div class="card-body">
                <p class="text-muted mb-1">Total</p>
                <h3 class="mb-0">{{ $summary['total'] }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <strong>Nota:</strong> este diagnóstico comprueba existencia de rutas y dependencias registradas; no hace llamadas HTTP internas para evitar timeouts o problemas de loopback en hosting compartido.
    Para diagnosticar un error 500 cuando esta pantalla no carga, usa el endpoint público de triage <code>/_health/triage?key=TOKEN</code> (requiere <code>FINANCE_HEALTH_TOKEN</code>).
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Checks</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Check</th>
                        <th>Mensaje</th>
                        <th>Detalle técnico</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checks as $check)
                        @php($meta = $statusMeta[$check['status']] ?? $statusMeta['warning'])
                        <tr>
                            <td>
                                <span class="badge {{ $meta['class'] }}">
                                    <i data-lucide="{{ $meta['icon'] }}" class="me-1"></i>{{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="fw-semibold">{{ $check['name'] }}</td>
                            <td>{{ $check['message'] }}</td>
                            <td><code>{{ $check['detail'] }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
