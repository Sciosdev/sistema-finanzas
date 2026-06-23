@extends('layouts.vertical', ['title' => 'Categorías'])

@section('content')
@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-12">
        <h4 class="mb-0 fw-semibold">Categorías</h4>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Nueva categoría</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.categories.store') }}" class="needs-validation" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select" required>
                        <option value="expense" @selected(old('type') === 'expense')>Egreso</option>
                        <option value="income" @selected(old('type') === 'income')>Ingreso</option>
                        <option value="yield" @selected(old('type') === 'yield')>Rendimiento</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grupo</label>
                    <input type="text" name="group" class="form-control" value="{{ old('group', 'Diario') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-control form-control-color w-100" value="{{ old('color', '#4d5761') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label d-block">Marcas</label>
                    <div class="d-flex flex-wrap gap-3 pt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_san_juan" value="1" id="category_san_juan" @checked(old('is_san_juan'))>
                            <label class="form-check-label" for="category_san_juan">SNJ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_rent" value="1" id="category_rent" @checked(old('is_rent'))>
                            <label class="form-check-label" for="category_rent">Renta</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-10">
                    <label class="form-label">Palabras clave</label>
                    <input type="text" name="keywords" class="form-control" value="{{ old('keywords') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-lucide="plus" class="me-1"></i>Agregar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Resumen por grupo contable</h4>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse ($categoryGroups as $group)
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <div>
                                <strong>{{ $group['group'] }}</strong>
                                <div class="text-muted small">
                                    @if ($group['type'] === 'expense')
                                        Egreso
                                    @elseif ($group['type'] === 'income')
                                        Ingreso
                                    @else
                                        Rendimiento
                                    @endif
                                </div>
                            </div>
                            <span class="badge badge-soft-primary">{{ $group['active_count'] }}/{{ $group['count'] }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @forelse ($group['colors'] as $color)
                                <span class="rounded-circle d-inline-block" title="{{ $color }}" style="width: 16px; height: 16px; background: {{ $color }}"></span>
                            @empty
                                <span class="text-muted small">Sin color</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted mb-0">Sin categorías para agrupar.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Categorías generales sugeridas</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Grupo</th>
                                <th>Color</th>
                                <th>Palabras clave</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categorySuggestions as $suggestion)
                                <tr>
                                    <td>{{ $suggestion['name'] }}</td>
                                    <td>
                                        @if ($suggestion['type'] === 'expense')
                                            Egreso
                                        @elseif ($suggestion['type'] === 'income')
                                            Ingreso
                                        @else
                                            Rendimiento
                                        @endif
                                    </td>
                                    <td>{{ $suggestion['group'] }}</td>
                                    <td>
                                        <span class="rounded-circle d-inline-block me-2" style="width: 12px; height: 12px; background: {{ $suggestion['color'] }}"></span>
                                        {{ $suggestion['color'] }}
                                    </td>
                                    <td style="min-width: 260px;">{{ $suggestion['keywords'] }}</td>
                                    <td class="text-end">
                                        @if ($suggestion['exists'])
                                            <span class="badge bg-success-subtle text-success">Ya existe</span>
                                        @else
                                            <form method="POST" action="{{ route('finance.categories.store') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="name" value="{{ $suggestion['name'] }}">
                                                <input type="hidden" name="type" value="{{ $suggestion['type'] }}">
                                                <input type="hidden" name="group" value="{{ $suggestion['group'] }}">
                                                <input type="hidden" name="color" value="{{ $suggestion['color'] }}">
                                                <input type="hidden" name="keywords" value="{{ $suggestion['keywords'] }}">
                                                <input type="hidden" name="is_san_juan" value="{{ $suggestion['is_san_juan'] ? 1 : 0 }}">
                                                <input type="hidden" name="is_rent" value="{{ $suggestion['is_rent'] ? 1 : 0 }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i data-lucide="plus" class="me-1"></i>Crear
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Posibles categorías repetidas</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Este bloque avisa y permite unificar solo cuando presionas un botón. El historial se mueve a la categoría destino y las categorías origen quedan inactivas.
                </p>

                @if ($duplicateCategoryGroups->isEmpty() && $similarCategoryPairs->isEmpty())
                    <p class="mb-0 text-success">No encontré nombres repetidos o muy parecidos.</p>
                @endif

                @foreach ($duplicateCategoryGroups as $group)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold mb-1">Nombre repetido exacto</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($group as $category)
                                <span class="badge bg-warning-subtle text-warning">
                                    {{ $category->name }} / {{ $category->type }}
                                </span>
                            @endforeach
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($group as $targetCategory)
                                <form method="POST" action="{{ route('finance.categories.merge', $targetCategory) }}" class="d-inline" onsubmit="return confirm('Se moverá el historial a la categoría destino y la categoría origen quedará inactiva. ¿Continuar?')">
                                    @csrf
                                    <input type="hidden" name="confirm_merge" value="1">
                                    @foreach ($group->where('id', '!=', $targetCategory->id) as $sourceCategory)
                                        <input type="hidden" name="source_category_ids[]" value="{{ $sourceCategory->id }}">
                                    @endforeach
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        Unificar en {{ $targetCategory->name }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @foreach ($similarCategoryPairs as $pair)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold mb-1">{{ $pair['reason'] }}</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-info-subtle text-info">{{ $pair['left']->name }}</span>
                            <span class="badge bg-info-subtle text-info">{{ $pair['right']->name }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <form method="POST" action="{{ route('finance.categories.merge', $pair['left']) }}" class="d-inline" onsubmit="return confirm('Se moverá el historial a la categoría destino y la categoría origen quedará inactiva. ¿Continuar?')">
                                @csrf
                                <input type="hidden" name="confirm_merge" value="1">
                                <input type="hidden" name="source_category_ids[]" value="{{ $pair['right']->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-info">
                                    Unificar en {{ $pair['left']->name }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('finance.categories.merge', $pair['right']) }}" class="d-inline" onsubmit="return confirm('Se moverá el historial a la categoría destino y la categoría origen quedará inactiva. ¿Continuar?')">
                                @csrf
                                <input type="hidden" name="confirm_merge" value="1">
                                <input type="hidden" name="source_category_ids[]" value="{{ $pair['left']->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-info">
                                    Unificar en {{ $pair['right']->name }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Catálogo</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Grupo</th>
                        <th>Color</th>
                        <th>Palabras clave</th>
                        <th>Marcas</th>
                        <th>Estado</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        @php
                            $formId = 'category-form-' . $category->id;
                        @endphp
                        <tr>
                            <td style="min-width: 220px;">
                                <form id="{{ $formId }}" method="POST" action="{{ route('finance.categories.update', $category) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="is_san_juan" value="0">
                                    <input type="hidden" name="is_rent" value="0">
                                    <input type="hidden" name="is_active" value="0">
                                </form>
                                <input form="{{ $formId }}" type="text" name="name" class="form-control form-control-sm" value="{{ $category->name }}" required>
                            </td>
                            <td style="min-width: 150px;">
                                <select form="{{ $formId }}" name="type" class="form-select form-select-sm" required>
                                    <option value="expense" @selected($category->type === 'expense')>Egreso</option>
                                    <option value="income" @selected($category->type === 'income')>Ingreso</option>
                                    <option value="yield" @selected($category->type === 'yield')>Rendimiento</option>
                                </select>
                            </td>
                            <td style="min-width: 160px;">
                                <input form="{{ $formId }}" type="text" name="group" class="form-control form-control-sm" value="{{ $category->group }}">
                            </td>
                            <td style="min-width: 90px;">
                                <input form="{{ $formId }}" type="color" name="color" class="form-control form-control-color form-control-sm w-100" value="{{ $category->color ?: '#4d5761' }}">
                            </td>
                            <td style="min-width: 260px;">
                                <input form="{{ $formId }}" type="text" name="keywords" class="form-control form-control-sm" value="{{ $category->keywords }}">
                            </td>
                            <td style="min-width: 170px;">
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input form="{{ $formId }}" class="form-check-input" type="checkbox" name="is_san_juan" value="1" id="category-snj-{{ $category->id }}" @checked($category->is_san_juan)>
                                        <label class="form-check-label" for="category-snj-{{ $category->id }}">SNJ</label>
                                    </div>
                                    <div class="form-check">
                                        <input form="{{ $formId }}" class="form-check-input" type="checkbox" name="is_rent" value="1" id="category-rent-{{ $category->id }}" @checked($category->is_rent)>
                                        <label class="form-check-label" for="category-rent-{{ $category->id }}">Renta</label>
                                    </div>
                                </div>
                            </td>
                            <td style="min-width: 120px;">
                                <div class="form-check form-switch">
                                    <input form="{{ $formId }}" class="form-check-input" type="checkbox" name="is_active" value="1" id="category-active-{{ $category->id }}" @checked($category->is_active)>
                                    <label class="form-check-label" for="category-active-{{ $category->id }}">{{ $category->is_active ? 'Activa' : 'Inactiva' }}</label>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <button form="{{ $formId }}" type="submit" class="btn btn-sm btn-success" title="Guardar categoría">
                                        <i data-lucide="save"></i>
                                    </button>
                                    <form method="POST" action="{{ route('finance.categories.destroy', $category) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar o desactivar categoría">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
