@php
    $money = fn ($value) => '$' . number_format((float) $value, 2);
    $creditFormId = 'credit-form-' . $credit->id;
    $monthlyAmount = (float) ($credit->installments->first()?->amount ?? 0);
    $schedule = $creditSchedules[$credit->id];
    $freeApplied = $schedule['free_applied'];
    $effectiveDue = $schedule['effective'];
    $maxFreePayment = (float) $schedule['max_free_payment'];
    $creditFreePaid = (float) $credit->freePayments->where('payment_type', '!=', 'refund')->sum('amount_applied');
    $refundApplied = $credit->freePayments->where('payment_type', 'refund')
        ->groupBy('target_installment_id')
        ->map(fn ($payments) => round((float) $payments->sum('amount_applied'), 2));
@endphp
<div class="card-body border-bottom d-flex flex-wrap gap-2">
                @if ($credit->installments->contains(fn ($installment) => $installment->plannedPayment !== null))
                    <form method="POST" action="{{ route('finance.credits.sync-planned-series', $credit) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-info">Unificar pagos futuros</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('finance.credits.destroy', $credit) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar crédito completo">Eliminar crédito</button>
                </form>
</div>
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('finance.credits.payment-plan', $credit) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-auto">
                    <label class="form-label small mb-1" for="planned-payment-day-{{ $credit->id }}">Día previsto de pago cada mes</label>
                    <input id="planned-payment-day-{{ $credit->id }}" type="number" name="planned_payment_day" class="form-control form-control-sm" min="1" max="31" value="{{ $credit->planned_payment_day }}" placeholder="Vencimiento" style="width: 130px">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Guardar plan</button>
                </div>
                <div class="col-12 small text-muted">Se aplica a todas las mensualidades pendientes de este crédito. Deja vacío para quitar la regla; las fechas antiguas ya vinculadas se conservan. Paga siempre desde Créditos.</div>
            </form>
            @if ($credit->is_manual_schedule)
            <form id="{{ $creditFormId }}" method="POST" action="{{ route('finance.credits.update', $credit) }}" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-2">
                    <label class="form-label">Fecha del crédito</label>
                    <input type="date" name="purchase_date" class="form-control form-control-sm" value="{{ $credit->purchase_date->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $credit->name }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cuenta / acreedor</label>
                    <select name="account_id" class="form-select form-select-sm" required>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected($credit->account_id === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($credit->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="{{ $credit->notes }}">
                </div>
                <div class="col-md-1 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-success" title="Guardar datos generales">Guardar</button>
                </div>
                <div class="col-12">
                    <div class="alert alert-info py-2 px-3 mb-0 small">
                        Las fechas y montos se editan en cada mensualidad. Guardar aquí no regenera el calendario ni aplica el ciclo de la cuenta.
                    </div>
                </div>
            </form>
            @else
            <form id="{{ $creditFormId }}" method="POST" action="{{ route('finance.credits.update', $credit) }}" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-2">
                    <label class="form-label">Compra</label>
                    <input type="date" name="purchase_date" class="form-control form-control-sm" value="{{ $credit->purchase_date->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $credit->name }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capturar por</label>
                    <select name="amount_mode" class="form-select form-select-sm">
                        <option value="total">Total</option>
                        <option value="monthly">Pago mensual</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total crédito</label>
                    <input type="number" name="total_amount" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ $credit->total_amount }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pago mensual</label>
                    <input type="number" name="monthly_amount" class="form-control form-control-sm" step="0.01" min="0.01" value="{{ $monthlyAmount }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Meses</label>
                    <input type="number" name="months" class="form-control form-control-sm" min="1" max="60" value="{{ $credit->months }}" required>
                </div>
                <div class="col-md-2 js-cycle-field">
                    <label class="form-label">Primer mes</label>
                    <input type="month" name="first_due_month" class="form-control form-control-sm" value="{{ $credit->first_due_month->format('Y-m') }}">
                </div>
                <div class="col-md-1 js-cycle-field">
                    <label class="form-label">Vence día</label>
                    <input type="number" name="due_day" class="form-control form-control-sm" min="1" max="31" value="{{ $credit->due_day }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cuenta</label>
                    <select name="account_id" class="form-select form-select-sm" data-credit-account>
                        <option value="">-</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                @if ($account->hasCreditCycle()) data-has-cycle="1" data-statement-day="{{ (int) $account->statement_day }}" data-payment-day="{{ (int) $account->payment_day }}" @endif
                                @selected($credit->account_id === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 js-cycle-note d-none">
                    <div class="alert alert-info py-2 px-3 mb-0 small">
                        <i data-lucide="info" class="me-1"></i><span class="js-cycle-note-text"></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($credit->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="{{ $credit->notes }}">
                </div>
                <div class="col-md-1 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-success" title="Guardar crédito completo">Guardar</button>
                </div>
            </form>
            @endif
        </div>
        <div class="card-body border-bottom" id="free-payments-{{ $credit->id }}">
            <div class="row g-3">
                <div class="col-lg-5">
                    <h5 class="mb-3">Registrar abono libre</h5>
                    <form method="POST" action="{{ route('finance.credits.free-payments.store', $credit) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="paid_on" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto</label>
                            <input type="number" name="amount" class="form-control form-control-sm text-end" step="0.01" min="0.01" max="{{ max(0.01, $maxFreePayment) }}" placeholder="{{ number_format($maxFreePayment, 2, '.', '') }}" @disabled($maxFreePayment <= 0) required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cuenta</label>
                            <select name="account_id" class="form-select form-select-sm">
                                <option value="">Cuenta del crédito</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected($credit->account_id === $account->id)>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Categoría del crédito</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($credit->category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notas</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Pago suelto, anticipo, transferencia...">
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center gap-3">
                            <small class="text-muted">
                                No marca mensualidades como pagadas ni cambia el calendario original: se descuenta de la
                                siguiente mensualidad, así que también baja lo que debes pagar este mes y el siguiente.
                                @if ($maxFreePayment > 0)
                                    <span class="d-block">Máximo {{ $money($maxFreePayment) }} (lo que falta de la siguiente mensualidad; no se pueden adelantar las posteriores).</span>
                                @else
                                    <span class="d-block text-warning">Este crédito ya no tiene mensualidades por pagar.</span>
                                @endif
                            </small>
                            <button type="submit" class="btn btn-sm btn-primary" @disabled($maxFreePayment <= 0)>
                                <i data-lucide="plus" class="me-1"></i>Registrar abono libre
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Abonos y devoluciones aplicados</h5>
                        <span class="badge badge-soft-info">{{ $money($creditFreePaid) }}</span>
                    </div>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Movimiento</th>
                                    <th class="text-end">Monto</th>
                                    <th>Notas</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($credit->freePayments->sortByDesc('paid_on') as $payment)
                                    <tr>
                                        <td>{{ $payment->paid_on->format('Y-m-d') }}</td>
                                        <td>
                                            @if ($payment->payment_type === 'refund')
                                                <a href="#card-refunds">Devolución de tarjeta</a>
                                                <div class="text-muted small">Aplicación al saldo · sin salida de dinero</div>
                                            @elseif ($payment->movement)
                                                {{ $payment->movement->description }}
                                                <div class="text-muted small">{{ $payment->movement->account?->name ?? $credit->account?->name ?? 'Sin cuenta' }}</div>
                                            @else
                                                <span class="badge badge-soft-warning">Sin movimiento ligado</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-danger">{{ $money($payment->amount_applied) }}</td>
                                        <td>{{ $payment->notes ?? '-' }}</td>
                                        <td class="text-end">
                                            @if ($payment->payment_type !== 'refund')
                                            <form method="POST" action="{{ route('finance.credits.free-payments.destroy', $payment) }}" onsubmit="return confirm('¿Eliminar este abono libre? Podrás deshacerlo durante 2 minutos.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar abono">Eliminar</button>
                                            </form>
                                            @else
                                                <a href="#card-refunds" class="small">Ver devolución</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Sin abonos libres</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-md-none finance-mobile-list">
                        @forelse ($credit->freePayments->sortByDesc('paid_on') as $payment)
                            <div class="finance-mobile-row d-flex justify-content-between align-items-start gap-2 py-2 border-bottom">
                                <div style="min-width: 0;">
                                    <div class="fw-semibold">
                                        {{ $payment->paid_on->format('Y-m-d') }}
                                        <span class="text-danger ms-1">{{ $money($payment->amount_applied) }}</span>
                                    </div>
                                    <div class="text-muted small">
                                        @if ($payment->payment_type === 'refund')
                                            <a href="#card-refunds">Devolución de tarjeta · sin salida de dinero</a>
                                        @elseif ($payment->movement)
                                            {{ $payment->movement->description }} · {{ $payment->movement->account?->name ?? $credit->account?->name ?? 'Sin cuenta' }}
                                        @else
                                            <span class="badge badge-soft-warning">Sin movimiento ligado</span>
                                        @endif
                                        @if ($payment->notes) · {{ $payment->notes }} @endif
                                    </div>
                                </div>
                                @if ($payment->payment_type !== 'refund')
                                <form method="POST" action="{{ route('finance.credits.free-payments.destroy', $payment) }}" onsubmit="return confirm('¿Eliminar este abono libre? Podrás deshacerlo durante 2 minutos.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar abono">Eliminar</button>
                                </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-center text-muted py-3 mb-0">Sin abonos libres</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Mes</th>
                            <th>Vence</th>
                            <th>En flujo</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Pendiente</th>
                            <th>Estado</th>
                            <th>Pagado</th>
                            <th>Notas</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($credit->installments as $installment)
                            @php
                                $installmentFormId = 'installment-form-' . $installment->id;
                                $rowApplied = (float) ($freeApplied[$installment->id] ?? 0);
                                $rowRefundApplied = min($rowApplied, (float) $refundApplied->get($installment->id, 0));
                                $rowFreeApplied = round(max(0, $rowApplied - $rowRefundApplied), 2);
                                $rowEffective = (float) ($effectiveDue[$installment->id] ?? 0);
                            @endphp
                            <tr>
                                <td>{{ $installment->installment_number }}</td>
                                <td style="min-width: 130px;">
                                    <form id="{{ $installmentFormId }}" method="POST" action="{{ route('finance.credits.installments.update', $installment) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <input form="{{ $installmentFormId }}" type="month" name="period_month" class="form-control form-control-sm" value="{{ $installment->period_month->format('Y-m') }}" required>
                                </td>
                                <td style="min-width: 150px;">
                                    <input form="{{ $installmentFormId }}" type="date" name="due_date" class="form-control form-control-sm" value="{{ $installment->due_date?->format('Y-m-d') }}">
                                </td>
                                <td>{{ $installment->effectiveDueDate()?->format('Y-m-d') ?? '-' }}</td>
                                <td style="min-width: 130px;">
                                    <input form="{{ $installmentFormId }}" type="number" name="amount" class="form-control form-control-sm text-end" step="0.01" min="0.01" value="{{ $installment->amount }}" required>
                                </td>
                                <td class="text-end" style="min-width: 120px;">
                                    <span class="fw-semibold">{{ $money($rowEffective) }}</span>
                                    @if ($rowFreeApplied > 0)
                                        <small class="d-block text-info">-{{ $money($rowFreeApplied) }} abono libre</small>
                                    @endif
                                    @if ($rowRefundApplied > 0)
                                        <small class="d-block text-info">-{{ $money($rowRefundApplied) }} devolución de tarjeta</small>
                                    @endif
                                </td>
                                <td style="min-width: 130px;">
                                    <select form="{{ $installmentFormId }}" name="status" class="form-select form-select-sm">
                                        <option value="pending" @selected($installment->status !== 'paid')>Pendiente</option>
                                        <option value="paid" @selected($installment->status === 'paid')>Pagado</option>
                                    </select>
                                </td>
                                <td style="min-width: 150px;">
                                    <input form="{{ $installmentFormId }}" type="date" name="paid_on" class="form-control form-control-sm" value="{{ $installment->paid_on?->format('Y-m-d') }}">
                                </td>
                                <td style="min-width: 220px;">
                                    <input form="{{ $installmentFormId }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $installment->notes }}">
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button form="{{ $installmentFormId }}" type="submit" class="btn btn-sm btn-success" title="Guardar mensualidad">Guardar</button>
                                        @if ($installment->status !== 'paid')
                                            <form method="POST" action="{{ route('finance.credits.installments.paid', $installment) }}">
                                                @csrf
                                                {{-- El egreso se fecha el dia que el usuario declara haber pagado
                                                     (columna "Pagado" de la fila). Si la deja vacia, hoy. --}}
                                                <input type="hidden" name="paid_on" data-paid-on-from="{{ $installmentFormId }}" value="{{ now()->toDateString() }}">
                                                <select name="payment_account_id" class="form-select form-select-sm" aria-label="Cuenta de salida de mensualidad {{ $installment->installment_number }}">
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected(($paymentAccountDefaults[$credit->id] ?? null) === $account->id)>{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary" title="Pagado y crear movimiento (usa la fecha de la columna Pagado)">Registrar pago</button>
                                            </form>
                                        @endif
                                        @if ($installment->plannedPayment)
                                            <form method="POST" action="{{ route('finance.planned.unlink-installment', $installment->plannedPayment) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Desvincular pago planeado {{ $installment->plannedPayment->name }}">Desvincular</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('finance.credits.installments.destroy', $installment) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar mensualidad">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vista móvil: una tarjeta por mensualidad (form propio por tarjeta). --}}
            <div class="d-md-none finance-mobile-list">
                @foreach ($credit->installments as $installment)
                    @php
                        $installmentFormId = 'installment-form-m-' . $installment->id;
                        $rowApplied = (float) ($freeApplied[$installment->id] ?? 0);
                        $rowRefundApplied = min($rowApplied, (float) $refundApplied->get($installment->id, 0));
                        $rowFreeApplied = round(max(0, $rowApplied - $rowRefundApplied), 2);
                        $rowEffective = (float) ($effectiveDue[$installment->id] ?? 0);
                    @endphp
                    <div class="finance-mobile-row px-3 py-3 border-bottom">
                        <form id="{{ $installmentFormId }}" method="POST" action="{{ route('finance.credits.installments.update', $installment) }}">
                            @csrf
                            @method('PUT')
                        </form>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Mensualidad {{ $installment->installment_number }}</span>
                            <span class="badge {{ $installment->status === 'paid' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $installment->status === 'paid' ? 'Pagado' : 'Pendiente' }}
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            Pendiente efectivo <span class="fw-semibold text-warning">{{ $money($rowEffective) }}</span>
                            @if ($rowFreeApplied > 0)
                                <span class="text-info">(abono libre {{ $money($rowFreeApplied) }})</span>
                            @endif
                            @if ($rowRefundApplied > 0)
                                <span class="text-info">(devolución de tarjeta {{ $money($rowRefundApplied) }})</span>
                            @endif
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">Mes</label>
                                <input form="{{ $installmentFormId }}" type="month" name="period_month" class="form-control form-control-sm" value="{{ $installment->period_month->format('Y-m') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Vence</label>
                                <input form="{{ $installmentFormId }}" type="date" name="due_date" class="form-control form-control-sm" value="{{ $installment->due_date?->format('Y-m-d') }}">
                            </div>
                            <div class="col-12 small text-info">En flujo: {{ $installment->effectiveDueDate()?->format('Y-m-d') ?? '-' }}</div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Monto</label>
                                <input form="{{ $installmentFormId }}" type="number" name="amount" class="form-control form-control-sm text-end" step="0.01" min="0.01" value="{{ $installment->amount }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Estado</label>
                                <select form="{{ $installmentFormId }}" name="status" class="form-select form-select-sm">
                                    <option value="pending" @selected($installment->status !== 'paid')>Pendiente</option>
                                    <option value="paid" @selected($installment->status === 'paid')>Pagado</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Pagado el</label>
                                <input form="{{ $installmentFormId }}" type="date" name="paid_on" class="form-control form-control-sm" value="{{ $installment->paid_on?->format('Y-m-d') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Notas</label>
                                <input form="{{ $installmentFormId }}" type="text" name="notes" class="form-control form-control-sm" value="{{ $installment->notes }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button form="{{ $installmentFormId }}" type="submit" class="btn btn-sm btn-success flex-grow-1">
                                <i data-lucide="save" class="me-1"></i>Guardar
                            </button>
                            @if ($installment->status !== 'paid')
                                <form method="POST" action="{{ route('finance.credits.installments.paid', $installment) }}">
                                    @csrf
                                    {{-- Misma regla que la tabla: manda la fecha de "Pagado el". --}}
                                    <input type="hidden" name="paid_on" data-paid-on-from="{{ $installmentFormId }}" value="{{ now()->toDateString() }}">
                                    <select name="payment_account_id" class="form-select form-select-sm mb-2" aria-label="Cuenta de salida de mensualidad {{ $installment->installment_number }}">
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(($paymentAccountDefaults[$credit->id] ?? null) === $account->id)>{{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary" title="Pagado y crear movimiento (usa la fecha de Pagado el)">Registrar pago</button>
                                </form>
                            @endif
                            @if ($installment->plannedPayment)
                                <form method="POST" action="{{ route('finance.planned.unlink-installment', $installment->plannedPayment) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Desvincular</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('finance.credits.installments.destroy', $installment) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar mensualidad">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
