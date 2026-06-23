@extends('layouts.vertical', ['title' => 'Editar movimiento'])

@section('content')
@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <h4 class="mb-0 fw-semibold">Editar movimiento</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('finance.movements.index', ['month' => $monthValue]) }}" class="btn btn-outline-primary">
            <i data-lucide="arrow-left" class="me-1"></i>Regresar
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ $movement->description }}</h4>
    </div>
    <div class="card-body">
        @include('finance.partials.movement-form', [
            'formMovement' => $movement,
            'formAction' => route('finance.movements.update', $movement),
            'formMethod' => 'PUT',
            'submitLabel' => 'Actualizar',
            'submitIcon' => 'save',
        ])
    </div>
</div>
@endsection
