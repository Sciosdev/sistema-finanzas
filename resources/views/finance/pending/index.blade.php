@extends('layouts.vertical', ['title' => 'Pendientes por resolver'])

@section('content')
@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-8">
        <h4 class="mb-0 fw-semibold">Pendientes por resolver</h4>
        <div class="text-muted">Lo que está incompleto, vencido, sin ligar o con posible error. Esta pantalla solo detecta y te lleva a corregirlo a mano.</div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="{{ route('finance.pending.index') }}" class="btn btn-outline-primary">
            <i data-lucide="refresh-cw" class="me-1"></i>Actualizar
        </a>
    </div>
</div>

@if ($summary['total'] === 0)
    <div class="card border-0 bg-success-subtle">
        <div class="card-body d-flex align-items-center gap-3">
            <i data-lucide="check-circle" class="text-success"></i>
            <div>
                <h5 class="mb-0">Todo en orden</h5>
                <div class="text-muted">No se encontraron pendientes por resolver.</div>
            </div>
        </div>
    </div>
@else
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge badge-soft-danger fs-13">Total: {{ $summary['total'] }}</span>
                @foreach ($groups as $group)
                    @if ($group['count'] > 0)
                        <a href="#group-{{ $group['key'] }}" class="badge badge-soft-warning fs-13 text-decoration-none">
                            {{ $group['title'] }}: {{ $group['count'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    @foreach ($groups as $group)
        @if ($group['count'] > 0)
            <div class="card mb-3" id="group-{{ $group['key'] }}">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i data-lucide="{{ $group['icon'] }}" class="me-1"></i>{{ $group['title'] }}
                    </h5>
                    <span class="badge badge-soft-secondary">{{ $group['count'] }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Monto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['items'] as $item)
                                    <tr>
                                        <td class="text-nowrap">{{ $item['tipo'] }}</td>
                                        <td>{{ $item['descripcion'] }}</td>
                                        <td class="text-end text-nowrap">
                                            @if (! is_null($item['monto']))
                                                ${{ number_format($item['monto'], 2) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $item['fecha'] ? $item['fecha']->format('d/m/Y') : '—' }}
                                        </td>
                                        <td><span class="badge badge-soft-warning">{{ $item['estado'] }}</span></td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary">
                                                {{ $item['accion'] }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif
@endsection
