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

@include('finance.partials.money-overview')

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
                        @php($rec = $reconciliations[$cut->id] ?? [])
                        @php($hasBaseline = collect($rec)->contains(fn ($r) => $r['has_baseline']))
                        @php($mismatched = collect($rec)->filter(fn ($r) => $r['has_baseline'] && abs($r['difference']) > 0.01))
                        <tr>
                            <td>
                                <button type="button" class="btn btn-sm btn-link p-0 me-1 align-baseline" data-bs-toggle="collapse" data-bs-target="#cut-detail-{{ $cut->id }}" aria-expanded="false" title="Ver diferencia por cuenta">
                                    <i data-lucide="chevron-down" class="fs-16"></i>
                                </button>
                                {{ $cut->cut_date->format('Y-m-d') }}
                                @if ($mismatched->isNotEmpty())
                                    <span class="badge badge-soft-danger ms-1">{{ $mismatched->count() }} descuadre(s)</span>
                                @endif
                            </td>
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
                        <tr class="cut-detail-row">
                            <td colspan="9" class="p-0 border-0">
                                <div class="collapse" id="cut-detail-{{ $cut->id }}">
                                    <div class="p-3 bg-body-tertiary">
                                        @if (! $hasBaseline)
                                            <p class="text-muted mb-0 small">
                                                <i data-lucide="flag" class="me-1"></i>Primer corte: es tu punto de partida, todavía no hay diferencia por comparar.
                                            </p>
                                        @else
                                            <h6 class="mb-2">Diferencia por cuenta</h6>
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Cuenta</th>
                                                        <th class="text-end">Esperado</th>
                                                        <th class="text-end">Declarado</th>
                                                        <th class="text-end">Diferencia</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($rec as $row)
                                                        @php($diff = (float) $row['difference'])
                                                        @php($cuadra = abs($diff) <= 0.01)
                                                        <tr>
                                                            <td>
                                                                <span class="rounded-circle d-inline-block me-1" style="width: 10px; height: 10px; background: {{ $row['color'] ?: '#4d5761' }}"></span>
                                                                {{ $row['name'] }}
                                                            </td>
                                                            <td class="text-end">{{ $money($row['expected']) }}</td>
                                                            <td class="text-end">{{ $money($row['real']) }}</td>
                                                            <td class="text-end fw-semibold {{ $cuadra ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-warning') }}">
                                                                {{ $cuadra ? 'Cuadra' : (($diff < 0 ? 'Falta ' : 'Sobra ') . $money(abs($diff))) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
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

@section('scripts')
<script>
    // Diferencia por cuenta EN VIVO al capturar el corte: compara lo que escribes
    // contra lo esperado (saldo del corte anterior + movimientos), igual que el
    // backend. Solo informativo; no envía nada extra ni cambia el cálculo.
    document.addEventListener('DOMContentLoaded', function () {
        const numberFormat = (value) => value
            .toFixed(2)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        document.querySelectorAll('[data-cut-form]').forEach(function (form) {
            const inputs = form.querySelectorAll('[data-cut-balance][data-expected]');

            if (!inputs.length) {
                return;
            }

            const summary = form.querySelector('[data-cut-summary]');

            const refresh = () => {
                const mismatches = [];

                inputs.forEach(function (input) {
                    const expected = parseFloat(input.dataset.expected);
                    const real = parseFloat(input.value);
                    const note = input.closest('.col-md-4').querySelector('[data-cut-diff]');

                    if (!note) {
                        return;
                    }

                    if (!isFinite(expected) || input.value === '' || !isFinite(real)) {
                        note.textContent = '';
                        note.className = 'd-block';
                        return;
                    }

                    const diff = Math.round((real - expected) * 100) / 100;
                    const esperado = 'Esperado $' + numberFormat(expected);

                    if (Math.abs(diff) <= 0.01) {
                        note.textContent = esperado + ' · cuadra';
                        note.className = 'd-block text-success';
                    } else if (diff < 0) {
                        note.textContent = esperado + ' · faltan $' + numberFormat(Math.abs(diff));
                        note.className = 'd-block text-danger';
                        mismatches.push((input.dataset.accountName || 'Cuenta') + ' (−$' + numberFormat(Math.abs(diff)) + ')');
                    } else {
                        note.textContent = esperado + ' · sobran $' + numberFormat(diff);
                        note.className = 'd-block text-warning';
                        mismatches.push((input.dataset.accountName || 'Cuenta') + ' (+$' + numberFormat(diff) + ')');
                    }
                });

                if (summary) {
                    if (mismatches.length) {
                        summary.classList.remove('d-none', 'alert-secondary', 'alert-success');
                        summary.classList.add('alert-warning');
                        summary.innerHTML = '<strong>Cuentas que no cuadran:</strong> ' + mismatches.join(', ');
                    } else {
                        summary.classList.remove('d-none', 'alert-warning', 'alert-secondary');
                        summary.classList.add('alert-success');
                        summary.textContent = 'Todas las cuentas cuadran con lo esperado.';
                    }
                }
            };

            inputs.forEach((input) => input.addEventListener('input', refresh));
            refresh();
        });
    });
</script>
@endsection
