<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\FinanceAdvisorSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class FinanceAdvisorApiController extends Controller
{
    public function __construct(
        private readonly FinanceAdvisorSnapshotService $snapshots,
    ) {}

    public function snapshot(Request $request): JsonResponse
    {
        $ownerEmail = mb_strtolower(trim((string) config('finance.owner_email')));

        if ($ownerEmail === '') {
            return $this->response([
                'ok' => false,
                'status' => 'owner_not_configured',
                'message' => 'No está configurado el propietario financiero.',
            ], 503);
        }

        $owner = User::query()
            ->whereRaw('LOWER(email) = ?', [$ownerEmail])
            ->first();

        if (! $owner) {
            return $this->response([
                'ok' => false,
                'status' => 'owner_not_found',
                'message' => 'No se encontró al propietario financiero configurado.',
            ], 503);
        }

        try {
            $snapshot = $this->snapshots->build($owner);

            Log::notice('Finance advisor snapshot accessed.', [
                'owner_id' => $owner->id,
                'ip' => $request->ip(),
                'history_days' => data_get($snapshot, 'scope.history_days'),
                'horizon_days' => data_get($snapshot, 'scope.horizon_days'),
            ]);

            return $this->response([
                'ok' => true,
                'snapshot' => $snapshot,
            ]);
        } catch (Throwable $exception) {
            Log::error('Finance advisor snapshot failed.', [
                'owner_id' => $owner->id,
                'ip' => $request->ip(),
                'exception' => $exception::class,
            ]);

            return $this->response([
                'ok' => false,
                'status' => 'snapshot_failed',
                'message' => 'No se pudo construir el resumen financiero.',
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function response(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
