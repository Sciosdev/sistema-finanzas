@extends('layouts.vertical', ['title' => 'Vincular ingreso'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $received = (float) $income->received_amount;
    $remaining = max(0, (float) $income->amount - $received);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Vincular abonos al ingreso esperado</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('finance.expected-incomes.index', ['month' => $monthValue]) }}" class="btn btn-outline-primary">
            <i data-lucide="arrow-left" class="me-1"></i>Regresar
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $income->name }}</h4>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <p class="text-muted mb-1">Fecha esperada</p>
                <strong>{{ $income->due_date?->format('Y-m-d') ?? '-' }}</strong>
            </div>
            <div class="col-xl-3 col-md-6">
                <p class="text-muted mb-1">Monto esperado</p>
                <strong>{{ $money($income->amount) }}</strong>
            </div>
            <div class="col-xl-3 col-md-6">
                <p class="text-muted mb-1">Recibido acumulado</p>
                <strong class="text-success">{{ $money($received) }}</strong>
            </div>
            <div class="col-xl-3 col-md-6">
                <p class="text-muted mb-1">Saldo pendiente</p>
                <strong class="{{ $remaining > 0 ? 'text-warning' : 'text-success' }}">{{ $money($remaining) }}</strong>
            </div>
            <div class="col-xl-3 col-md-6">
                <p class="text-muted mb-1">Categoría</p>
                <strong>{{ $income->category?->name ?? '-' }}</strong>
            </div>
            <div class="col-xl-3 col-md-6">
                <p class="text-muted mb-1">Estado</p>
                <span class="badge {{ $income->status === 'received' ? 'badge-soft-success' : ($income->status === 'partial' ? 'badge-soft-primary' : 'badge-soft-warning') }}">
                    {{ $income->status === 'received' ? 'Recibido' : ($income->status === 'partial' ? 'Parcial' : 'Pendiente') }}
                </span>
                @if ($income->payments->isNotEmpty())
                    <span class="badge badge-soft-primary ms-1">{{ $income->payments->count() }} abono(s)</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($income->payments->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Abonos ligados</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Movimiento</th>
                            <th class="text-end">Aplicado</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($income->payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_on?->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    {{ $payment->movement?->description ?? 'Marcado como registrado' }}
                                    @if ($payment->notes)
                                        <div class="text-muted small">{{ $payment->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-success">{{ $money($payment->amount_applied) }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('finance.expected-incomes.payments.destroy', $payment) }}" onsubmit="return confirm('¿Desligar este abono del ingreso esperado?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                            <i data-lucide="unlink"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Movimientos de ingreso del mes</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Cuenta</th>
                        <th>Categoría</th>
                        <th>Persona</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Aplicar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php
                            $sameAmount = abs((float) $movement->amount - (float) $income->amount) <= 0.01;
                            $alreadyLinked = $income->payments->contains('movement_id', $movement->id);
                            $defaultApplied = min((float) $movement->amount, $remaining > 0 ? $remaining : (float) $movement->amount);
                        @endphp
                        <tr>
                            <td>{{ $movement->happened_on->format('Y-m-d') }}</td>
                            <td>
                                {{ $movement->description }}
                                @if ($sameAmount)
                                    <span class="badge badge-soft-success ms-1">Monto coincide</span>
                                @endif
                                @if ($movement->is_rent)
                                    <span class="badge badge-soft-success ms-1">Renta</span>
                                @endif
                                @if ($alreadyLinked)
                                    <span class="badge badge-soft-primary ms-1">Ya ligado</span>
                                @endif
                            </td>
                            <td>{{ $movement->account?->name ?? '-' }}</td>
                            <td>{{ $movement->category?->name ?? '-' }}</td>
                            <td>{{ $movement->person?->name ?? '-' }}</td>
                            <td class="text-end text-success">{{ $money($movement->amount) }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('finance.expected-incomes.link-movement', $income) }}" class="d-flex justify-content-end gap-1">
                                    @csrf
                                    <input type="hidden" name="movement_id" value="{{ $movement->id }}">
                                    <input type="number" name="amount_applied" class="form-control form-control-sm text-end" style="max-width: 120px" step="0.01" min="0.01" max="{{ $movement->amount }}" value="{{ $defaultApplied }}" title="Monto a aplicar" @disabled($alreadyLinked)>
                                    <button type="submit" class="btn btn-sm btn-success" @disabled($alreadyLinked)>
                                        <i data-lucide="link" class="me-1"></i>Aplicar abono
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Sin movimientos de ingreso para vincular</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
