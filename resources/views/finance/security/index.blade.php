@extends('layouts.vertical', ['title' => 'Seguridad'])

@section('content')
@php
    $bytes = function ($value) {
        $value = (float) $value;

        if ($value >= 1048576) {
            return number_format($value / 1048576, 2) . ' MB';
        }

        if ($value >= 1024) {
            return number_format($value / 1024, 2) . ' KB';
        }

        return number_format($value, 0) . ' B';
    };

    $dateTime = fn ($value) => $value ? $value->format('Y-m-d H:i') : '-';
    $snapshotStatus = function ($snapshot) {
        if ($snapshot->restored_at) {
            return ['label' => 'Restaurado', 'class' => 'badge-soft-success'];
        }

        if ($snapshot->expires_at->lt(now())) {
            return ['label' => 'Expirado', 'class' => 'badge-soft-danger'];
        }

        return ['label' => 'Disponible', 'class' => 'badge-soft-warning'];
    };
    $failureStatus = fn ($status) => $status === 'resolved'
        ? ['label' => 'Resuelta', 'class' => 'badge-soft-success']
        : ['label' => 'Abierta', 'class' => 'badge-soft-danger'];
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Seguridad</h4>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-md-end gap-2">
            <form method="POST" action="{{ route('finance.security.backups.database') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i data-lucide="database-backup" class="me-1"></i>Backup solo BD
                </button>
            </form>
            <form method="POST" action="{{ route('finance.security.backups.full') }}" class="d-flex align-items-center gap-2">
                @csrf
                <button type="submit" class="btn btn-outline-success">
                    <i data-lucide="archive" class="me-1"></i>Backup completo
                </button>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1" name="include_env" id="security-include-env">
                    <label class="form-check-label" for="security-include-env">Incluir .env</label>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="alert alert-warning">
    <strong>.env contiene credenciales.</strong> Inclúyelo solo si necesitas restaurar el sistema completo.
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Backup externo</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.security.backups.external') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-xl-4 col-md-6">
                <label class="form-label">Ruta configurada</label>
                <input type="text" class="form-control" value="{{ $externalBackupPath ?: 'No configurada: FINANCE_EXTERNAL_BACKUP_PATH' }}" readonly>
                <div class="form-text">Debe existir y tener permisos de escritura. Ejemplo: FINANCE_EXTERNAL_BACKUP_PATH="C:/Users/axelg/OneDrive/BackupsFinanzas"</div>
            </div>
            <div class="col-xl-3 col-md-6">
                <label class="form-label">Acción</label>
                <select name="mode" class="form-select">
                    <option value="copy_latest">Copiar último backup local</option>
                    <option value="database">Generar BD y copiar</option>
                    <option value="full">Generar completo y copiar</option>
                </select>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="include_env" id="external-include-env">
                    <label class="form-check-label" for="external-include-env">Incluir .env si es backup completo</label>
                </div>
                <div class="form-text">Úsalo solo si necesitas restauración completa.</div>
            </div>
            <div class="col-xl-2 col-md-6 d-grid">
                <button type="submit" class="btn btn-outline-success">
                    <i data-lucide="hard-drive-upload" class="me-1"></i>Backup externo
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Backups generados</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Archivo</th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (collect($backups['database'] ?? [])->merge($backups['full'] ?? [])->sortByDesc('created_at') as $backup)
                                <tr>
                                    <td>
                                        <span class="badge {{ $backup['type'] === 'database' ? 'badge-soft-primary' : 'badge-soft-success' }}">
                                            {{ $backup['type'] === 'database' ? 'BD' : 'Completo' }}
                                        </span>
                                    </td>
                                    <td>{{ $backup['name'] }}</td>
                                    <td>{{ $bytes($backup['size'] ?? 0) }}</td>
                                    <td>{{ $dateTime($backup['created_at'] ?? null) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('finance.security.backups.download', ['type' => $backup['type'], 'filename' => $backup['name']]) }}" class="btn btn-sm btn-outline-primary">
                                            <i data-lucide="download"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sin backups generados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Exportaciones Excel</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exports as $export)
                                <tr>
                                    <td>{{ $export['name'] }}</td>
                                    <td>{{ $bytes($export['size'] ?? 0) }}</td>
                                    <td>{{ $dateTime($export['created_at'] ?? null) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Sin exportaciones generadas</td>
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
                <h4 class="card-title mb-0">Snapshots recientes</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Módulo</th>
                                <th>Registro</th>
                                <th>Estado</th>
                                <th>Expira</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($snapshots as $snapshot)
                                @php
                                    $status = $snapshotStatus($snapshot);
                                @endphp
                                <tr>
                                    <td>{{ $snapshot->entity_type }}</td>
                                    <td>#{{ $snapshot->entity_id }}</td>
                                    <td><span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                    <td>{{ $dateTime($snapshot->expires_at) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Sin snapshots recientes</td>
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
                <h4 class="card-title mb-0">Fallas financieras</h4>
            </div>
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('finance.security.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Módulo</label>
                        <select name="module" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($failureModules as $module)
                                <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ $module }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="open" @selected(($filters['status'] ?? '') === 'open')>Abierta</option>
                            <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Resuelta</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-1 d-flex justify-content-end">
                        <button class="btn btn-outline-primary" type="submit">
                            <i data-lucide="filter"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Módulo</th>
                                <th>Acción</th>
                                <th>Mensaje</th>
                                <th>Estado</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($failures as $failure)
                                @php
                                    $status = $failureStatus($failure->status);
                                @endphp
                                <tr>
                                    <td>{{ $dateTime($failure->occurred_at) }}</td>
                                    <td>{{ $failure->module }}</td>
                                    <td>{{ $failure->action }}</td>
                                    <td style="min-width: 260px;">
                                        {{ $failure->message }}
                                        @if (! empty($failure->context))
                                            <div class="text-muted small mt-1">{{ json_encode($failure->context, JSON_UNESCAPED_SLASHES) }}</div>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                    <td class="text-end">
                                        @if ($failure->status !== 'resolved')
                                            <form method="POST" action="{{ route('finance.security.failures.resolve', $failure) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Marcar resuelta">
                                                    <i data-lucide="check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin fallas registradas</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
