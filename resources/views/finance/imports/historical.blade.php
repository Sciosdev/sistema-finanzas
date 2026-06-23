@extends('layouts.vertical', ['title' => 'Importar histórico'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $preview = $preview ?? null;
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-7">
        <h4 class="mb-0 fw-semibold">Importar reporte histórico 2025/2026</h4>
        <p class="text-muted mb-0">Primero se valida el CSV; después decides si guardas los movimientos válidos.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Archivo CSV</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.imports.historical.preview') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-6">
                <label class="form-label">CSV del reporteador</label>
                <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                <div class="form-text">Si tienes Excel, guarda una copia como CSV antes de subirla.</div>
            </div>
            <div class="col-lg-3 d-grid">
                <button type="submit" class="btn btn-outline-primary">
                    <i data-lucide="file-search" class="me-1"></i>Validar vista previa
                </button>
            </div>
            <div class="col-lg-3 d-grid">
                <a href="{{ route('finance.imports.historical.template') }}" class="btn btn-outline-success">
                    <i data-lucide="download" class="me-1"></i>Descargar plantilla
                </a>
            </div>
        </form>

        <div class="alert alert-info mt-3 mb-0">
            <strong>Columnas esperadas:</strong>
            fecha, tipo, descripcion, monto. Opcionales: cuenta, categoria, persona, notas, san_juan, renta, desconocido, diferencia_conciliacion.
        </div>
    </div>
</div>

@if ($preview)
    <div class="row g-3">
        <div class="col-xl-3 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Movimientos válidos</p>
                    <h4 class="mb-0 text-success">{{ $preview['valid_count'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Advertencias</p>
                    <h4 class="mb-0 text-warning">{{ $preview['warning_count'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Errores</p>
                    <h4 class="mb-0 text-danger">{{ $preview['error_count'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-12">
            <div class="card h-100">
                <div class="card-body d-grid align-content-center">
                    <form method="POST" action="{{ route('finance.imports.historical.store') }}">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" @disabled(($preview['valid_count'] ?? 0) <= 0)>
                            <i data-lucide="save" class="me-1"></i>Guardar movimientos válidos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Vista previa</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 560px;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Línea</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Cuenta</th>
                            <th>Categoría</th>
                            <th>Persona</th>
                            <th class="text-end">Monto</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($preview['rows'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['line'] }}</td>
                                <td>
                                    @if (! $row['valid'])
                                        <span class="badge badge-soft-danger">Error</span>
                                    @elseif ($row['duplicate'])
                                        <span class="badge badge-soft-warning">Duplicado</span>
                                    @else
                                        <span class="badge badge-soft-success">Listo</span>
                                    @endif
                                </td>
                                <td>{{ $row['happened_on'] ?? '-' }}</td>
                                <td>{{ $row['movement_type'] ?? '-' }}</td>
                                <td>{{ $row['description'] ?? '-' }}</td>
                                <td>{{ $row['account_name'] ?: '-' }}</td>
                                <td>{{ $row['category_name'] ?: '-' }}</td>
                                <td>{{ $row['person_name'] ?: '-' }}</td>
                                <td class="text-end">{{ $money($row['amount'] ?? 0) }}</td>
                                <td style="min-width: 280px;">
                                    @foreach (($row['errors'] ?? []) as $error)
                                        <div class="text-danger small">{{ $error }}</div>
                                    @endforeach
                                    @foreach (($row['warnings'] ?? []) as $warning)
                                        <div class="text-warning small">{{ $warning }}</div>
                                    @endforeach
                                    @if (empty($row['errors']) && empty($row['warnings']))
                                        <span class="text-muted small">Sin observaciones</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">El archivo no contiene movimientos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
