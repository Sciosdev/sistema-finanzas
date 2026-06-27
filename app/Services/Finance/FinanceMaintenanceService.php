<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Mantenimiento seguro para hosting sin terminal (HostGator/cPanel).
 *
 * SOLO ejecuta comandos Artisan fijos y embebidos en el código:
 *   - migrate:status   (solo lectura)
 *   - migrate --force  (aplica únicamente migraciones pendientes)
 *   - optimize:clear   (limpia caché)
 *
 * No recibe comandos desde el request, no usa funciones de sistema, y no existe
 * ninguna ruta ni código que invoque comandos destructivos de base de datos.
 */
class FinanceMaintenanceService
{
    /**
     * Estado de solo lectura para mostrar al abrir Seguridad. No modifica nada.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $migrationsTableExists = false;
        try {
            $migrationsTableExists = Schema::hasTable('migrations');
        } catch (Throwable) {
            $migrationsTableExists = false;
        }

        $statusOutput = '';
        try {
            Artisan::call('migrate:status');
            $statusOutput = trim(Artisan::output());
        } catch (Throwable $exception) {
            $statusOutput = 'No se pudo obtener el estado de migraciones: ' . $exception->getMessage();
        }

        return [
            'app_env' => (string) app()->environment(),
            'db_connection' => (string) config('database.default'),
            'migrations_table_exists' => $migrationsTableExists,
            'status_output' => $statusOutput !== '' ? $statusOutput : 'Sin información de migraciones.',
            'has_pending' => str_contains(mb_strtolower($statusOutput), 'pending'),
        ];
    }

    /**
     * Ejecuta únicamente las migraciones pendientes.
     *
     * @return array{ok: bool, action: string, output: string}
     */
    public function runMigrations(): array
    {
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            return [
                'ok' => $exitCode === 0,
                'action' => 'migrate --force',
                'output' => trim(Artisan::output()) ?: 'Sin salida del comando.',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'action' => 'migrate --force',
                'output' => 'Error: ' . $exception->getMessage(),
            ];
        }
    }

    /**
     * Limpia las cachés de configuración, rutas, vistas y eventos.
     *
     * @return array{ok: bool, action: string, output: string}
     */
    public function clearOptimizationCache(): array
    {
        try {
            $exitCode = Artisan::call('optimize:clear');

            return [
                'ok' => $exitCode === 0,
                'action' => 'optimize:clear',
                'output' => trim(Artisan::output()) ?: 'Sin salida del comando.',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'action' => 'optimize:clear',
                'output' => 'Error: ' . $exception->getMessage(),
            ];
        }
    }
}
