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

    private const REQUIRED_PHP_EXTENSIONS = [
        'pdo_mysql',
        'mbstring',
        'openssl',
        'zip',
        'fileinfo',
        'json',
        'ctype',
        'tokenizer',
        'xml',
    ];

    private const MINIMUM_PHP_VERSION_ID = 80200;

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
            $this->appUrlIsExpected(),
            $this->ownerEmailConfigured(),
            $this->registrationStateIsSafe(),
            $this->phpVersionIsSupported(),
            ...$this->phpExtensionsLoaded(),
            $this->databaseConnectionWorks(),
            ...$this->databaseCriticalVars(),
            ...$this->criticalTablesExist(),
            $this->vendorAutoloadExists(),
            $this->buildManifestExists(),
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

    private function appUrlIsExpected(): array
    {
        $current = trim((string) config('app.url'));
        $expected = trim((string) config('finance.expected_app_url'));

        if ($current === '') {
            return $this->check('APP_URL', 'fail', 'APP_URL está vacía.', 'config(app.url) vacío.');
        }

        if ($expected === '' || $current === $expected) {
            return $this->check('APP_URL', 'ok', 'APP_URL configurada.', 'actual=' . $current);
        }

        $status = app()->environment('production') ? 'warning' : 'ok';

        return $this->check(
            'APP_URL',
            $status,
            $status === 'warning'
                ? 'APP_URL no coincide con la URL esperada de producción.'
                : 'APP_URL configurada (no aplica comparación fuera de producción).',
            'actual=' . $current . '; esperado=' . $expected
        );
    }

    private function ownerEmailConfigured(): array
    {
        $configured = trim((string) config('finance.owner_email')) !== '';

        return $this->check(
            'FINANCE_OWNER_EMAIL',
            $configured ? 'ok' : 'fail',
            $configured
                ? 'El dueño financiero está configurado.'
                : 'FINANCE_OWNER_EMAIL está vacío: nadie podrá entrar a Seguridad ni Diagnóstico.',
            $configured ? 'config(finance.owner_email) presente.' : 'config(finance.owner_email) vacío.'
        );
    }

    private function registrationStateIsSafe(): array
    {
        $enabled = (bool) config('auth.registration_enabled');
        $isProduction = app()->environment('production');

        if ($isProduction && $enabled) {
            return $this->check(
                'Registro de usuarios',
                'fail',
                'El registro está abierto en producción; debe estar cerrado.',
                'config(auth.registration_enabled)=true'
            );
        }

        return $this->check(
            'Registro de usuarios',
            'ok',
            $enabled ? 'Registro abierto (permitido fuera de producción).' : 'Registro cerrado.',
            'config(auth.registration_enabled)=' . ($enabled ? 'true' : 'false')
        );
    }

    private function phpVersionIsSupported(): array
    {
        $supported = PHP_VERSION_ID >= self::MINIMUM_PHP_VERSION_ID;

        return $this->check(
            'Versión de PHP',
            $supported ? 'ok' : 'fail',
            $supported ? 'La versión de PHP es compatible.' : 'La versión de PHP es menor a la mínima soportada (8.2).',
            'actual=' . PHP_VERSION . '; mínimo=8.2'
        );
    }

    private function phpExtensionsLoaded(): array
    {
        return array_map(function (string $extension): array {
            $loaded = extension_loaded($extension);

            return $this->check(
                'Extensión ' . $extension,
                $loaded ? 'ok' : 'fail',
                $loaded ? 'Extensión PHP disponible.' : 'Falta una extensión PHP requerida.',
                $extension
            );
        }, self::REQUIRED_PHP_EXTENSIONS);
    }

    private function databaseCriticalVars(): array
    {
        $connectionName = (string) config('database.default');
        $connection = (array) config("database.connections.{$connectionName}", []);

        $variables = [
            'DB_CONNECTION' => $connectionName,
            'DB_DATABASE' => (string) ($connection['database'] ?? ''),
            'DB_USERNAME' => (string) ($connection['username'] ?? ''),
        ];

        return array_map(function (string $name, string $value): array {
            $present = trim($value) !== '';

            return $this->check(
                $name,
                $present ? 'ok' : 'fail',
                $present ? 'Variable de BD presente.' : 'Falta una variable crítica de BD.',
                $name . '=' . ($present ? 'presente' : 'ausente')
            );
        }, array_keys($variables), array_values($variables));
    }

    private function vendorAutoloadExists(): array
    {
        $path = base_path('vendor/autoload.php');
        $exists = is_file($path);

        return $this->check(
            'Autoload de Composer',
            $exists ? 'ok' : 'fail',
            $exists
                ? 'vendor/autoload.php existe.'
                : 'Falta vendor/autoload.php. Sube vendor manualmente; HostGator no ejecuta composer install.',
            'vendor/autoload.php'
        );
    }

    private function buildManifestExists(): array
    {
        $path = public_path('build/manifest.json');
        $exists = is_file($path);

        return $this->check(
            'Manifest de assets',
            $exists ? 'ok' : 'fail',
            $exists
                ? 'public/build/manifest.json existe.'
                : 'Falta public/build/manifest.json. Sube public/build manualmente o genera el paquete de producción.',
            'public/build/manifest.json'
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
