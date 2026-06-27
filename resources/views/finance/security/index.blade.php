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
    $backupType = function ($type) {
        return match ($type) {
            'database' => ['label' => 'BD', 'class' => 'badge-soft-primary'],
            'full' => ['label' => 'Completo', 'class' => 'badge-soft-success'],
            'migration' => ['label' => 'Migracion', 'class' => 'badge-soft-warning'],
            default => ['label' => $type, 'class' => 'badge-soft-secondary'],
        };
    };
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Seguridad</h4>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-md-end gap-2">
            <form method="POST" action="{{ route('finance.security.backups.migration') }}">
                @csrf
                <button type="submit" class="btn btn-outline-warning">
                    <i data-lucide="package-open" class="me-1"></i>Paquete migracion
                </button>
            </form>
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

<div class="alert alert-info">
    <strong>Paquete migracion:</strong> genera un zip con SQL importable en Laragon/HeidiSQL, sin .env ni credenciales.
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

<div class="card border-warning border-opacity-50">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="card-title mb-0"><i data-lucide="wrench" class="me-1"></i>Mantenimiento</h4>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge badge-soft-secondary">APP_ENV: {{ $maintenance['app_env'] ?? '-' }}</span>
            <span class="badge badge-soft-secondary">DB: {{ $maintenance['db_connection'] ?? '-' }}</span>
            <span class="badge {{ ($maintenance['migrations_table_exists'] ?? false) ? 'badge-soft-success' : 'badge-soft-danger' }}">
                Tabla migrations: {{ ($maintenance['migrations_table_exists'] ?? false) ? 'sí' : 'no' }}
            </span>
            @if (($maintenance['pending_count'] ?? 0) > 0)
                <span class="badge badge-soft-warning">{{ $maintenance['pending_count'] }} migración(es) pendiente(s)</span>
                @if ($maintenance['has_destructive_pending'] ?? false)
                    <span class="badge badge-soft-danger">posible pérdida de datos</span>
                @endif
            @else
                <span class="badge badge-soft-success">Sin migraciones pendientes</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Backup automático antes de migrar.</strong> Al ejecutar migraciones, el sistema crea primero un <em>Paquete de migración</em> (zip) de respaldo; si ese backup falla, <strong>no</strong> migra. Aun así, te recomendamos tener también un backup descargado a tu equipo.
        </div>

        @if (($maintenance['pending_count'] ?? 0) > 0)
            <div class="alert alert-warning">
                <strong>Hay {{ $maintenance['pending_count'] }} migración(es) pendiente(s).</strong> Revísalas y ejecútalas con el botón de abajo (se crea un backup automático antes).
                <ul class="mb-0 mt-2">
                    @foreach ($maintenance['pending'] as $pendingMigration)
                        <li>
                            <code>{{ $pendingMigration['name'] }}</code>
                            @if ($pendingMigration['destructive'])
                                <span class="badge badge-soft-danger ms-1">posible pérdida de datos</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @if ($maintenance['has_destructive_pending'] ?? false)
                <div class="alert alert-danger">
                    <i data-lucide="alert-triangle" class="me-1"></i>
                    <strong>Aviso:</strong> una o más migraciones pendientes contienen operaciones que <strong>podrían borrar datos</strong> (eliminar tablas/columnas, TRUNCATE o DELETE). Se hará un backup automático antes, pero revisa bien antes de ejecutar.
                </div>
            @endif
        @endif

        <h6 class="mb-2">Estado de migraciones (migrate:status)</h6>
        <pre class="bg-body-tertiary p-3 rounded small mb-3" style="max-height: 260px; overflow:auto;">{{ $maintenance['status_output'] ?? 'Sin información.' }}</pre>

        @if (! empty($maintenanceResult))
            <h6 class="mb-2">Último resultado de mantenimiento</h6>
            <div class="alert {{ ($maintenanceResult['ok'] ?? false) ? 'alert-success' : 'alert-danger' }}">
                <div class="fw-semibold mb-1">Comando: <code>{{ $maintenanceResult['action'] ?? '-' }}</code> — {{ ($maintenanceResult['ok'] ?? false) ? 'OK' : 'Falló' }}</div>
                <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $maintenanceResult['output'] ?? '' }}</pre>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <form method="POST" action="{{ route('finance.maintenance.run-migrations') }}" class="border rounded p-3 h-100"
                      onsubmit="return confirm('Se creará un backup automático y luego se ejecutarán las migraciones pendientes. ¿Continuar?');">
                    @csrf
                    <h6 class="mb-1">Ejecutar migraciones pendientes</h6>
                    <p class="text-muted small mb-2">Equivale a <code>php artisan migrate --force</code>. Crea un backup automático y luego aplica solo las pendientes.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="confirm_backup" id="confirm_backup" required>
                        <label class="form-check-label" for="confirm_backup">
                            Confirmo: crear backup automático y ejecutar las migraciones pendientes
                        </label>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i data-lucide="database" class="me-1"></i>Ejecutar migraciones
                    </button>
                </form>
            </div>
            <div class="col-lg-6">
                <form method="POST" action="{{ route('finance.maintenance.clear-cache') }}" class="border rounded p-3 h-100"
                      onsubmit="return confirm('¿Limpiar caché de configuración, rutas y vistas?');">
                    @csrf
                    <h6 class="mb-1">Limpiar caché</h6>
                    <p class="text-muted small mb-2">Equivale a <code>php artisan optimize:clear</code>. No toca datos.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="confirm_clear" id="confirm_clear" required>
                        <label class="form-check-label" for="confirm_clear">
                            Confirmo limpiar la caché
                        </label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">
                        <i data-lucide="eraser" class="me-1"></i>Limpiar caché
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-danger">
    <div class="card-header">
        <h4 class="card-title mb-0 text-danger"><i data-lucide="alert-triangle" class="me-1"></i>Restaurar (reemplaza TODA la base)</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <strong>Cuidado: esto borra todos tus datos actuales</strong> y los reemplaza por los del paquete. Es irreversible. El sistema crea un <em>backup automático antes</em> (red de seguridad), pero tu base quedará exactamente como estaba en ese respaldo. Para confirmar, escribe <code>RESTAURAR</code>.
        </div>

        <form method="POST" action="{{ route('finance.security.restore.backup') }}" class="row g-2 align-items-end mb-3"
              onsubmit="return confirm('Esto BORRA tus datos actuales y los reemplaza por el respaldo elegido. ¿Continuar?');">
            @csrf
            <div class="col-lg-5">
                <label class="form-label">Restaurar un respaldo guardado</label>
                <select name="backup" class="form-select" required>
                    <option value="">Selecciona un respaldo…</option>
                    @foreach (($backups['migration'] ?? []) as $item)
                        <option value="migration::{{ $item['name'] }}">Migración — {{ $item['name'] }}</option>
                    @endforeach
                    @foreach (($backups['database'] ?? []) as $item)
                        <option value="database::{{ $item['name'] }}">BD — {{ $item['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label class="form-label">Escribe RESTAURAR</label>
                <input type="text" name="confirm_phrase" class="form-control" placeholder="RESTAURAR" autocomplete="off" required>
            </div>
            <div class="col-lg-3 d-grid">
                <button type="submit" class="btn btn-danger">
                    <i data-lucide="history" class="me-1"></i>Restaurar guardado
                </button>
            </div>
        </form>

        <hr>

        <form method="POST" action="{{ route('finance.security.restore.upload') }}" enctype="multipart/form-data" class="row g-2 align-items-end"
              onsubmit="return confirm('Esto BORRA tus datos actuales y los reemplaza por el archivo subido. ¿Continuar?');">
            @csrf
            <div class="col-lg-5">
                <label class="form-label">Restaurar desde archivo (.zip de respaldo)</label>
                <input type="file" name="package" accept=".zip" class="form-control" required>
            </div>
            <div class="col-lg-4">
                <label class="form-label">Escribe RESTAURAR</label>
                <input type="text" name="confirm_phrase" class="form-control" placeholder="RESTAURAR" autocomplete="off" required>
            </div>
            <div class="col-lg-3 d-grid">
                <button type="submit" class="btn btn-outline-danger">
                    <i data-lucide="upload" class="me-1"></i>Subir y restaurar
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
                            @forelse (collect($backups['database'] ?? [])->merge($backups['full'] ?? [])->merge($backups['migration'] ?? [])->sortByDesc('created_at') as $backup)
                                @php
                                    $type = $backupType($backup['type']);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $type['class'] }}">
                                            {{ $type['label'] }}
                                        </span>
                                    </td>
                                    <td>{{ $backup['name'] }}</td>
                                    <td>{{ $bytes($backup['size'] ?? 0) }}</td>
                                    <td>{{ $dateTime($backup['created_at'] ?? null) }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <a href="{{ route('finance.security.backups.download', ['type' => $backup['type'], 'filename' => $backup['name']]) }}" class="btn btn-sm btn-outline-primary" title="Descargar">
                                                <i data-lucide="download"></i>
                                            </a>
                                            @if (in_array($backup['type'], ['migration', 'database'], true))
                                                <form method="POST" action="{{ route('finance.security.restore.backup') }}" class="d-inline" onsubmit="return financeConfirmRestore(this);">
                                                    @csrf
                                                    <input type="hidden" name="backup" value="{{ $backup['type'] }}::{{ $backup['name'] }}">
                                                    <input type="hidden" name="confirm_phrase" value="">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Restaurar este respaldo (reemplaza toda la base)">
                                                        <i data-lucide="history"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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

@section('scripts')
<script>
    window.financeConfirmRestore = function (form) {
        var phrase = window.prompt('CUIDADO: esto BORRA tus datos actuales y los reemplaza por este respaldo. Es irreversible (se crea un backup automático antes).\n\nEscribe RESTAURAR para confirmar:');

        if (phrase === null) {
            return false;
        }

        var input = form.querySelector('input[name="confirm_phrase"]');
        if (input) {
            input.value = phrase;
        }

        return true;
    };
</script>
@endsection
