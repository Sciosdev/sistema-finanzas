@php
    $correctionOld = fn ($key, $default = '') => old('payment_correction_form') ? old($key, $default) : $default;
    $correctionCandidates = $credits->flatMap(fn ($credit) => $credit->installments->map(fn ($installment) => [
        'credit' => $credit, 'installment' => $installment,
    ]))->filter(fn ($row) => $row['installment']->status === 'paid'
        && $row['installment']->movement?->source === 'credit_installment'
        && $row['installment']->paid_on);
@endphp
<div class="card mt-4" id="payment-corrections">
    <div class="card-header">
        <h4 class="card-title mb-1">Corregir la distribución de un pago</h4>
        <p class="text-muted mb-2">Separa un cargo que quedó incluido al marcar otras compras como pagadas. Conserva el total de dinero que salió y guarda el detalle de la corrección.</p>
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#payment-correction-form">Preparar corrección</button>
    </div>
    <div id="payment-correction-form" class="collapse {{ old('payment_correction_form') ? 'show' : '' }}">
        <form method="POST" action="{{ route('finance.credits.payment-corrections.store') }}" class="card-body" data-payment-correction-form>
            @csrf
            <input type="hidden" name="payment_correction_form" value="1">
            <input type="hidden" name="idempotency_key" value="{{ $correctionOld('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="correction-account" class="form-label">Tarjeta de las compras</label>
                    <select id="correction-account" name="correction_account_id" class="form-select" data-correction-account required>
                        <option value="">Selecciona</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) $correctionOld('correction_account_id') === (string) $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="correction-paid-on" class="form-label">Fecha del pago registrado</label>
                    <input id="correction-paid-on" type="date" name="paid_on" value="{{ $correctionOld('paid_on') }}" class="form-control" data-correction-date required>
                </div>
            </div>
            <div class="table-responsive mb-3" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead><tr><th>Incluir</th><th>Compra</th><th>Mensualidad</th><th>Pagado</th></tr></thead>
                    <tbody>
                    @foreach ($correctionCandidates as $row)
                        @php($candidate = $row['installment'])
                        <tr data-correction-row data-account="{{ $row['credit']->account_id }}" data-paid-on="{{ $candidate->paid_on->format('Y-m-d') }}" hidden>
                            <td><input class="form-check-input" type="checkbox" name="installment_ids[]" value="{{ $candidate->id }}" @checked(in_array($candidate->id, $correctionOld('installment_ids', []))) data-paid-amount="{{ $candidate->paid_amount }}" aria-label="Seleccionar {{ $row['credit']->name }} {{ $candidate->installment_number }} por {{ $money($candidate->paid_amount) }}"></td>
                            <td>{{ $row['credit']->name }}</td>
                            <td>{{ $candidate->period_month?->format('Y-m') }} · {{ $candidate->installment_number }}/{{ $row['credit']->months }}</td>
                            <td>{{ $money($candidate->paid_amount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="correction-concept" class="form-label">Cargo que debió recibir parte del pago</label>
                    <input id="correction-concept" name="concept" value="{{ $correctionOld('concept') }}" class="form-control" maxlength="255" placeholder="Concepto exacto del estado de cuenta" required>
                </div>
                <div class="col-md-3">
                    <label for="correction-amount" class="form-label">Importe de ese cargo</label>
                    <input id="correction-amount" name="amount" value="{{ $correctionOld('amount') }}" type="number" step="0.01" min="0.01" class="form-control" data-correction-amount required>
                </div>
                <div class="col-md-3">
                    <label for="correction-category" class="form-label">Categoría del cargo</label>
                    <select id="correction-category" name="charge_category_id" class="form-select">
                        <option value="">Crédito / tarjeta (si existe)</option>
                        @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) $correctionOld('charge_category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="correction-charge-month" class="form-label">Periodo del cargo separado</label>
                    <input id="correction-charge-month" name="charge_period_month" value="{{ $correctionOld('charge_period_month') }}" type="month" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="correction-charge-due" class="form-label">Vencimiento de ese cargo</label>
                    <input id="correction-charge-due" name="charge_due_date" value="{{ $correctionOld('charge_due_date') }}" type="date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="correction-target-month" class="form-label">Periodo correcto de las compras</label>
                    <input id="correction-target-month" name="target_period_month" value="{{ $correctionOld('target_period_month') }}" type="month" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="correction-target-due" class="form-label">Vencimiento de las compras</label>
                    <input id="correction-target-due" name="target_due_date" value="{{ $correctionOld('target_due_date') }}" type="date" class="form-control" required>
                </div>
                <div class="col-12">
                    <label for="correction-reason" class="form-label">Motivo y evidencia de la corrección</label>
                    <textarea id="correction-reason" name="reason" rows="3" class="form-control" required>{{ $correctionOld('reason') }}</textarea>
                </div>
            </div>
            <input type="hidden" name="expected_paid_total" value="0" data-correction-total>
            <div class="alert alert-info mt-3" data-correction-preview>Selecciona la tarjeta, fecha y compras para ver la distribución.</div>
            <p class="small text-muted">El dinero restante se aplica a las compras seleccionadas en orden de compra. Es una distribución para tu control; no afirma cómo el banco repartió el abono. Los precios de las compras se conservan.</p>
            <button type="submit" class="btn btn-primary" data-correction-submit disabled>Guardar corrección documentada</button>
        </form>
    </div>
    @if (($paymentCorrections ?? collect())->isNotEmpty())
        <div class="card-body border-top">
            <h5>Correcciones registradas</h5>
            @foreach ($paymentCorrections as $correction)
                <div class="border-bottom py-2">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <strong>{{ $correction->created_at->format('Y-m-d H:i') }} · {{ $correction->concept }}</strong>
                        @if ($correction->reverted_at)
                            <span class="badge badge-soft-secondary">Revertida</span>
                        @else
                            <form method="POST" action="{{ route('finance.credits.payment-corrections.destroy', $correction) }}" onsubmit="return confirm('¿Revertir esta corrección y restaurar las compras y movimientos originales? Solo se realizará si esos registros no han cambiado después.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-warning">Revertir</button>
                            </form>
                        @endif
                    </div>
                    <p class="mb-1">Cargo separado: {{ $money($correction->amount) }} · Pago original: {{ $money($correction->paid_total) }}</p>
                    <p class="mb-0">{{ $correction->reason }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-payment-correction-form]');
    if (!form) return;
    const account = form.querySelector('[data-correction-account]');
    const date = form.querySelector('[data-correction-date]');
    const rows = Array.from(form.querySelectorAll('[data-correction-row]'));
    const amount = form.querySelector('[data-correction-amount]');
    const money = cents => new Intl.NumberFormat('es-MX', {style: 'currency', currency: 'MXN'}).format(cents / 100);
    function update() {
        let total = 0;
        rows.forEach(row => {
            const visible = row.dataset.account === account.value && row.dataset.paidOn === date.value;
            row.hidden = !visible;
            const check = row.querySelector('input');
            if (!visible) check.checked = false;
            if (check.checked) total += Math.round(Number(check.dataset.paidAmount) * 100);
        });
        const charge = Math.round(Number(amount.value || 0) * 100);
        form.querySelector('[data-correction-total]').value = (total / 100).toFixed(2);
        form.querySelector('[data-correction-preview]').textContent = `Salida de dinero registrada: ${money(total)}. Cargo separado: ${money(charge)}. Aplicado a las compras: ${money(total - charge)}. La salida total se conserva en ${money(total)}.`;
        form.querySelector('[data-correction-submit]').disabled = total <= 0 || charge <= 0 || charge >= total;
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
});
</script>
