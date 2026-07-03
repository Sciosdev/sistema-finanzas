@php
    $money = $money ?? fn ($value) => '$' . number_format((float) $value, 2);
    $pp = $periodPlan ?? ['meta' => [], 'segments' => [], 'credit_accounts' => [], 'messages' => []];
    $cs = $creditSchedule ?? [
        'pay_now' => ['total' => 0, 'items' => []],
        'after_income' => [],
        'deferred' => ['total' => 0, 'items' => []],
        'cushion_dip' => ['used' => false, 'total' => 0, 'reponible_by_date' => null, 'items' => []],
        'meta' => ['current_month_credit_due_total' => 0],
        'messages' => [],
    ];
    $we = $weeklyEnvelope ?? [
        'meta' => [], 'weeks' => [], 'current_week' => null,
        'category_weights' => [], 'pattern_advice' => [], 'messages' => [],
    ];
    $ppMeta = $pp['meta'] ?? [];
    $weMeta = $we['meta'] ?? [];
    $currentWeek = $we['current_week'] ?? null;
@endphp

<div class="card border-primary mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h4 class="card-title mb-0">Plan por periodos</h4>
            <p class="text-muted small mb-0">
                Del {{ $ppMeta['today'] ?? '' }} al {{ $ppMeta['planning_end'] ?? '' }}
                @if (!empty($ppMeta['next_month_first_income_date']))
                    · alcanza el próximo ingreso del {{ $ppMeta['next_month_first_income_date'] }}
                @endif
                · encadena tu efectivo por quincenas y cortes de ingreso.
            </p>
        </div>
        <span class="badge badge-soft-primary align-self-start">Saldo inicial: {{ $money($ppMeta['starting_balance'] ?? 0) }}</span>
    </div>
    <div class="card-body">

        {{-- ============ 1. Pista por periodos (motor de periodos) ============ --}}
        <h5 class="mb-2">Pista de efectivo por tramos</h5>
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>Tramo</th>
                        <th class="text-end">Apertura</th>
                        <th class="text-end">Ingreso</th>
                        <th class="text-end">Flujos</th>
                        <th class="text-end">Tarjeta</th>
                        <th class="text-end">Crédito</th>
                        <th class="text-end">Cierre</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pp['segments'] ?? [] as $segment)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $segment['start_date'] }} → {{ $segment['end_date'] }}</div>
                                <div class="text-muted small">{{ $segment['quincena_label'] }}</div>
                            </td>
                            <td class="text-end">{{ $money($segment['opening_balance']) }}</td>
                            <td class="text-end {{ $segment['income_total'] > 0 ? 'text-success' : 'text-muted' }}">
                                {{ $segment['income_total'] > 0 ? '+' . $money($segment['income_total']) : '—' }}
                            </td>
                            <td class="text-end {{ $segment['cash_flows_total'] > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ $segment['cash_flows_total'] > 0 ? '-' . $money($segment['cash_flows_total']) : '—' }}
                            </td>
                            <td class="text-end {{ $segment['card_charges_total'] > 0 ? 'text-warning' : 'text-muted' }}">
                                {{ $segment['card_charges_total'] > 0 ? '-' . $money($segment['card_charges_total']) : '—' }}
                            </td>
                            <td class="text-end {{ $segment['credit_due_total'] > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ $segment['credit_due_total'] > 0 ? '-' . $money($segment['credit_due_total']) : '—' }}
                            </td>
                            <td class="text-end fw-semibold {{ ($segment['cushion_ok'] ?? true) ? 'text-success' : 'text-danger' }}">
                                {{ $money($segment['closing_balance']) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted small">No hay tramos para planear.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (!empty($pp['messages']))
            <ul class="small text-muted ps-3 mb-3">
                @foreach ($pp['messages'] as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif

        {{-- ============ 2. Cronograma de crédito (multi-periodo + spillover) ============ --}}
        <h5 class="mb-2">Cronograma de crédito</h5>
        <div class="row g-2 mb-3">
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Deuda del mes</p>
                    <h5 class="mb-0">{{ $money($cs['meta']['current_month_credit_due_total'] ?? 0) }}</h5>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Pagar hoy</p>
                    <h5 class="mb-0 text-primary">{{ $money($cs['pay_now']['total'] ?? 0) }}</h5>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Rayar colchón</p>
                    <h5 class="mb-0 {{ ($cs['cushion_dip']['used'] ?? false) ? 'text-warning' : '' }}">{{ $money($cs['cushion_dip']['total'] ?? 0) }}</h5>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Diferir al mes siguiente</p>
                    <h5 class="mb-0 {{ ($cs['deferred']['total'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $money($cs['deferred']['total'] ?? 0) }}</h5>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <h6 class="mb-2">Pagar hoy</h6>
                @forelse ($cs['pay_now']['items'] ?? [] as $item)
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $item['account_name'] }}</div>
                            @if ($item['is_cushion_dip'] ?? false)
                                <span class="badge badge-soft-warning">Raya el colchón</span>
                            @endif
                            @if ($item['is_partial'] ?? false)
                                <span class="badge badge-soft-secondary">Parcial</span>
                            @endif
                            <div class="text-muted small">{{ $item['reason'] ?? '' }}</div>
                        </div>
                        <div class="fw-semibold text-end">{{ $money($item['amount']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Hoy no conviene pagar crédito todavía.</p>
                @endforelse
            </div>
            <div class="col-lg-4">
                <h6 class="mb-2">Después de cada ingreso</h6>
                @forelse ($cs['after_income'] ?? [] as $group)
                    <div class="mb-2">
                        <div class="fw-semibold">
                            {{ $group['checkpoint_date'] }}
                            @if (($group['income_total'] ?? 0) > 0)
                                <span class="text-success small">(+{{ $money($group['income_total']) }})</span>
                            @endif
                        </div>
                        @foreach ($group['items'] as $item)
                            <div class="d-flex justify-content-between gap-2">
                                <span class="text-muted small">{{ $item['account_name'] }} @if ($item['is_partial'] ?? false)<span class="badge badge-soft-secondary">Parcial</span>@endif</span>
                                <span class="small">{{ $money($item['amount']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted small mb-0">Sin pagos que esperar a un ingreso.</p>
                @endforelse
            </div>
            <div class="col-lg-4">
                <h6 class="mb-2">Diferir al mes siguiente</h6>
                @forelse ($cs['deferred']['items'] ?? [] as $item)
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $item['account_name'] }}</div>
                            <div class="text-muted small">{{ $item['reason'] ?? '' }}</div>
                        </div>
                        <div class="fw-semibold text-end">{{ $money($item['amount']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Este mes alcanza para toda la deuda del periodo.</p>
                @endforelse
            </div>
        </div>

        @if (($cs['cushion_dip']['used'] ?? false) && ($cs['cushion_dip']['reponible_by_date'] ?? null))
            <div class="alert alert-warning py-2 small mb-3">
                Puedes rayar el colchón por {{ $money($cs['cushion_dip']['total']) }}; se repone con el ingreso del {{ $cs['cushion_dip']['reponible_by_date'] }}.
            </div>
        @endif

        {{-- ============ 3. Sobres semanales por categoría ============ --}}
        <h5 class="mb-2">Sobres semanales para vivir</h5>
        <div class="row g-2 mb-3">
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Dinero para vivir este mes</p>
                    <h5 class="mb-0 {{ ($weMeta['living_pool_month'] ?? 0) > 0 ? 'text-success' : 'text-danger' }}">{{ $money($weMeta['living_pool_month'] ?? 0) }}</h5>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Tope de esta semana</p>
                    <h5 class="mb-0">{{ $money($currentWeek['week_cap'] ?? 0) }}</h5>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Te queda esta semana</p>
                    <h5 class="mb-0">{{ $money($currentWeek['remaining_total'] ?? 0) }}</h5>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <p class="text-muted small mb-1">Gasto diario sugerido</p>
                    <h5 class="mb-0">{{ $money($weMeta['daily_cap'] ?? 0) }}</h5>
                </div>
            </div>
        </div>

        @if ($currentWeek)
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <h6 class="mb-0">Semana en curso: {{ $currentWeek['start_date'] }} a {{ $currentWeek['end_date'] }}</h6>
                <span class="badge badge-soft-secondary">{{ $currentWeek['quincena_label'] }}</span>
                @if ($currentWeek['tradeoff_active'] ?? false)
                    <span class="badge badge-soft-danger">Tope semanal agotado</span>
                @endif
            </div>
            <div class="table-responsive mb-2">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th>Categoría</th>
                            <th class="text-end">Sobre</th>
                            <th class="text-end">Gastado</th>
                            <th class="text-end">Disponible</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($currentWeek['categories'] ?? [] as $cat)
                            <tr>
                                <td>{{ $cat['category_name'] }} <span class="text-muted small">({{ $cat['weight_percent'] }}%)</span></td>
                                <td class="text-end">{{ $money($cat['envelope']) }}</td>
                                <td class="text-end {{ ($cat['over_envelope'] ?? false) ? 'text-danger' : '' }}">{{ $money($cat['spent'] ?? 0) }}</td>
                                <td class="text-end fw-semibold {{ ($cat['effective_remaining'] ?? 0) <= 0 ? 'text-danger' : 'text-success' }}">{{ $money($cat['effective_remaining'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($currentWeek['tradeoff_active'] ?? false)
                <p class="text-muted small mb-2">Ya usaste el tope de esta semana; aunque tengas sobre en otra categoría, mejor ya no gastes para no comerte la otra quincena.</p>
            @endif
        @endif

        @if (!empty($we['pattern_advice']))
            <div class="alert alert-light border py-2 small mb-2">
                @foreach ($we['pattern_advice'] as $advice)
                    <div>{{ $advice }}</div>
                @endforeach
            </div>
        @endif

        <div class="alert alert-light border py-2 small mb-0">
            Esto es solo una recomendación. No se creó ningún movimiento ni se cambió ningún estado.
        </div>
    </div>
</div>
