@php($suggestedBalances = $suggestedBalances ?? [])
@php($previousBalances = $previousBalances ?? [])
@php($previousCutDate = $previousCutDate ?? null)
<form method="POST" action="{{ route('finance.cuts.store') }}" class="needs-validation" novalidate>
    @csrf
    @if (! empty($suggestedBalances))
        <div class="alert alert-info py-2 mb-3">
            <i data-lucide="info" class="me-1"></i>
            Saldos calculados desde tu último corte{{ $previousCutDate ? ' (' . $previousCutDate->format('d/m/Y') . ')' : '' }} + ingresos − egresos capturados. Debajo de cada cuenta ves lo que había en ese corte. Si coinciden, solo concilia; si no, ajusta el monto.
        </div>
    @endif
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
                    <input type="number" name="balances[{{ $account->id }}]" class="form-control" step="0.01" min="0" value="{{ old('balances.' . $account->id, $suggestedBalances[$account->id] ?? 0) }}" onfocus="this.select()" onmouseup="return false;">
                </div>
                @if (isset($previousBalances[$account->id]))
                    <small class="text-muted">Anterior: ${{ number_format($previousBalances[$account->id], 2) }}</small>
                @endif
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
