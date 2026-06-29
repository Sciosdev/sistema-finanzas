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

        $pending = $this->pendingMigrations();

        return [
            'app_env' => (string) app()->environment(),
            'db_connection' => (string) config('database.default'),
            'migrations_table_exists' => $migrationsTableExists,
            'status_output' => $statusOutput !== '' ? $statusOutput : 'Sin información de migraciones.',
            'pending' => $pending,
            'pending_count' => count($pending),
            'has_pending' => count($pending) > 0,
            'has_destructive_pending' => (bool) collect($pending)->contains(fn (array $migration) => $migration['destructive']),
        ];
    }

    /**
     * Lista las migraciones pendientes (archivos que aún no están registrados en
     * la tabla migrations), marcando las que parecen poder borrar datos.
     *
     * @return array<int, array{name: string, destructive: bool}>
     */
    private function pendingMigrations(): array
    {
        try {
            /** @var \Illuminate\Database\Migrations\Migrator $migrator */
            $migrator = app('migrator');
            $paths = array_unique(array_merge([database_path('migrations')], $migrator->paths()));
            $files = $migrator->getMigrationFiles($paths);

            $repository = $migrator->getRepository();
            $ran = $repository->repositoryExists() ? $repository->getRan() : [];

            $pending = [];
            foreach ($files as $name => $path) {
                if (in_array($name, $ran, true)) {
                    continue;
                }

                $pending[] = [
                    'name' => (string) $name,
                    'destructive' => $this->isDestructiveMigrationContent((string) @file_get_contents($path)),
                ];
            }

            return $pending;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Heurística: detecta operaciones que podrían borrar datos en una migración
     * (eliminar tablas/columnas, truncar o borrar registros). Solo es un aviso.
     */
    public function isDestructiveMigrationContent(string $contents): bool
    {
        $lower = mb_strtolower($contents);

        foreach (['dropifexists', 'dropcolumn', 'schema::drop', 'droptable', 'drop table', 'truncate', 'delete from', 'wipe'] as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
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
     * Cachea configuración, rutas y vistas (config:cache + route:cache +
     * view:cache + event:cache vía `optimize`). En hosting compartido acelera
     * MUCHO cada request porque deja de re-parsear config y re-registrar rutas.
     * Hay que volver a ejecutarlo después de cada git pull o cambio en .env.
     *
     * @return array{ok: bool, action: string, output: string}
     */
    public function optimizeForProduction(): array
    {
        try {
            $exitCode = Artisan::call('optimize');

            return [
                'ok' => $exitCode === 0,
                'action' => 'optimize',
                'output' => trim(Artisan::output()) ?: 'Sin salida del comando.',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'action' => 'optimize',
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
