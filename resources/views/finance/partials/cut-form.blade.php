<form method="POST" action="{{ route('finance.cuts.store') }}" class="needs-validation" novalidate>
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Fecha corte</label>
            <input type="date" name="cut_date" class="form-control" value="{{ old('cut_date', now()->toDateString()) }}" required>
        </div>
        @foreach ($accounts as $account)
            <div class="col-md-4">
                <label class="form-label">{{ $account->name }}</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" name="balances[{{ $account->id }}]" class="form-control" step="0.01" min="0" value="{{ old('balances.' . $account->id, 0) }}">
                </div>
            </div>
        @endforeach
        <div class="col-12">
            <label class="form-label">Notas</label>
            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
        </div>
        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="check-circle" class="me-1"></i>Conciliar
            </button>
        </div>
    </div>
</form>
