<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceBuildDeployService;
use App\Services\Finance\FinanceFailureReporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Actualización de assets / public build (owner-only).
 *
 * Permite subir un .zip generado localmente con `npm run build` y montarlo como
 * public/build, sin compilar en el servidor, sin npm y sin comandos de sistema.
 * Toda la lógica pesada vive en FinanceBuildDeployService. El destino es fijo
 * (public/build); el request nunca define rutas ni carpeta destino.
 */
class FinanceBuildDeployController extends Controller
{
    public function __construct(
        private readonly FinanceBuildDeployService $builds,
        private readonly FinanceFailureReporter $failures,
    ) {
    }

    public function uploadBuild(Request $request)
    {
        $request->validate([
            'build' => ['required', 'file', 'max:51200'],
            'confirm_build' => ['accepted'],
        ], [
            'build.required' => 'Selecciona el archivo build.zip generado con npm run build.',
            'build.file' => 'Sube un archivo válido.',
            'build.max' => 'El archivo supera el tamaño máximo permitido (50 MB).',
            'confirm_build.accepted' => 'Confirma que el ZIP viene de npm run build local y contiene manifest.json.',
        ]);

        $file = $request->file('build');

        // Aceptar únicamente .zip (extensión del archivo subido).
        if (strtolower((string) $file->getClientOriginalExtension()) !== 'zip') {
            return back()->with('error', 'Solo se aceptan archivos .zip.');
        }

        $stored = $file->store('build-deploys-tmp');
        $path = Storage::disk('local')->path($stored);

        $result = $this->builds->deployFromZip($path);

        if (is_file($path)) {
            @unlink($path);
        }

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'build', 'deploy', $result['message'] ?? 'No se pudo montar el build.', [
                'file' => $file->getClientOriginalName(),
            ]);

            return back()->with('error', $result['message'] ?? 'No se pudo montar el build.');
        }

        return redirect()
            ->route('finance.security.index')
            ->with('success', $result['message'] . (($result['backup'] ?? null) ? ' Respaldo del build anterior: ' . $result['backup'] : ''))
            ->with('maintenance_result', [
                'ok' => true,
                'action' => $result['action'] ?? 'build:deploy',
                'output' => $result['output'] ?? '',
            ]);
    }

    public function rollbackBuild(Request $request)
    {
        $data = $request->validate([
            'backup' => ['nullable', 'string', 'max:200'],
        ]);

        $result = $this->builds->rollback($data['backup'] ?? null);

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'build', 'rollback', $result['message'] ?? 'No se pudo restaurar el build.');

            return back()->with('error', $result['message'] ?? 'No se pudo restaurar el build.');
        }

        return redirect()
            ->route('finance.security.index')
            ->with('success', $result['message'])
            ->with('maintenance_result', [
                'ok' => true,
                'action' => $result['action'] ?? 'build:rollback',
                'output' => $result['output'] ?? '',
            ]);
    }

    public function cleanupBuildBackups(Request $request)
    {
        $result = $this->builds->cleanupBackups(1);

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'build', 'cleanup', $result['message'] ?? 'No se pudieron limpiar los respaldos.');
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
