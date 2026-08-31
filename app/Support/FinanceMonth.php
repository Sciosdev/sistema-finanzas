<?php

namespace App\Support;

use Carbon\Carbon;

class FinanceMonth
{
    /**
     * Convierte el valor YYYY-MM de un input type="month" al primer dia.
     *
     * El formato Y-m por si solo hereda el dia actual. En un dia 31 eso puede
     * desbordar meses cortos (por ejemplo, 2026-09 termina como 2026-10-01).
     */
    public static function parse(string $value): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $value)->startOfMonth();
    }
}
