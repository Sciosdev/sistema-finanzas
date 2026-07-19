<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceAdvisorApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = trim((string) config('finance.advisor.api_token'));

        if (mb_strlen($expected) < 32) {
            return new JsonResponse([
                'ok' => false,
                'status' => 'not_configured',
                'message' => 'La API del asesor financiero no está configurada.',
            ], 503);
        }

        $provided = (string) $request->bearerToken();

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return new JsonResponse([
                'ok' => false,
                'status' => 'unauthorized',
                'message' => 'Token del asesor financiero inválido.',
            ], 401, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        return $next($request);
    }
}
