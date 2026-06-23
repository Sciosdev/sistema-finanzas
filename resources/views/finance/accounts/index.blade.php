@extends('layouts.vertical', ['title' => 'Cuentas'])

@section('content')
@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-8">
        <h4 class="mb-0 fw-semibold">Cuentas financieras</h4>
        <p class="text-muted mb-0">Administra efectivo, bancos, tarjetas, créditos y billeteras sin perder historial.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nueva cuenta</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.accounts.store') }}" class="needs-validation" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Onix, Santander, Caja..." required>
                </div>
                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select" required>
                        @foreach ($typeOptions as $type => $label)
                            <option value="{{ $type }}" @selected(old('type', 'card') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-control form-control-color w-100" value="{{ old('color', '#4d5761') }}">
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">Orden</label>
                    <input type="number" name="display_order" class="form-control" min="0" max="9999" value="{{ old('display_order', 80) }}">
                </div>
                <div class="col-xl-3 col-md-4">
                    <label class="form-label d-block">Estado</label>
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new_account_active" @checked(old('is_active', '1'))>
                        <label class="form-check-label" for="new_account_active">Activa para capturas nuevas</label>
                    </div>
                </div>
                <div class="col-xl-10">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Uso, banco, tarjeta, límite o aclaración interna">
                </div>
                <div class="col-xl-2 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-lucide="plus" class="me-1"></i>Agregar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($accounts->groupBy('type') as $type => $rows)
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="border rounded p-3 h-100">
                <div class="text-muted small">{{ $typeOptions[$type] ?? ucfirst($type) }}</div>
                <div class="fs-4 fw-semibold">{{ $rows->where('is_active', true)->count() }}</div>
                <div class="text-muted small">activas de {{ $rows->count() }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h4 class="card-title mb-0">Catálogo de cuentas</h4>
            <p class="text-muted mb-0 small">Desactivar una cuenta la oculta de formularios nuevos, pero conserva movimientos, créditos, cortes e historial.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary">{{ $accounts->where('is_active', true)->count() }} activas</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cuenta</th>
                        <th>Tipo</th>
                        <th>Color</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Notas</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        @php($formId = 'account_form_' . $account->id)
                        <tr>
                            <td style="min-width: 180px;">
                                <input form="{{ $formId }}" type="text" name="name" class="form-control form-control-sm" value="{{ old('accounts.' . $account->id . '.name', $account->name) }}" required>
                            </td>
                            <td style="min-width: 150px;">
                                <select form="{{ $formId }}" name="type" class="form-select form-select-sm" required>
                                    @foreach ($typeOptions as $type => $label)
                                        <option value="{{ $type }}" @selected($account->type === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="min-width: 120px;">
                                <div class="d-flex align-items-center gap-2">
                                    <input form="{{ $formId }}" type="color" name="color" class="form-control form-control-color form-control-sm" value="{{ $account->color ?: '#4d5761' }}">
                                    <span class="rounded-circle d-inline-block" style="width: 18px; height: 18px; background: {{ $account->color ?: '#4d5761' }}"></span>
                                </div>
                            </td>
                            <td style="width: 110px;">
                                <input form="{{ $formId }}" type="number" name="display_order" class="form-control form-control-sm" min="0" max="9999" value="{{ $account->display_order }}">
                            </td>
                            <td style="min-width: 150px;">
                                <input form="{{ $formId }}" type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch">
                                    <input form="{{ $formId }}" class="form-check-input" type="checkbox" name="is_active" value="1" id="account_active_{{ $account->id }}" @checked($account->is_active)>
                                    <label class="form-check-label" for="account_active_{{ $account->id }}">
                                        {{ $account->is_active ? 'Activa' : 'Inactiva' }}
                                    </label>
                                </div>
                            </td>
                            <td style="min-width: 260px;">
                                <input form="{{ $formId }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $account->notes }}">
                            </td>
                            <td class="text-end">
                                <form id="{{ $formId }}" method="POST" action="{{ route('finance.accounts.update', $account) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i data-lucide="save" class="me-1"></i>Guardar
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
@endsection
