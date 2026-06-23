<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Services\Finance\FinanceExcelImportService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('finance:import-excel {path=D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx} {--user=test@example.com} {--fresh}', function () {
    $user = User::where('email', $this->option('user'))->first();

    if (!$user) {
        $this->error('No existe el usuario: ' . $this->option('user'));
        return self::FAILURE;
    }

    $counts = app(FinanceExcelImportService::class)->import(
        $user,
        $this->argument('path'),
        (bool) $this->option('fresh')
    );

    foreach ($counts as $label => $count) {
        $this->line($label . ': ' . $count);
    }

    $this->info('Importacion completada.');

    return self::SUCCESS;
})->purpose('Importa movimientos, pagos, rendimientos y cortes desde el Excel financiero.');
