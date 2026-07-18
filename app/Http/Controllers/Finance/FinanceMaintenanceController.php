<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceBackupService;
use App\Services\Finance\FinanceDeploymentService;
use App\Services\Finance\FinanceFailureReporter;
use App\Services\Finance\FinanceMaintenanceService;
use Illuminate\Http\Request;

/**
 * Acciones de mantenimiento (owner-only) para HostGator/cPanel sin terminal.
 *
 * No acepta ningún comando desde el request: cada acción llama a un comando
 * Artisan fijo a través de FinanceMaintenanceService. No hay forma de ejecutar
 * comandos arbitrarios ni destructivos desde aquí.
 */
class FinanceMaintenanceController extends Controller
{
    public function __construct(
        private readonly FinanceMaintenanceService $maintenance,
        private readonly FinanceFailureReporter $failures,
        private readonly FinanceBackupService $backups,
        private readonly FinanceDeploymentService $deployments,
    ) {}

    public function runMigrations(Request $request)
    {
        $request->validate([
            'confirm_backup' => ['accepted'],
        ], [
            'confirm_backup.accepted' => 'Confirma para crear el backup automático y ejecutar las migraciones.',
        ]);

        // Backup automático ANTES de migrar (paquete de migración: PHP puro, sin
        // mysqldump, en .zip). Si el backup falla, NO se migra: red de seguridad.
        $backup = $this->backups->createMigrationPackage();

        if (! ($backup['ok'] ?? false)) {
            $this->failures->report($request->user(), 'mantenimiento', 'auto-backup', 'No se pudo crear el backup automático antes de migrar.', [
                'detail' => substr((string) ($backup['message'] ?? ''), 0, 300),
            ]);

            return redirect()
                ->route('finance.security.index')
                ->with('error', 'No se ejecutaron las migraciones porque falló el backup automático. '.($backup['message'] ?? ''));
        }

        $result = $this->maintenance->runMigrations();

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'mantenimiento', 'migrate', 'No se pudieron ejecutar las migraciones.', [
                'output' => substr((string) ($result['output'] ?? ''), 0, 500),
            ]);
        }

        return redirect()
            ->route('finance.security.index')
            ->with($result['ok'] ? 'success' : 'error', ($result['ok']
                ? 'Migraciones ejecutadas correctamente. '
                : 'No se pudieron ejecutar las migraciones (revisa el detalle). ')
                .'Se creó un backup automático: '.$backup['name'])
            ->with('maintenance_result', $result)
            ->with('backup_download', [
                'type' => $backup['type'],
                'name' => $backup['name'],
                'size' => $backup['size'] ?? null,
            ]);
    }

    public function deployFromRemote(Request $request)
    {
        $request->validate([
            'confirm_deploy' => ['accepted'],
        ], [
            'confirm_deploy.accepted' => 'Confirma para crear el backup y actualizar producción.',
        ]);

        $result = $this->deployments->deploy(
            source: 'web',
            actorId: $request->user()?->id,
            ip: $request->ip(),
        );

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'despliegue', 'update-from-remote', (string) ($result['message'] ?? 'Falló el despliegue.'), [
                'status' => (string) ($result['status'] ?? 'unknown'),
                'after_commit' => (string) data_get($result, 'after.commit', ''),
            ]);
        }

        return redirect()
            ->route('finance.security.index')
            ->with($result['ok'] ? 'success' : 'error', (string) $result['message'])
            ->with('deployment_result', $result);
    }

    public function optimizeForProduction(Request $request)
    {
        $result = $this->maintenance->optimizeForProduction();

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'mantenimiento', 'optimize', 'No se pudo optimizar (cachear) la app.', [
                'output' => substr((string) ($result['output'] ?? ''), 0, 500),
            ]);
        }

        return redirect()
            ->route('finance.security.index')
            ->with($result['ok'] ? 'success' : 'error', $result['ok']
                ? 'App optimizada: configuración, rutas y vistas quedaron cacheadas. Vuelve a ejecutarlo después de cada git pull o cambio en .env.'
                : 'No se pudo optimizar. Revisa el detalle.')
            ->with('maintenance_result', $result);
    }

    public function clearOptimizationCache(Request $request)
    {
        $request->validate([
            'confirm_clear' => ['accepted'],
        ], [
            'confirm_clear.accepted' => 'Confirma para limpiar la caché.',
        ]);

        $result = $this->maintenance->clearOptimizationCache();

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'mantenimiento', 'optimize:clear', 'No se pudo limpiar la caché.', [
                'output' => substr((string) ($result['output'] ?? ''), 0, 500),
            ]);
        }

        return redirect()
            ->route('finance.security.index')
            ->with($result['ok'] ? 'success' : 'error', $result['ok']
                ? 'Caché limpiada correctamente.'
                : 'No se pudo limpiar la caché. Revisa el detalle.')
            ->with('maintenance_result', $result);
    }
}
