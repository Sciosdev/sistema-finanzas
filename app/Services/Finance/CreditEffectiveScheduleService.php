<?php

namespace App\Services\Finance;

use App\Models\Finance\CreditFreePayment;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendario EFECTIVO de un crédito.
 *
 * El calendario contratado (las mensualidades tal como se firmaron) nunca se
 * toca: `amount` y `paid_amount` siguen reflejando lo pactado y lo pagado en
 * dinero real. Los abonos libres viven aparte, en `finance_credit_free_payments`,
 * y este servicio los proyecta VIRTUALMENTE sobre las mensualidades para
 * responder la única pregunta que importa en pantalla: *¿cuánto me falta pagar
 * realmente de esta mensualidad?*
 *
 * Reglas del reparto (todas derivadas de cómo funciona una tarjeta de crédito):
 *
 * 1. Un abono NUNCA puede caer en un mes anterior al mes en que se registró.
 *    Cada abono queda anclado a su propio `paid_on`. Esto es lo que hace que el
 *    cálculo sea ESTABLE: el reparto de un abono de julio da el mismo resultado
 *    hoy que dentro de seis meses. Si el ancla fuera "hoy", el abono iría
 *    saltando de mensualidad conforme pasan los meses y regalaría mensualidades
 *    que nunca se pagaron.
 *
 * 2. Dentro de las mensualidades elegibles, el abono llena huecos en orden
 *    cronológico (due_date, luego period_month, luego installment_number).
 *
 * 3. El "hueco" de una mensualidad es `amount - paid_amount`, es decir lo que no
 *    se cubrió con dinero. Las mensualidades ya pagadas también consumen su
 *    hueco: si pagaste una mensualidad de $705.30 poniendo solo $405.30 porque
 *    $300 venían de un abono, ese hueco de $300 le pertenece al abono y no debe
 *    liberarse hacia la siguiente mensualidad.
 *
 * 4. Para mostrar, una mensualidad `paid` o `skipped` siempre pendea $0 (misma
 *    semántica que ya usaba el resto del sistema).
 *
 * Todo se calcula en centavos enteros para que no se acumulen errores de coma
 * flotante, y se redondea a dos decimales solo al salir.
 *
 * Este servicio es de SOLO LECTURA. Las escrituras siguen en
 * {@see CreditFreePaymentService}.
 */
class CreditEffectiveScheduleService
{
    /**
     * Memo por crédito dentro del request: credit_id => [installment_id => centavos abonados].
     *
     * @var array<int, array<int, int>>
     */
    private array $allocations = [];

    /**
     * Invalida el memo. Obligatorio después de escribir abonos o mensualidades
     * dentro del mismo request.
     */
    public function flush(?int $creditId = null): void
    {
        if ($creditId === null) {
            $this->allocations = [];

            return;
        }

        unset($this->allocations[$creditId]);
    }

    /**
     * Reparto virtual: cuánto abono libre le tocó a cada mensualidad.
     *
     * @return array<int, float> installment_id => importe abonado
     */
    public function allocationFor(CreditPurchase $credit): array
    {
        return array_map(
            fn (int $cents): float => $this->money($cents),
            $this->allocationCents($credit)
        );
    }

    /**
     * Pendiente efectivo de cada mensualidad (lo contratado menos lo pagado en
     * dinero menos los abonos libres que le tocaron).
     *
     * @return array<int, float> installment_id => pendiente efectivo
     */
    public function effectivePendingFor(CreditPurchase $credit): array
    {
        $allocation = $this->allocationCents($credit);
        $pending = [];

        foreach ($this->orderedInstallments($credit) as $installment) {
            $pending[$installment->id] = $this->settled($installment)
                ? 0.0
                : $this->money(max(0, $this->holeCents($installment) - ($allocation[$installment->id] ?? 0)));
        }

        return $pending;
    }

    /**
     * Pendiente efectivo de UNA mensualidad. Atajo para los puntos del sistema
     * que solo tienen la mensualidad a la mano.
     */
    public function effectivePending(CreditInstallment $installment): float
    {
        $credit = $installment->creditPurchase;

        if (! $credit) {
            // Mensualidad huérfana: sin crédito no hay abonos que repartir.
            return $this->settled($installment) ? 0.0 : $this->money($this->holeCents($installment));
        }

        return $this->effectivePendingFor($credit)[$installment->id]
            ?? ($this->settled($installment) ? 0.0 : $this->money($this->holeCents($installment)));
    }

    /**
     * Abono libre aplicado a UNA mensualidad (para mostrarlo en la tarjeta).
     */
    public function freePaymentApplied(CreditInstallment $installment): float
    {
        $credit = $installment->creditPurchase;

        if (! $credit) {
            return 0.0;
        }

        return $this->allocationFor($credit)[$installment->id] ?? 0.0;
    }

    /**
     * Total efectivo que se debe de un crédito en un mes dado.
     */
    public function effectiveTotalForMonth(CreditPurchase $credit, Carbon $month): float
    {
        $pending = $this->effectivePendingFor($credit);
        $total = 0;

        foreach ($this->orderedInstallments($credit) as $installment) {
            if (! $installment->period_month?->isSameMonth($month)) {
                continue;
            }

            $total += $this->cents($pending[$installment->id] ?? 0);
        }

        return $this->money($total);
    }

    /**
     * La siguiente mensualidad realmente pagable: la primera que sigue debiendo
     * algo, de `$from` (por defecto el mes en curso) en adelante.
     *
     * Los meses ya pasados quedan fuera a propósito. En una tarjeta no se puede
     * "dejar un mes sin pagar": si aparece un pendiente viejo es un residuo del
     * arranque del sistema, y no tiene por qué comerse los abonos nuevos.
     */
    public function nextPayableInstallment(CreditPurchase $credit, ?Carbon $from = null): ?CreditInstallment
    {
        $floor = ($from ?? today())->copy()->startOfMonth();
        $pending = $this->effectivePendingFor($credit);

        foreach ($this->orderedInstallments($credit) as $installment) {
            if ($this->settled($installment)) {
                continue;
            }

            $period = $this->periodMonthOf($installment);

            if ($period && $period->lt($floor)) {
                continue;
            }

            if (($pending[$installment->id] ?? 0) > 0) {
                return $installment;
            }
        }

        return null;
    }

    /**
     * Cuánto se puede abonar como máximo ahora mismo.
     *
     * Es el pendiente efectivo de la SIGUIENTE mensualidad, no el saldo completo
     * del crédito: en una tarjeta no puedes adelantar octubre si no has pagado
     * agosto. Se acota además al saldo real para que un calendario inconsistente
     * nunca permita abonar de más.
     */
    public function maxFreePayment(CreditPurchase $credit, ?Carbon $from = null): float
    {
        $next = $this->nextPayableInstallment($credit, $from);

        if (! $next) {
            return 0.0;
        }

        $installmentRoom = $this->cents($this->effectivePendingFor($credit)[$next->id] ?? 0);

        return $this->money(max(0, min($installmentRoom, $this->balanceDueCents($credit))));
    }

    /**
     * Saldo real del crédito: lo contratado menos todo lo pagado (mensualidades
     * + abonos libres).
     */
    public function balanceDue(CreditPurchase $credit): float
    {
        return $this->money($this->balanceDueCents($credit));
    }

    // ---------------------------------------------------------------- interno

    /**
     * @return array<int, int> installment_id => centavos abonados
     */
    private function allocationCents(CreditPurchase $credit): array
    {
        if (array_key_exists($credit->id, $this->allocations)) {
            return $this->allocations[$credit->id];
        }

        $installments = $this->orderedInstallments($credit);
        $allocated = [];
        $holes = [];

        foreach ($installments as $installment) {
            $allocated[$installment->id] = 0;
            $holes[$installment->id] = $this->holeCents($installment);
        }

        foreach ($this->orderedFreePayments($credit) as $payment) {
            $pool = $this->cents($payment->amount_applied);

            if ($pool <= 0) {
                continue;
            }

            $anchor = $payment->paid_on?->copy()->startOfMonth();

            foreach ($installments as $installment) {
                if ($pool <= 0) {
                    break;
                }

                $period = $this->periodMonthOf($installment);

                if ($anchor && $period && $period->lt($anchor)) {
                    continue;
                }

                $room = $holes[$installment->id] - $allocated[$installment->id];

                if ($room <= 0) {
                    continue;
                }

                $take = min($pool, $room);
                $allocated[$installment->id] += $take;
                $pool -= $take;
            }
        }

        return $this->allocations[$credit->id] = $allocated;
    }

    /**
     * @return Collection<int, CreditInstallment>
     */
    private function orderedInstallments(CreditPurchase $credit): Collection
    {
        return $credit->installments
            ->sortBy(fn (CreditInstallment $installment) => $this->sortKey($installment))
            ->values();
    }

    /**
     * Clave cronológica estable: fecha de vencimiento, luego mes del periodo,
     * luego número de mensualidad como último desempate.
     */
    private function sortKey(CreditInstallment $installment): string
    {
        return sprintf(
            '%s|%s|%06d',
            $installment->due_date?->toDateString() ?? '9999-12-31',
            $installment->period_month?->toDateString() ?? '9999-12-01',
            (int) $installment->installment_number
        );
    }

    /**
     * @return Collection<int, CreditFreePayment>
     */
    private function orderedFreePayments(CreditPurchase $credit): Collection
    {
        return $credit->freePayments
            ->sortBy(fn (CreditFreePayment $payment) => sprintf(
                '%s|%012d',
                $payment->paid_on?->toDateString() ?? '0000-01-01',
                (int) $payment->id
            ))
            ->values();
    }

    private function periodMonthOf(CreditInstallment $installment): ?Carbon
    {
        return $installment->period_month?->copy()->startOfMonth()
            ?? $installment->due_date?->copy()->startOfMonth();
    }

    private function holeCents(CreditInstallment $installment): int
    {
        return max(0, $this->cents($installment->amount) - $this->cents($installment->paid_amount));
    }

    private function settled(CreditInstallment $installment): bool
    {
        return in_array($installment->status, ['paid', 'skipped'], true);
    }

    private function balanceDueCents(CreditPurchase $credit): int
    {
        $paid = 0;

        foreach ($credit->installments as $installment) {
            $paid += $this->cents($installment->paid_amount);
        }

        foreach ($credit->freePayments as $payment) {
            $paid += $this->cents($payment->amount_applied);
        }

        return max(0, $this->cents($credit->total_amount) - $paid);
    }

    private function cents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function money(int|float $cents): float
    {
        return round(((int) $cents) / 100, 2);
    }
}
