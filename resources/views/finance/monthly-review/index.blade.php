@extends('layouts.vertical', ['title' => 'Revisión mensual'])

@section('content')
@php
    $suggestions = collect($review['suggestions'] ?? []);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-7">
        <h4 class="mb-0 fw-semibold">Corrector mensual</h4>
        <p class="text-muted mb-0">Revisión local de textos, categorías y personas. Nada se cambia sin que presiones Aplicar.</p>
    </div>
    <div class="col-md-5 mt-2 mt-md-0">
        <form method="GET" action="{{ route('finance.monthly-review.index') }}" class="d-flex justify-content-md-end gap-2">
            <input type="month" name="month" class="form-control" style="max-width: 190px;" value="{{ $selectedMonth->format('Y-m') }}">
            <button type="submit" class="btn btn-outline-primary">
                <i data-lucide="search" class="me-1"></i>Revisar
            </button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Movimientos revisados</p>
                <h4 class="mb-0">{{ $review['movements_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Sugerencias encontradas</p>
                <h4 class="mb-0 text-warning">{{ $suggestions->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12">
        <div class="card mb-0 h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Aplicables de forma segura</p>
                <h4 class="mb-0 text-success">{{ $review['applyable_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Sugerencias</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Actual</th>
                        <th>Sugerencia</th>
                        <th>Motivo</th>
                        <th class="text-end">Afecta</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suggestions as $suggestion)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $suggestion['title'] }}</div>
                                @if ($suggestion['applyable'])
                                    <span class="badge badge-soft-success">Aplicable</span>
                                @else
                                    <span class="badge badge-soft-warning">Manual</span>
                                @endif
                            </td>
                            <td style="min-width: 180px;">{{ $suggestion['current'] }}</td>
                            <td style="min-width: 180px;">{{ $suggestion['suggestion'] }}</td>
                            <td style="min-width: 260px;">{{ $suggestion['reason'] }}</td>
                            <td class="text-end">{{ $suggestion['count'] }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @if ($suggestion['applyable'])
                                        <form method="POST" action="{{ route('finance.monthly-review.apply', ['key' => $suggestion['key'], 'month' => $selectedMonth->format('Y-m')]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i data-lucide="check" class="me-1"></i>Aplicar
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('finance.monthly-review.ignore', ['key' => $suggestion['key'], 'month' => $selectedMonth->format('Y-m')]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            Ignorar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No se encontraron textos o categorías para corregir en este mes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <strong>Regla de seguridad:</strong> las categorías parecidas solo se reportan. La unificación de historial se hace desde Categorías para evitar cambios masivos accidentales.
</div>
@endsection
