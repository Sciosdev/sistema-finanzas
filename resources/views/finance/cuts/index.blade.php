@extends('layouts.vertical', ['title' => 'Cortes'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Cortes diarios</h4>
    </div>
    <div class="col-md-6">
        <form method="GET" action="{{ route('finance.cuts.index') }}" class="d-flex justify-content-md-end gap-2">
            <input type="month" name="month" class="form-control" style="max-width: 180px" value="{{ $monthValue }}">
            <button class="btn btn-outline-primary" type="submit">
                <i data-lucide="calendar-search" class="me-1"></i>Ver
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nuevo corte</h4>
    </div>
    <div class="card-body">
        @include('finance.partials.cut-form')
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Historial de cortes</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Saldo proyectado</th>
                        <th class="text-end">Tarjetas</th>
                        <th class="text-end">Efectivo</th>
                        <th class="text-end">Saldo real</th>
                        <th class="text-end">Diferencia de conciliación</th>
                        <th class="text-end">Obligaciones pendientes</th>
                        <th class="text-end">Saldo disponible después de obligaciones</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cuts as $cut)
                        <tr>
                            <td>{{ $cut->cut_date->format('Y-m-d') }}</td>
                            <td class="text-end">{{ $money($cut->expected_leftover) }}</td>
                            <td class="text-end">{{ $money($cut->cards_amount) }}</td>
                            <td class="text-end">{{ $money($cut->cash_amount) }}</td>
                            <td class="text-end">{{ $money($cut->real_total) }}</td>
                            <td class="text-end {{ abs((float) $cut->difference) <= 0.01 ? 'text-success' : 'text-danger' }}">{{ $money($cut->difference) }}</td>
                            <td class="text-end">{{ $money($cut->pending_payments) }}</td>
                            <td class="text-end {{ (float) $cut->amount_missing < 0 ? 'text-danger' : 'text-success' }}">{{ $money($cut->amount_missing) }}</td>
                            <td>
                                <span class="badge {{ $cut->status === 'ok' ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                    {{ $cut->status === 'ok' ? 'Cuadra' : 'Revisar' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Sin cortes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
