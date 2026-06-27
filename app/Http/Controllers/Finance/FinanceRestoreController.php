<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceBackupService;
use App\Services\Finance\FinanceFailureReporter;
use App\Services\Finance\FinanceRestoreService;
use Illuminate\Http\Request;

/**
 * Restauración estilo All-in-One (owner-only).
 *
 * Es destructivo: reemplaza toda la base con el paquete elegido. Por seguridad:
 *  - Exige escribir la frase exacta "RESTAURAR".
 *  - Crea un backup automático ANTES de restaurar; si ese backup falla, aborta.
 *  - Registra cualquier fallo en el reportador de fallas.
 */
class FinanceRestoreController extends Controller
{
    public function __construct(
        private readonly FinanceRestoreService $restore,
        private readonly FinanceBackupService $backups,
        private readonly FinanceFailureReporter $failures,
    ) {
    }

    public function restoreFromBackup(Request $request)
    {
        $data = $request->validate([
            'backup' => ['required', 'string'],
            'confirm_phrase' => ['required', 'in:RESTAURAR'],
        ], $this->phraseMessages());

        [$type, $filename] = $this->splitBackup($data['backup']);

        if (! in_array($type, ['migration', 'database'], true)) {
            return back()->with('error', 'Tipo de respaldo no válido para restaurar.');
        }

        try {
            $path = $this->backups->downloadPath($type, $filename);
        } catch (\Throwable) {
            return back()->with('error', 'No se encontró el respaldo seleccionado.');
        }

        return $this->runRestore($request, $path, 'lista:' . $filename);
    }

    public function restoreFromUpload(Request $request)
    {
        $request->validate([
            'package' => ['required', 'file', 'max:51200'],
            'confirm_phrase' => ['required', 'in:RESTAURAR'],
        ], $this->phraseMessages());

        $stored = $request->file('package')->store('finance-restore-tmp');
        $path = storage_path('app/' . $stored);

        $response = $this->runRestore($request, $path, 'archivo:' . $request->file('package')->getClientOriginalName());

        if (is_file($path)) {
            @unlink($path);
        }

        return $response;
    }

    private function runRestore(Request $request, string $packagePath, string $source)
    {
        // Backup automático ANTES de restaurar (red de seguridad). Si falla, no se restaura.
        $backup = $this->backups->createMigrationPackage();

        if (! ($backup['ok'] ?? false)) {
            $this->failures->report($request->user(), 'restauracion', 'auto-backup', 'No se pudo crear el backup previo a restaurar.', [
                'detail' => substr((string) ($backup['message'] ?? ''), 0, 300),
            ]);

            return redirect()
                ->route('finance.security.index')
                ->with('error', 'No se restauró nada porque falló el backup automático previo. ' . ($backup['message'] ?? ''));
        }

        $result = $this->restore->restoreFromZip($packagePath);

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'restauracion', 'restore', 'Falló la restauración del paquete.', [
                'source' => $source,
                'detail' => substr((string) ($result['message'] ?? ''), 0, 300),
            ]);
        }

        return redirect()
            ->route('finance.security.index')
            ->with($result['ok'] ? 'success' : 'error', ($result['ok']
                ? 'Restauración completada. '
                : 'No se pudo restaurar (revisa el detalle). ')
                . 'Se creó un backup previo: ' . $backup['name'])
            ->with('maintenance_result', [
                'ok' => (bool) ($result['ok'] ?? false),
                'action' => 'restore (' . $source . ')',
                'output' => (string) ($result['message'] ?? ''),
            ])
            ->with('backup_download', [
                'type' => $backup['type'],
                'name' => $backup['name'],
                'size' => $backup['size'] ?? null,
            ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitBackup(string $value): array
    {
        $parts = explode('::', $value, 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * @return array<string, string>
     */
    private function phraseMessages(): array
    {
        return [
            'confirm_phrase.required' => 'Escribe RESTAURAR para confirmar.',
            'confirm_phrase.in' => 'Escribe exactamente RESTAURAR (en mayúsculas) para confirmar.',
        ];
    }
}
