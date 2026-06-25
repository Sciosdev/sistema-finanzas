<?php

namespace App\Services\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FinanceHealthCheckService
{
    private const EXPECTED_TIMEZONE = 'America/Mexico_City';

    private const CRITICAL_TABLES = [
        'users',
        'finance_accounts',
        'finance_categories',
        'finance_movements',
        'finance_planned_payments',
        'finance_expected_incomes',
        'finance_credit_purchases',
        'finance_daily_cuts',
        'finance_delete_snapshots',
        'finance_system_failures',
    ];

    private const CRITICAL_ROUTES = [
        'login',
        'finance.dashboard',
        'finance.security.index',
        'finance.reports.index',
        'finance.movements.index',
    ];

    public function __construct(private readonly FinanceBackupService $backups)
    {
    }

    public function run(): Collection
    {
        return collect([
            $this->envFileExists(),
            $this->appKeyIsConfigured(),
            $this->debugDisabledInProduction(),
            $this->timezoneIsExpected(),
            $this->databaseConnectionWorks(),
            ...$this->criticalTablesExist(),
            $this->fileExists('Autoload de Composer', base_path('vendor/autoload.php'), 'vendor/autoload.php existe.', 'vendor/autoload.php no existe.'),
            $this->fileExists('Manifest de assets', public_path('build/manifest.json'), 'public/build/manifest.json existe.', 'public/build/manifest.json no existe.'),
            ...$this->storageIsWritable(),
            $this->currentLogIsAccessible(),
            $this->backupsAreListable(),
            ...$this->basicPermissions(),
            ...$this->criticalRoutesExist(),
        ]);
    }

    private function envFileExists(): array
    {
        return $this->fileExists('Archivo .env', base_path('.env'), '.env existe en base_path().', '.env no existe en base_path().');
    }

    private function appKeyIsConfigured(): array
    {
        $configured = filled((string) config('app.key'));

        return $this->check(
            'APP_KEY',
            $configured ? 'ok' : 'fail',
            $configured ? 'APP_KEY está configurada.' : 'APP_KEY está vacía.',
            $configured ? 'config(app.key) presente.' : 'config(app.key) vacío.'
        );
    }

    private function debugDisabledInProduction(): array
    {
        if (! app()->environment('production')) {
            return $this->check('APP_DEBUG en producción', 'ok', 'No aplica fuera de producción.', 'environment=' . app()->environment());
        }

        $debug = (bool) config('app.debug');

        return $this->check(
            'APP_DEBUG en producción',
            $debug ? 'fail' : 'ok',
            $debug ? 'APP_DEBUG debe estar desactivado en producción.' : 'APP_DEBUG está desactivado en producción.',
            'config(app.debug)=' . ($debug ? 'true' : 'false')
        );
    }

    private function timezoneIsExpected(): array
    {
        $timezone = (string) config('app.timezone');
        $ok = $timezone === self::EXPECTED_TIMEZONE;

        return $this->check(
            'Timezone',
            $ok ? 'ok' : 'warning',
            $ok ? 'Timezone correcto.' : 'Timezone distinto al esperado.',
            'actual=' . $timezone . '; esperado=' . self::EXPECTED_TIMEZONE
        );
    }

    private function databaseConnectionWorks(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->check('Conexión DB', 'ok', 'La conexión a la base de datos responde.', 'PDO disponible.');
        } catch (Throwable $exception) {
            return $this->check('Conexión DB', 'fail', 'No se pudo conectar a la base de datos.', $this->shortException($exception));
        }
    }

    private function criticalTablesExist(): array
    {
        return array_map(function (string $table): array {
            try {
                $exists = Schema::hasTable($table);

                return $this->check('Tabla ' . $table, $exists ? 'ok' : 'fail', $exists ? 'Tabla crítica disponible.' : 'Falta una tabla crítica.', $table);
            } catch (Throwable $exception) {
                return $this->check('Tabla ' . $table, 'fail', 'No se pudo validar la tabla crítica.', $this->shortException($exception));
            }
        }, self::CRITICAL_TABLES);
    }

    private function storageIsWritable(): array
    {
        $paths = [
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
        ];

        return array_map(fn (string $label, string $path): array => $this->directoryWritable('Escritura ' . $label, $path), array_keys($paths), $paths);
    }

    private function currentLogIsAccessible(): array
    {
        $log = storage_path('logs/laravel.log');
        $logsDirectory = storage_path('logs');

        if (is_file($log) && is_readable($log)) {
            return $this->check('Log actual', 'ok', 'El log actual es accesible.', 'storage/logs/laravel.log readable.');
        }

        if (is_writable($logsDirectory)) {
            return $this->check('Log actual', 'warning', 'No hay log actual legible, pero storage/logs es escribible.', 'storage/logs writable.');
        }

        return $this->check('Log actual', 'fail', 'No se puede leer el log actual ni escribir en storage/logs.', 'storage/logs no writable.');
    }

    private function backupsAreListable(): array
    {
        try {
            $backups = $this->backups->listBackups();
            $total = collect($backups)->flatten(1)->count();

            return $this->check('Backups listables', 'ok', 'Los backups se pueden listar.', 'total=' . $total);
        } catch (Throwable $exception) {
            return $this->check('Backups listables', 'fail', 'No se pudieron listar los backups.', $this->shortException($exception));
        }
    }

    private function basicPermissions(): array
    {
        return [
            $this->directoryWritable('Permisos storage', storage_path()),
            $this->directoryWritable('Permisos bootstrap/cache', base_path('bootstrap/cache')),
            $this->directoryReadable('Permisos public/build', public_path('build')),
        ];
    }

    private function criticalRoutesExist(): array
    {
        return array_map(fn (string $route): array => $this->check(
            'Ruta ' . $route,
            Route::has($route) ? 'ok' : 'fail',
            Route::has($route) ? 'Ruta registrada.' : 'Ruta crítica no registrada.',
            $route
        ), self::CRITICAL_ROUTES);
    }

    private function fileExists(string $name, string $path, string $success, string $failure): array
    {
        $exists = is_file($path);

        return $this->check($name, $exists ? 'ok' : 'fail', $exists ? $success : $failure, $this->relativePath($path));
    }

    private function directoryWritable(string $name, string $path): array
    {
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);

        return $this->check($name, $writable ? 'ok' : 'fail', $writable ? 'Directorio escribible.' : 'Directorio inexistente o sin escritura.', $this->relativePath($path));
    }

    private function directoryReadable(string $name, string $path): array
    {
        $exists = is_dir($path);
        $readable = $exists && is_readable($path);

        return $this->check($name, $readable ? 'ok' : 'fail', $readable ? 'Directorio legible.' : 'Directorio inexistente o sin lectura.', $this->relativePath($path));
    }

    private function check(string $name, string $status, string $message, string $detail): array
    {
        return compact('name', 'status', 'message', 'detail');
    }

    private function shortException(Throwable $exception): string
    {
        return substr(class_basename($exception) . ': ' . $exception->getMessage(), 0, 180);
    }

    private function relativePath(string $path): string
    {
        return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
    }
}
