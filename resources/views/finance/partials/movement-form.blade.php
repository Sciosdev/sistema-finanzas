@php
    $formMovement = $formMovement ?? null;
    $formAction = $formAction ?? route('finance.movements.store');
    $formMethod = $formMethod ?? null;
    $submitLabel = $submitLabel ?? 'Guardar';
    $submitIcon = $submitIcon ?? 'plus';
    $dateValue = old('happened_on', $formMovement?->happened_on?->format('Y-m-d') ?? now()->toDateString());
    $typeValue = old('movement_type', $formMovement?->movement_type ?? 'expense');
    $amountValue = old('amount', $formMovement?->amount);
    $accountValue = old('account_id', $formMovement?->account_id);
    $descriptionValue = old('description', $formMovement?->description);
    $categoryValue = old('category_id', $formMovement?->category_id);
    $personValue = old('person_id', $formMovement?->person_id);
    $notesValue = old('notes', $formMovement?->notes);
    $returnTo = $returnTo ?? null;
@endphp

<form method="POST" action="{{ $formAction }}" class="needs-validation" novalidate>
    @csrf
    @if ($formMethod)
        @method($formMethod)
    @endif
    @if (! empty($returnTo))
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
    @endif
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="happened_on" class="form-control" value="{{ $dateValue }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="movement_type" class="form-select" required>
                <option value="expense" @selected($typeValue === 'expense')>Egreso</option>
                <option value="income" @selected($typeValue === 'income')>Ingreso</option>
                <option value="yield" @selected($typeValue === 'yield')>Rendimiento</option>
                <option value="transfer" @selected($typeValue === 'transfer')>Transferencia</option>
                <option value="adjustment" @selected($typeValue === 'adjustment')>Ajuste</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Monto</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ $amountValue }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Cuenta</label>
            <select name="account_id" class="form-select">
                <option value="">Sin cuenta</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) $accountValue === (string) $account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Descripción</label>
            <input type="text" name="description" class="form-control" maxlength="255" value="{{ $descriptionValue }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoría</label>
            <select name="category_id" class="form-select">
                <option value="">Sin categoría</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $categoryValue === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Persona</label>
            <select name="person_id" class="form-select">
                <option value="">Sin persona</option>
                @foreach ($people as $person)
                    <option value="{{ $person->id }}" @selected((string) $personValue === (string) $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Notas</label>
            <input type="text" name="notes" class="form-control" value="{{ $notesValue }}">
        </div>
        <div class="col-md-4">
            <label class="form-label d-block">Marcas</label>
            <div class="d-flex flex-wrap gap-3 pt-1">
                <div class="form-check">
                    <input type="hidden" name="is_san_juan" value="0">
                    <input class="form-check-input" type="checkbox" name="is_san_juan" value="1" id="mark_san_juan" @checked((bool) old('is_san_juan', $formMovement?->is_san_juan ?? false))>
                    <label class="form-check-label" for="mark_san_juan">San Juan</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_rent" value="0">
                    <input class="form-check-input" type="checkbox" name="is_rent" value="1" id="mark_rent" @checked((bool) old('is_rent', $formMovement?->is_rent ?? false))>
                    <label class="form-check-label" for="mark_rent">Renta</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_unknown" value="0">
                    <input class="form-check-input" type="checkbox" name="is_unknown" value="1" id="mark_unknown" @checked((bool) old('is_unknown', $formMovement?->is_unknown ?? false))>
                    <label class="form-check-label" for="mark_unknown">?</label>
                </div>
            </div>
        </div>
        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="{{ $submitIcon }}" class="me-1"></i>{{ $submitLabel }}
            </button>
        </div>
    </div>
</form>
