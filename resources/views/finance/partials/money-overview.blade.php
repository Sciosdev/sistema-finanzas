@php
    $expectedBalances = $expectedBalances ?? [];
    $moneyAccounts = ($accounts ?? collect())->where('is_active', true);
    $moneyFmt = fn ($value) => '$' . number_format((float) $value, 2);

    $moneyIds = $moneyAccounts->pluck('id')->all();
    $moneyTotal = collect($expectedBalances)->only($moneyIds)->sum('expected');
    $cashIds = $moneyAccounts->filter(fn ($account) => $account->isCash())->pluck('id')->all();
    $cashTotal = collect($expectedBalances)->only($cashIds)->sum('expected');
    $hasAnyAmount = $moneyAccounts->contains(fn ($account) => abs((float) ($expectedBalances[$account->id]['expected'] ?? 0)) > 0.005);
@endphp

<div class="card border-success border-opacity-25" data-money-overview>
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
            <div>
                <div class="text-muted small text-uppercase fw-semibold">
                    <i data-lucide="wallet" class="me-1"></i>Dinero disponible (estimado)
                    <span class="badge bg-info-subtle text-info ms-1 d-none" data-money-preview>con este movimiento</span>
                </div>
                <div class="fs-3 fw-bold text-success" data-money-total data-base="{{ round((float) $moneyTotal, 2) }}">{{ $moneyFmt($moneyTotal) }}</div>
            </div>
            @if (! empty($cashIds))
                <div class="text-end">
                    <div class="text-muted small text-uppercase">Efectivo</div>
                    <div class="fs-5 fw-semibold" data-money-cash data-base="{{ round((float) $cashTotal, 2) }}">{{ $moneyFmt($cashTotal) }}</div>
                </div>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2" data-money-pills>
            @foreach ($moneyAccounts as $account)
                @php($expected = round((float) ($expectedBalances[$account->id]['expected'] ?? 0), 2))
                <span class="badge rounded-pill bg-body-tertiary text-body border d-inline-flex align-items-center {{ abs($expected) > 0.005 ? '' : 'd-none' }}"
                      data-money-pill="{{ $account->id }}" data-base="{{ $expected }}" data-cash="{{ $account->isCash() ? 1 : 0 }}">
                    <span class="rounded-circle d-inline-block me-1" style="width: 8px; height: 8px; background: {{ $account->color ?: '#4d5761' }}"></span>
                    {{ $account->name }}: <span class="ms-1" data-money-amount>{{ $moneyFmt($expected) }}</span>
                </span>
            @endforeach
            @unless ($hasAnyAmount)
                <span class="text-muted small" data-money-empty>Aún no hay saldo estimado. Captura movimientos o haz un corte para empezar.</span>
            @endunless
        </div>

        <div class="text-muted small mt-2">
            Calculado desde tu último corte + ingresos − egresos capturados.
            <a href="{{ route('finance.accounts.index') }}" class="text-decoration-none">Ver por cuenta</a>
        </div>
    </div>
</div>

<script>
    // Preview EN VIVO del dinero mientras capturas o editas un movimiento (sin
    // guardar). Misma regla de signo que el backend: ingreso/rendimiento +, egreso −;
    // sin cuenta = no afecta. Al EDITAR, la base ya incluye el movimiento original,
    // así que se muestra el cambio neto (quita el efecto original y aplica el nuevo).
    document.addEventListener('DOMContentLoaded', function () {
        const overview = document.querySelector('[data-money-overview]');
        const form = document.querySelector('[data-movement-form]');

        if (!overview || !form) {
            return;
        }

        const typeField = form.querySelector('[name="movement_type"]');
        const amountField = form.querySelector('[name="amount"]');
        const accountField = form.querySelector('[name="account_id"]');

        if (!typeField || !amountField || !accountField) {
            return;
        }

        const totalEl = overview.querySelector('[data-money-total]');
        const cashEl = overview.querySelector('[data-money-cash]');
        const previewEl = overview.querySelector('[data-money-preview]');
        const pills = Array.prototype.slice.call(overview.querySelectorAll('[data-money-pill]'));

        const signOf = (type) => (type === 'income' || type === 'yield') ? 1 : (type === 'expense' ? -1 : 0);
        const numberFormat = (value) => '$' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        const base = (el) => parseFloat(el && el.dataset.base) || 0;
        const isCashAccount = (id) => {
            const pill = pills.find((p) => p.dataset.moneyPill === String(id));
            return pill ? pill.dataset.cash === '1' : false;
        };

        // Efecto del movimiento original (ya contado en la base) — solo al editar.
        const origAccount = form.dataset.originalAccount || '';
        const origAmountRaw = parseFloat(form.dataset.originalAmount);
        const origSign = signOf(form.dataset.originalType || '');
        const origEffect = (origAccount !== '' && isFinite(origAmountRaw) && origSign !== 0)
            ? origSign * origAmountRaw
            : 0;

        const render = () => {
            const newSign = signOf(typeField.value);
            const newAmount = parseFloat(amountField.value);
            const newAccount = accountField.value;
            const newValid = newSign !== 0 && newAccount !== '' && isFinite(newAmount) && newAmount > 0;
            const newEffect = newValid ? newSign * newAmount : 0;

            const totalDelta = newEffect - origEffect;
            const changed = Math.abs(totalDelta) > 0.005
                || (origAccount !== String(newAccount) && (newEffect !== 0 || origEffect !== 0));

            const effectFor = (id) => {
                let effect = 0;
                if (String(id) === String(newAccount) && newValid) {
                    effect += newEffect;
                }
                if (String(id) === String(origAccount) && origEffect !== 0) {
                    effect -= origEffect;
                }
                return effect;
            };

            if (totalEl) {
                const value = base(totalEl) + totalDelta;
                totalEl.textContent = numberFormat(value);
                totalEl.classList.toggle('text-danger', value < 0);
                totalEl.classList.toggle('text-success', value >= 0);
            }

            if (cashEl) {
                let cashDelta = 0;
                if (newValid && isCashAccount(newAccount)) {
                    cashDelta += newEffect;
                }
                if (origEffect !== 0 && isCashAccount(origAccount)) {
                    cashDelta -= origEffect;
                }
                cashEl.textContent = numberFormat(base(cashEl) + cashDelta);
            }

            pills.forEach((pill) => {
                const value = base(pill) + effectFor(pill.dataset.moneyPill);
                const amountSpan = pill.querySelector('[data-money-amount]');
                if (amountSpan) {
                    amountSpan.textContent = numberFormat(value);
                    amountSpan.classList.toggle('text-danger', value < 0);
                }
                const affected = (pill.dataset.moneyPill === String(newAccount) && newValid)
                    || (pill.dataset.moneyPill === String(origAccount) && origEffect !== 0);
                pill.classList.toggle('d-none', Math.abs(value) <= 0.005);
                pill.classList.toggle('border-info', changed && affected);
            });

            if (previewEl) {
                previewEl.classList.toggle('d-none', !changed);
            }
        };

        [typeField, amountField, accountField].forEach((field) => {
            field.addEventListener('input', render);
            field.addEventListener('change', render);
        });
    });
</script>
