<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Endpoint mínimo de triage para diagnosticar errores 500 en producción
 * (HostGator compartido, sin terminal) sin depender de auth, sesión ni Blade.
 *
 * Nunca debe producir un 500: todo va dentro de try/catch y se devuelve
 * texto plano. No expone secretos del .env.
 */
class FinanceTriageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $configuredToken = trim((string) config('finance.health_token'));

        // Sin token configurado: el endpoint queda deshabilitado.
        if ($configuredToken === '') {
            return $this->notFound();
        }

        $providedToken = (string) $request->query('key', '');

        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return $this->notFound();
        }

        $lines = [];
        $lines[] = 'Sistema de Finanzas - triage';
        $lines[] = 'generated_at: ' . $this->safe(fn () => now()->toIso8601String());
        $lines[] = 'environment: ' . $this->safe(fn () => (string) app()->environment());
        $lines[] = '';
        $lines[] = $this->line('.env presente', $this->boolStatus(is_file(base_path('.env'))));
        $lines[] = $this->line('APP_KEY presente', $this->boolStatus(filled((string) config('app.key'))));
        $lines[] = $this->line('vendor/autoload.php', $this->boolStatus(is_file(base_path('vendor/autoload.php'))));
        $lines[] = $this->line('public/build/manifest.json', $this->boolStatus(is_file(public_path('build/manifest.json'))));
        $lines[] = $this->line('storage escribible', $this->boolStatus($this->isWritable(storage_path())));
        $lines[] = $this->line('bootstrap/cache escribible', $this->boolStatus($this->isWritable(base_path('bootstrap/cache'))));
        $lines[] = $this->line('conexión DB', $this->databaseStatus());
        $lines[] = '';
        $lines[] = '--- últimas líneas del log (sanitizadas) ---';

        foreach ($this->tailLog(10) as $logLine) {
            $lines[] = $logLine;
        }

        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function notFound(): Response
    {
        return response('Not Found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'OK';
        } catch (Throwable $exception) {
            return 'ERROR: ' . $this->sanitize($this->shortMessage($exception));
        }
    }

    /**
     * Lee de forma segura las últimas líneas del log actual sin cargar el
     * archivo completo y sanitizando posibles secretos.
     *
     * @return list<string>
     */
    private function tailLog(int $maxLines): array
    {
        try {
            $path = storage_path('logs/laravel.log');

            if (! is_file($path) || ! is_readable($path)) {
                return ['(sin log legible)'];
            }

            $size = filesize($path);
            if ($size === false || $size === 0) {
                return ['(log vacío)'];
            }

            // Leer solo el último tramo del archivo para no cargar logs gigantes.
            $readBytes = min($size, 64 * 1024);
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return ['(no se pudo abrir el log)'];
            }

            try {
                fseek($handle, -$readBytes, SEEK_END);
                $chunk = (string) fread($handle, $readBytes);
            } finally {
                fclose($handle);
            }

            $allLines = preg_split('/\r\n|\r|\n/', trim($chunk)) ?: [];
            $tail = array_slice($allLines, -$maxLines);

            if ($tail === []) {
                return ['(log sin líneas)'];
            }

            return array_map(fn (string $line): string => $this->sanitize($line), $tail);
        } catch (Throwable) {
            return ['(no se pudo leer el log)'];
        }
    }

    private function isWritable(string $path): bool
    {
        return is_dir($path) && is_writable($path);
    }

    private function boolStatus(bool $ok): string
    {
        return $ok ? 'OK' : 'FALTA';
    }

    private function line(string $label, string $status): string
    {
        return str_pad($label, 32, ' ') . ': ' . $status;
    }

    /**
     * Redacta posibles secretos antes de mostrarlos en texto plano.
     */
    private function sanitize(string $value): string
    {
        $patterns = [
            // APP_KEY de Laravel.
            '/base64:[A-Za-z0-9+\/=]+/' => '[REDACTED]',
            // Pares clave/valor sensibles: password=..., secret: ..., token "..."
            '/(?i)(password|passwd|secret|token|api[_-]?key|app[_-]?key)\s*[=:]\s*\S+/' => '$1=[REDACTED]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $value = (string) preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    private function shortMessage(Throwable $exception): string
    {
        return substr(class_basename($exception) . ': ' . $exception->getMessage(), 0, 180);
    }

    /**
     * @param callable():string $callback
     */
    private function safe(callable $callback): string
    {
        try {
            return $callback();
        } catch (Throwable) {
            return 'n/a';
        }
    }
}
