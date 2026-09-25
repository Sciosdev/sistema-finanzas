@php
    $refundRows = collect($cardRefunds ?? []);
    $refundMoney = fn ($amount) => '$'.number_format((float) $amount, 2);
    $refundOld = fn ($field, $default = '') => old('card_refund_form') ? old($field, $default) : $default;
@endphp

<section class="card" id="card-refunds" aria-labelledby="card-refunds-title">
    <div class="card-header">
        <h4 class="card-title mb-1" id="card-refunds-title">Devoluciones de tarjeta</h4>
        <p class="text-muted mb-0">Registra la devolución que aparece en tu banco. Reduce las mensualidades pendientes del periodo indicado sin crear un movimiento de efectivo.</p>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.credits.card-refunds.store') }}" class="row g-3">
            @csrf
            <input type="hidden" name="card_refund_form" value="1">
            <input type="hidden" name="idempotency_key" value="{{ $refundOld('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="col-md-4">
                <label for="card-refund-account" class="form-label">Tarjeta / acreedor</label>
                <select name="account_id" id="card-refund-account" class="form-select" required>
                    <option value="">Selecciona una cuenta</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $refundOld('account_id') === (string) $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="card-refund-date" class="form-label">Fecha de la devolución</label>
                <input type="date" name="received_on" id="card-refund-date" class="form-control" value="{{ $refundOld('received_on', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3">
                <label for="card-refund-period" class="form-label">Mes de pago al que se aplica</label>
                <input type="month" name="period_month" id="card-refund-period" class="form-control" value="{{ $refundOld('period_month', now()->format('Y-m')) }}" required>
            </div>
            <div class="col-md-2">
                <label for="card-refund-amount" class="form-label">Importe devuelto</label>
                <input type="number" name="amount" id="card-refund-amount" class="form-control text-end" step="0.01" min="0.01" max="999999999999.99" value="{{ $refundOld('amount') }}" required>
            </div>
            <div class="col-md-5">
                <label for="card-refund-description" class="form-label">Concepto que aparece en el banco</label>
                <input type="text" name="description" id="card-refund-description" class="form-control" maxlength="255" value="{{ $refundOld('description') }}" required>
            </div>
            <div class="col-md-7">
                <label for="card-refund-notes" class="form-label">Referencia o evidencia</label>
                <input type="text" name="notes" id="card-refund-notes" class="form-control" maxlength="5000" value="{{ $refundOld('notes') }}" placeholder="Folio, compra de origen o detalle de la devolución" required>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">Registrar devolución</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha / periodo</th>
                    <th>Tarjeta y concepto</th>
                    <th class="text-end">Devolución</th>
                    <th>Aplicada a mensualidades</th>
                    <th>Referencia</th>
                    <th><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refundRows as $refund)
                    <tr>
                        <td class="text-nowrap">
                            {{ $refund->received_on->format('Y-m-d') }}
                            <div class="text-muted small">Pago {{ $refund->period_month->format('Y-m') }}</div>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $refund->account?->name ?? 'Cuenta no disponible' }}</span>
                            <div>{{ $refund->description }}</div>
                            @if ($refund->originalPurchase)
                                <div class="text-muted small">Compra de origen: {{ $refund->originalPurchase->name }}</div>
                            @endif
                        </td>
                        <td class="text-end text-success text-nowrap">{{ $refundMoney($refund->amount) }}</td>
                        <td>
                            @foreach ($refund->allocations as $allocation)
                                @php($target = $allocation->targetInstallment)
                                <div class="small">
                                    {{ $target?->creditPurchase?->name ?? 'Mensualidad no disponible' }}
                                    @if ($target)
                                        · cuota {{ $target->installment_number }} · {{ $target->period_month?->format('Y-m') }}
                                    @endif
                                    <span class="text-success text-nowrap">{{ $refundMoney($allocation->amount_applied) }}</span>
                                </div>
                            @endforeach
                        </td>
                        <td class="small">{{ $refund->notes }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('finance.credits.card-refunds.destroy', $refund) }}" onsubmit="return confirm('¿Eliminar esta devolución? Su importe volverá a quedar pendiente en las mensualidades.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar devolución</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Sin devoluciones de tarjeta registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
