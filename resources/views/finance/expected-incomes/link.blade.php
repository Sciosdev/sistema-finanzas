@extends('layouts.vertical', ['title' => 'Vincular ingreso'])

@section('content')
@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
@endphp

@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Vincular ingreso esperado</h4>
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
            <div class="col-md-3">
                <p class="text-muted mb-1">Fecha esperada</p>
                <strong>{{ $income->due_date?->format('Y-m-d') ?? '-' }}</strong>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Monto esperado</p>
                <strong>{{ $money($income->amount) }}</strong>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Categoría</p>
                <strong>{{ $income->category?->name ?? '-' }}</strong>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Estado</p>
                <span class="badge {{ $income->status === 'received' ? 'badge-soft-success' : 'badge-soft-warning' }}">
                    {{ $income->status === 'received' ? 'Recibido' : 'Pendiente' }}
                </span>
                @if ($income->movement_id)
                    <span class="badge badge-soft-primary ms-1">Ligado</span>
                @endif
            </div>
        </div>
    </div>
</div>

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
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php
                            $sameAmount = abs((float) $movement->amount - (float) $income->amount) <= 0.01;
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
                            </td>
                            <td>{{ $movement->account?->name ?? '-' }}</td>
                            <td>{{ $movement->category?->name ?? '-' }}</td>
                            <td>{{ $movement->person?->name ?? '-' }}</td>
                            <td class="text-end text-success">{{ $money($movement->amount) }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('finance.expected-incomes.link-movement', $income) }}">
                                    @csrf
                                    <input type="hidden" name="movement_id" value="{{ $movement->id }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i data-lucide="link" class="me-1"></i>Vincular
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
