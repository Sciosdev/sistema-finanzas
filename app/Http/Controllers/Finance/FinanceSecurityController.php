<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\DeleteSnapshot;
use App\Models\Finance\SystemFailure;
use App\Services\Finance\FinanceBackupService;
use App\Services\Finance\FinanceBuildDeployService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceFailureReporter;
use App\Services\Finance\FinanceMaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class FinanceSecurityController extends Controller
{
    public function __construct(
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
        private readonly FinanceBackupService $backups,
        private readonly FinanceFailureReporter $failures,
        private readonly FinanceMaintenanceService $maintenance,
        private readonly FinanceBuildDeployService $builds,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate([
            'module' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:open,resolved'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $failureQuery = SystemFailure::query()
            ->where('user_id', $user->id)
            ->orderByDesc('occurred_at');

        if (! empty($filters['module'])) {
            $failureQuery->where('module', $filters['module']);
        }

        if (! empty($filters['status'])) {
            $failureQuery->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $failureQuery->whereDate('occurred_at', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }

        if (! empty($filters['date_to'])) {
            $failureQuery->whereDate('occurred_at', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }

        return view('finance.security.index', [
            'backups' => $this->backups->listBackups(),
            'exports' => $this->listExportFiles(),
            'externalBackupPath' => config('finance.external_backup_path'),
            'snapshots' => DeleteSnapshot::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'failures' => $failureQuery->limit(50)->get(),
            'filters' => $filters,
            'failureModules' => SystemFailure::where('user_id', $user->id)
                ->select('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
            'maintenance' => $this->maintenance->status(),
            'maintenanceResult' => session('maintenance_result'),
            'buildDeploy' => $this->builds->status(),
        ]);
    }

    public function undoDelete(Request $request, string $token)
    {
        $result = $this->deleteSnapshots->restore($request->user(), $token);

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'deshacer', 'restore', $result['message'] ?? 'No se pudo deshacer el borrado.', [
                'token_prefix' => substr($token, 0, 8),
            ]);
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function createDatabaseBackup()
    {
        return $this->backupResponse($this->backups->createDatabaseBackup(), 'database');
    }

    public function createFullBackup(Request $request)
    {
        return $this->backupResponse($this->backups->createFullBackup($request->boolean('include_env')), 'full');
    }

    public function createMigrationPackage()
    {
        return $this->backupResponse($this->backups->createMigrationPackage(), 'migration');
    }

    public function createExternalBackup(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:copy_latest,database,full'],
            'include_env' => ['nullable', 'boolean'],
        ]);

        $result = match ($data['mode']) {
            'database' => $this->backups->createDatabaseBackupExternal(),
            'full' => $this->backups->createFullBackupExternal($request->boolean('include_env')),
            default => $this->backups->copyLatestBackupToExternal(),
        };

        if (! ($result['ok'] ?? false)) {
            $this->failures->report($request->user(), 'backup', 'external', $result['message'] ?? 'No se pudo crear el backup externo.', [
                'mode' => $data['mode'],
            ]);

            return back()->with('error', $result['message'] ?? 'No se pudo crear el backup externo.');
        }

        return back()->with('success', ($result['message'] ?? 'Backup externo creado.') . ' Archivo: ' . $result['name']);
    }

    public function downloadBackup(Request $request, string $type, string $filename)
    {
        try {
            $path = $this->backups->downloadPath($type, $filename);
        } catch (\Throwable $exception) {
            $this->failures->report($request->user(), 'backup', 'download', 'No se pudo descargar backup: ' . $exception->getMessage(), [
                'type' => $type,
                'filename' => basename($filename),
            ]);

            abort(404);
        }

        return response()->download($path);
    }

    public function resolveFailure(Request $request, SystemFailure $failure)
    {
        abort_unless((int) $failure->user_id === (int) $request->user()->id, 403);

        $failure->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Falla marcada como resuelta.');
    }

    private function backupResponse(array $result, string $action)
    {
        if (! ($result['ok'] ?? false)) {
            $this->failures->report(request()->user(), 'backup', $action, $result['message'] ?? 'No se pudo crear el backup.', [
                'backup_type' => $action,
            ]);

            return back()->with('error', $result['message'] ?? 'No se pudo crear el backup.');
        }

        return back()
            ->with('success', ($result['message'] ?? 'Backup creado.') . ' Archivo: ' . $result['name'])
            ->with('backup_download', [
                'type' => $result['type'],
                'name' => $result['name'],
                'size' => $result['size'] ?? null,
            ]);
    }

    private function listExportFiles(): array
    {
        $directory = storage_path('app/private/finance-exports');

        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file) => in_array(strtolower($file->getExtension()), ['xlsx', 'xls', 'csv'], true))
            ->map(fn (\SplFileInfo $file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }
}
