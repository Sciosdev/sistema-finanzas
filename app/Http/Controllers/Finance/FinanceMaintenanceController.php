<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
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
    ) {
    }

    public function runMigrations(Request $request)
    {
        $request->validate([
            'confirm_backup' => ['accepted'],
        ], [
            'confirm_backup.accepted' => 'Confirma que ya hiciste backup de la BD antes de ejecutar migraciones.',
        ]);

        $result = $this->maintenance->runMigrations();

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'mantenimiento', 'migrate', 'No se pudieron ejecutar las migraciones.', [
                'output' => substr((string) ($result['output'] ?? ''), 0, 500),
            ]);
        }

        return redirect()
            ->route('finance.security.index')
            ->with($result['ok'] ? 'success' : 'error', $result['ok']
                ? 'Migraciones ejecutadas correctamente.'
                : 'No se pudieron ejecutar las migraciones. Revisa el detalle.')
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
