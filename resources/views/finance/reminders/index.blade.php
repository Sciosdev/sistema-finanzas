@extends('layouts.vertical', ['title' => 'Recordatorios'])

@section('content')
@php
    $money = fn ($value) => $value === null || $value === '' ? '-' : '$' . number_format((float) $value, 2);
    $editing = (bool) $editReminder;
    $formReminder = $editReminder;
    $statusLabel = fn ($status) => match ($status) {
        'done' => 'Hecho',
        'skipped' => 'Omitido',
        default => 'Pendiente',
    };
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-8">
        <h4 class="mb-0 fw-semibold">Recordatorios</h4>
        <p class="text-muted mb-0">Carro, moto, refrendo, verificación y pagos recurrentes que no deben olvidarse.</p>
    </div>
    <div class="col-md-4 text-md-end mt-2 mt-md-0">
        <a href="{{ route('finance.operations.index') }}" class="btn btn-outline-primary">
            <i data-lucide="settings-2" class="me-1"></i>Operación
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $editing ? 'Editar recordatorio' : 'Nuevo recordatorio' }}</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $editing ? route('finance.reminders.update', $formReminder) : route('finance.reminders.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-xl-4 col-md-6">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" required value="{{ old('title', $formReminder?->title) }}" placeholder="Refrendo carro, Verificación moto...">
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Tipo</label>
                    <select name="reminder_type" class="form-select" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('reminder_type', $formReminder?->reminder_type ?? 'other') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Vehículo</label>
                    <select name="vehicle_type" class="form-select">
                        <option value="">No aplica</option>
                        @foreach ($vehicles as $value => $label)
                            <option value="{{ $value }}" @selected(old('vehicle_type', $formReminder?->vehicle_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="due_date" class="form-control" required value="{{ old('due_date', $formReminder?->due_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Monto estimado</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0" value="{{ old('amount', $formReminder?->amount) }}">
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Recurrencia</label>
                    <select name="recurrence" class="form-select" required>
                        @foreach ($recurrences as $value => $label)
                            <option value="{{ $value }}" @selected(old('recurrence', $formReminder?->recurrence ?? 'none') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Avisar días antes</label>
                    <input type="number" name="notify_days_before" class="form-control" min="0" max="365" value="{{ old('notify_days_before', $formReminder?->notify_days_before ?? 15) }}">
                </div>
                @if ($editing)
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="pending" @selected(old('status', $formReminder?->status) === 'pending')>Pendiente</option>
                            <option value="done" @selected(old('status', $formReminder?->status) === 'done')>Hecho</option>
                            <option value="skipped" @selected(old('status', $formReminder?->status) === 'skipped')>Omitido</option>
                        </select>
                    </div>
                @endif
                <div class="col-xl-{{ $editing ? '4' : '6' }} col-md-12">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes', $formReminder?->notes) }}" placeholder="Lugar, placas, folio, comentario...">
                </div>
                <div class="col-xl-2 col-md-12 d-flex align-items-end justify-content-end gap-2">
                    @if ($editing)
                        <a href="{{ route('finance.reminders.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="me-1"></i>{{ $editing ? 'Guardar' : 'Agregar' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
        <h4 class="card-title mb-0">Listado</h4>
        <form method="GET" action="{{ route('finance.reminders.index') }}" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="width: 160px;">
                <option value="pending" @selected($status === 'pending')>Pendientes</option>
                <option value="done" @selected($status === 'done')>Hechos</option>
                <option value="skipped" @selected($status === 'skipped')>Omitidos</option>
                <option value="all" @selected($status === 'all')>Todos</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" type="submit">
                <i data-lucide="filter" class="me-1"></i>Filtrar
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Recordatorio</th>
                        <th>Tipo</th>
                        <th>Vehículo</th>
                        <th>Pronto aviso</th>
                        <th>Recurrencia</th>
                        <th class="text-end">Monto</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reminders as $reminder)
                        <tr>
                            <td>{{ $reminder->due_date->format('Y-m-d') }}</td>
                            <td>
                                {{ $reminder->title }}
                                @if ($reminder->notes)
                                    <div class="text-muted small">{{ $reminder->notes }}</div>
                                @endif
                            </td>
                            <td>{{ $types[$reminder->reminder_type] ?? $reminder->reminder_type }}</td>
                            <td>{{ $reminder->vehicle_type ? ($vehicles[$reminder->vehicle_type] ?? $reminder->vehicle_type) : '-' }}</td>
                            <td>
                                <span class="badge {{ \App\Support\FinanceLabels::dueBadgeClass($reminder->due_date, $reminder->status) }}">
                                    {{ \App\Support\FinanceLabels::dueLabel($reminder->due_date, $reminder->status) }}
                                </span>
                            </td>
                            <td>{{ $recurrences[$reminder->recurrence] ?? $reminder->recurrence }}</td>
                            <td class="text-end">{{ $money($reminder->amount) }}</td>
                            <td>{{ $statusLabel($reminder->status) }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('finance.reminders.index', ['edit' => $reminder->id, 'status' => $status]) }}" title="Editar">
                                        <i data-lucide="pencil"></i>
                                    </a>
                                    @if ($reminder->status === 'pending')
                                        <form method="POST" action="{{ route('finance.reminders.complete', $reminder) }}">
                                            @csrf
                                            <input type="hidden" name="completed_on" value="{{ now()->toDateString() }}">
                                            <button type="submit" class="btn btn-sm btn-success" title="Marcar hecho">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('finance.reminders.skip', $reminder) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Omitir">
                                                <i data-lucide="x"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Sin recordatorios para este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
