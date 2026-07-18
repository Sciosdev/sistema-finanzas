<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceDeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FinanceDeploymentApiController extends Controller
{
    public function __construct(
        private readonly FinanceDeploymentService $deployments,
    ) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'deployment' => $this->deployments->status($request->boolean('refresh')),
        ]);
    }

    public function deploy(Request $request): JsonResponse
    {
        $request->validate([
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Envía confirm=true para autorizar el despliegue.',
        ]);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));

        if (preg_match('/^[A-Za-z0-9._-]{8,100}$/', $idempotencyKey) !== 1) {
            return response()->json([
                'ok' => false,
                'status' => 'validation_error',
                'message' => 'Envía un encabezado Idempotency-Key único de 8 a 100 caracteres.',
            ], 422);
        }

        $cacheKey = 'finance:deployment:idempotency:'.hash('sha256', $idempotencyKey);

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['replayed'] = true;

                return response()->json($cached, $this->statusCode($cached));
            }
        } catch (Throwable) {
            // La idempotencia mejora los reintentos, pero el bloqueo principal
            // sigue protegiendo el despliegue si el caché no está disponible.
        }

        $result = $this->deployments->deploy(
            source: 'api',
            actorId: null,
            ip: $request->ip(),
        );
        $result['replayed'] = false;

        if (! in_array($result['status'] ?? '', ['busy', 'not_configured'], true)) {
            try {
                Cache::put($cacheKey, $result, now()->addDay());
            } catch (Throwable) {
                // La respuesta del despliegue no depende de poder cachearla.
            }
        }

        return response()->json($result, $this->statusCode($result));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function statusCode(array $result): int
    {
        if ($result['ok'] ?? false) {
            return 200;
        }

        return match ($result['status'] ?? '') {
            'busy' => 409,
            'not_configured' => 503,
            'validation_error' => 422,
            default => 502,
        };
    }
}
