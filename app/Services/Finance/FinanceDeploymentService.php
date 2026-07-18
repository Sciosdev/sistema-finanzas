<?php

namespace App\Services\Finance;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Despliegue controlado para el repositorio administrado por cPanel.
 *
 * La actualización se realiza exclusivamente mediante la API oficial UAPI
 * VersionControl::update. No recibe comandos, rutas ni ramas desde el request.
 */
class FinanceDeploymentService
{
    private const STATUS_CACHE_KEY = 'finance:deployment:repository-status';

    public function __construct(
        private readonly FinanceBackupService $backups,
        private readonly FinanceMaintenanceService $maintenance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function status(bool $refresh = false): array
    {
        $settings = $this->settings();
        $status = [
            'configured' => $settings['configured'],
            'api_configured' => $settings['api_configured'],
            'missing' => $settings['missing'],
            'api_missing' => $settings['api_missing'],
            'cpanel_url' => $settings['cpanel_url'],
            'repository_root' => $settings['repository_root'],
            'branch' => $settings['branch'],
            'local_version' => $this->versionFromDisk(),
            'repository_found' => false,
            'repository' => null,
            'error' => null,
        ];

        if (! $settings['configured']) {
            return $status;
        }

        $repository = $this->repositoryStatus($refresh);

        return array_merge($status, [
            'repository_found' => $repository['ok'],
            'repository' => $repository['repository'] ?? null,
            'error' => $repository['ok'] ? null : $repository['message'],
        ]);
    }

    /**
     * Ejecuta el flujo completo y fijo: backup, Update from Remote, limpieza de
     * caché y migraciones. La entrada del solicitante solo se usa para auditoría.
     *
     * @return array<string, mixed>
     */
    public function deploy(string $source, ?int $actorId = null, ?string $ip = null): array
    {
        $startedAt = microtime(true);
        $settings = $this->settings();

        if (! $settings['configured']) {
            return $this->finish([
                'ok' => false,
                'status' => 'not_configured',
                'message' => 'El despliegue no está configurado. Completa las variables de cPanel en .env.',
                'steps' => [],
                'missing' => $settings['missing'],
            ], $source, $actorId, $ip, $startedAt);
        }

        $lock = $this->acquireLock();

        if (! $lock['ok']) {
            return $this->finish([
                'ok' => false,
                'status' => 'failed',
                'message' => $lock['message'],
                'steps' => [],
            ], $source, $actorId, $ip, $startedAt);
        }

        if (! $lock['acquired']) {
            return $this->finish([
                'ok' => false,
                'status' => 'busy',
                'message' => 'Ya hay un despliegue en curso. Espera a que termine antes de reintentar.',
                'steps' => [],
            ], $source, $actorId, $ip, $startedAt);
        }

        try {
            $steps = [];
            $before = $this->repositoryStatus(true);
            $steps[] = $this->step(
                'repository_check',
                $before['ok'],
                $before['message'],
            );

            if (! $before['ok']) {
                return $this->finish([
                    'ok' => false,
                    'status' => 'failed',
                    'message' => 'No se pudo verificar el repositorio administrado por cPanel.',
                    'steps' => $steps,
                ], $source, $actorId, $ip, $startedAt);
            }

            $backup = $this->backups->createMigrationPackage();
            $steps[] = $this->step(
                'backup',
                (bool) ($backup['ok'] ?? false),
                (string) ($backup['message'] ?? 'Sin información del backup.'),
            );

            if (! ($backup['ok'] ?? false)) {
                return $this->finish([
                    'ok' => false,
                    'status' => 'failed',
                    'message' => 'El código no se actualizó porque falló el backup automático.',
                    'steps' => $steps,
                ], $source, $actorId, $ip, $startedAt);
            }

            $pull = $this->cpanelCall('VersionControl', 'update', [
                'repository_root' => $settings['repository_root'],
                'branch' => $settings['branch'],
                'source_repository' => json_encode(['remote_name' => 'origin'], JSON_THROW_ON_ERROR),
            ]);
            $steps[] = $this->step('update_from_remote', $pull['ok'], $pull['message']);

            if (! $pull['ok']) {
                return $this->finish([
                    'ok' => false,
                    'status' => 'failed',
                    'message' => 'cPanel no pudo completar Update from Remote.',
                    'steps' => $steps,
                    'backup' => $this->backupSummary($backup),
                    'before' => $before['repository'],
                ], $source, $actorId, $ip, $startedAt);
            }

            try {
                Cache::forget(self::STATUS_CACHE_KEY);
            } catch (Throwable) {
                // optimize:clear limpia el estado después; esto solo evita
                // mostrar durante unos segundos el commit previo.
            }

            $cache = $this->maintenance->clearOptimizationCache();
            $steps[] = $this->step(
                'optimize_clear',
                (bool) ($cache['ok'] ?? false),
                (string) ($cache['output'] ?? 'Sin salida de optimize:clear.'),
            );

            $migrations = $this->maintenance->runMigrations();
            $steps[] = $this->step(
                'migrate',
                (bool) ($migrations['ok'] ?? false),
                (string) ($migrations['output'] ?? 'Sin salida de migrate --force.'),
            );

            $after = $this->repositoryStatus(true);
            $steps[] = $this->step(
                'deployment_check',
                $after['ok'],
                $after['message'],
            );

            $ok = (bool) ($cache['ok'] ?? false)
                && (bool) ($migrations['ok'] ?? false)
                && $after['ok'];

            return $this->finish([
                'ok' => $ok,
                'status' => $ok ? 'completed' : 'failed',
                'message' => $ok
                    ? 'Producción quedó actualizada, migrada y con la caché limpia.'
                    : 'El código se actualizó, pero uno o más pasos posteriores fallaron. Revisa el detalle.',
                'steps' => $steps,
                'backup' => $this->backupSummary($backup),
                'before' => $before['repository'],
                'after' => $after['repository'] ?? null,
                'version' => $this->versionFromDisk(),
            ], $source, $actorId, $ip, $startedAt);
        } catch (Throwable $exception) {
            return $this->finish([
                'ok' => false,
                'status' => 'failed',
                'message' => 'Error inesperado durante el despliegue: '.$exception->getMessage(),
                'steps' => $steps ?? [],
            ], $source, $actorId, $ip, $startedAt);
        } finally {
            $this->releaseLock($lock['handle']);
        }
    }

    /**
     * Usa flock en storage para que optimize:clear no borre el bloqueo activo.
     *
     * @return array{ok: bool, acquired: bool, message: string, handle: resource|null}
     */
    private function acquireLock(): array
    {
        try {
            $directory = storage_path('app/private');
            File::ensureDirectoryExists($directory);
            $handle = fopen($directory.DIRECTORY_SEPARATOR.'finance-deployment.lock', 'c+');

            if ($handle === false) {
                return [
                    'ok' => false,
                    'acquired' => false,
                    'message' => 'No se pudo abrir el archivo de bloqueo del despliegue.',
                    'handle' => null,
                ];
            }

            $acquired = flock($handle, LOCK_EX | LOCK_NB);

            if (! $acquired) {
                fclose($handle);
            }

            return [
                'ok' => true,
                'acquired' => $acquired,
                'message' => $acquired ? 'Bloqueo adquirido.' : 'Ya existe un despliegue en curso.',
                'handle' => $acquired ? $handle : null,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'acquired' => false,
                'message' => 'No se pudo preparar el bloqueo de despliegue: '.$exception->getMessage(),
                'handle' => null,
            ];
        }
    }

    /**
     * @param  resource|null  $handle
     */
    private function releaseLock($handle): void
    {
        if (! is_resource($handle)) {
            return;
        }

        try {
            flock($handle, LOCK_UN);
            fclose($handle);
        } catch (Throwable) {
            // PHP libera el bloqueo al finalizar el request aun si falla el cierre.
        }
    }

    /**
     * @return array{ok: bool, message: string, repository?: array<string, mixed>}
     */
    private function repositoryStatus(bool $refresh): array
    {
        if (! $refresh) {
            try {
                $cached = Cache::get(self::STATUS_CACHE_KEY);
                if (is_array($cached)) {
                    return $cached;
                }
            } catch (Throwable) {
                // El estado remoto sigue disponible aunque el caché no lo esté.
            }
        }

        $settings = $this->settings();
        $response = $this->cpanelCall('VersionControl', 'retrieve', [
            'fields' => 'name,type,branch,last_update,repository_root,source_repository',
        ]);

        if (! $response['ok']) {
            return [
                'ok' => false,
                'message' => $response['message'],
            ];
        }

        $repositories = is_array($response['data']) ? $response['data'] : [];
        $repository = collect($repositories)->first(
            fn (mixed $candidate): bool => is_array($candidate)
                && (string) ($candidate['repository_root'] ?? '') === $settings['repository_root']
        );

        if (! is_array($repository)) {
            return [
                'ok' => false,
                'message' => 'cPanel respondió correctamente, pero no encontró el repository_root configurado.',
            ];
        }

        $result = [
            'ok' => true,
            'message' => 'Repositorio verificado en cPanel.',
            'repository' => $this->repositorySummary($repository),
        ];

        try {
            Cache::put(self::STATUS_CACHE_KEY, $result, now()->addSeconds(30));
        } catch (Throwable) {
            // El caché es una optimización; nunca debe impedir el despliegue.
        }

        return $result;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array{ok: bool, message: string, data: mixed}
     */
    private function cpanelCall(string $module, string $function, array $query): array
    {
        $settings = $this->settings();
        $url = $settings['cpanel_url'].'/execute/'.$module.'/'.$function;

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => 'cpanel '.$settings['cpanel_username'].':'.$settings['cpanel_api_token'],
                    'User-Agent' => 'Finanzas-Deployment/'.$this->versionFromDisk(),
                ])
                ->connectTimeout((int) config('finance.deployment.connect_timeout', 10))
                ->timeout($function === 'update'
                    ? (int) config('finance.deployment.timeout', 120)
                    : min((int) config('finance.deployment.timeout', 120), 15))
                ->get($url, $query);

            return $this->parseCpanelResponse($response);
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'No se pudo conectar con la API de cPanel: '.$exception->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, data: mixed}
     */
    private function parseCpanelResponse(Response $response): array
    {
        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => 'cPanel respondió con HTTP '.$response->status().'.',
                'data' => null,
            ];
        }

        $payload = $response->json();
        $result = is_array($payload) && is_array($payload['result'] ?? null)
            ? $payload['result']
            : null;

        if (! is_array($result)) {
            return [
                'ok' => false,
                'message' => 'cPanel devolvió una respuesta JSON sin el resultado esperado.',
                'data' => null,
            ];
        }

        $ok = (int) ($result['status'] ?? 0) === 1;
        $messages = $this->apiMessages($result[$ok ? 'messages' : 'errors'] ?? null);

        return [
            'ok' => $ok,
            'message' => $messages !== ''
                ? $messages
                : ($ok ? 'Operación completada por cPanel.' : 'cPanel rechazó la operación.'),
            'data' => $result['data'] ?? null,
        ];
    }

    private function apiMessages(mixed $messages): string
    {
        if (is_string($messages)) {
            return mb_substr(trim($messages), 0, 1000);
        }

        if (is_array($messages)) {
            return mb_substr(collect($messages)
                ->flatten()
                ->filter(fn (mixed $message): bool => is_scalar($message))
                ->map(fn (mixed $message): string => trim((string) $message))
                ->filter()
                ->implode(' '), 0, 1000);
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $url = rtrim(trim((string) config('finance.deployment.cpanel_url')), '/');
        $username = trim((string) config('finance.deployment.cpanel_username'));
        $cpanelToken = trim((string) config('finance.deployment.cpanel_api_token'));
        $repositoryRoot = trim((string) config('finance.deployment.repository_root'));
        $branch = trim((string) config('finance.deployment.branch', 'main'));
        $agentToken = trim((string) config('finance.deployment.agent_api_token'));

        $missing = [];

        if (! $this->validCpanelUrl($url)) {
            $missing[] = 'FINANCE_CPANEL_URL';
        }
        if ($username === '') {
            $missing[] = 'FINANCE_CPANEL_USERNAME';
        }
        if (mb_strlen($cpanelToken) < 20) {
            $missing[] = 'FINANCE_CPANEL_API_TOKEN';
        }
        if (! str_starts_with($repositoryRoot, '/')) {
            $missing[] = 'FINANCE_CPANEL_REPOSITORY_ROOT';
        }
        if ($branch === '' || preg_match('/^[A-Za-z0-9._\/-]+$/', $branch) !== 1) {
            $missing[] = 'FINANCE_CPANEL_BRANCH';
        }

        $apiMissing = mb_strlen($agentToken) >= 32
            ? []
            : ['FINANCE_DEPLOY_API_TOKEN'];

        return [
            'configured' => $missing === [],
            'api_configured' => $apiMissing === [],
            'missing' => $missing,
            'api_missing' => $apiMissing,
            'cpanel_url' => $url,
            'cpanel_username' => $username,
            'cpanel_api_token' => $cpanelToken,
            'repository_root' => $repositoryRoot,
            'branch' => $branch,
            'agent_api_token' => $agentToken,
        ];
    }

    private function validCpanelUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_HOST) !== null
            && (int) parse_url($url, PHP_URL_PORT) === 2083
            && trim((string) parse_url($url, PHP_URL_PATH), '/') === ''
            && parse_url($url, PHP_URL_USER) === null
            && parse_url($url, PHP_URL_PASS) === null
            && parse_url($url, PHP_URL_QUERY) === null
            && parse_url($url, PHP_URL_FRAGMENT) === null;
    }

    /**
     * @param  array<string, mixed>  $repository
     * @return array<string, mixed>
     */
    private function repositorySummary(array $repository): array
    {
        $lastUpdate = is_array($repository['last_update'] ?? null)
            ? $repository['last_update']
            : [];

        return [
            'name' => (string) ($repository['name'] ?? ''),
            'repository_root' => (string) ($repository['repository_root'] ?? ''),
            'branch' => (string) ($repository['branch'] ?? ''),
            'remote_url' => (string) data_get($repository, 'source_repository.url', ''),
            'commit' => (string) ($lastUpdate['identifier'] ?? ''),
            'message' => (string) ($lastUpdate['message'] ?? ''),
            'author' => (string) ($lastUpdate['author'] ?? ''),
            'date' => isset($lastUpdate['date']) && is_numeric($lastUpdate['date'])
                ? date(DATE_ATOM, (int) $lastUpdate['date'])
                : (string) ($lastUpdate['date'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $backup
     * @return array<string, mixed>
     */
    private function backupSummary(array $backup): array
    {
        return [
            'name' => (string) ($backup['name'] ?? ''),
            'type' => (string) ($backup['type'] ?? 'migration'),
            'size' => isset($backup['size']) ? (int) $backup['size'] : null,
        ];
    }

    /**
     * @return array{name: string, ok: bool, detail: string}
     */
    private function step(string $name, bool $ok, string $detail): array
    {
        return [
            'name' => $name,
            'ok' => $ok,
            'detail' => mb_substr(trim($detail), 0, 2000),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function finish(
        array $result,
        string $source,
        ?int $actorId,
        ?string $ip,
        float $startedAt,
    ): array {
        $result['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        Log::log(($result['ok'] ?? false) ? 'notice' : 'warning', 'Finance deployment finished.', [
            'source' => $source,
            'actor_id' => $actorId,
            'ip' => $ip,
            'ok' => (bool) ($result['ok'] ?? false),
            'status' => (string) ($result['status'] ?? 'unknown'),
            'before_commit' => (string) data_get($result, 'before.commit', ''),
            'after_commit' => (string) data_get($result, 'after.commit', ''),
            'version' => (string) ($result['version'] ?? ''),
            'duration_ms' => $result['duration_ms'],
        ]);

        return $result;
    }

    private function versionFromDisk(): string
    {
        try {
            $financeConfig = require config_path('finance.php');

            return (string) ($financeConfig['version'] ?? config('finance.version', 'desconocida'));
        } catch (Throwable) {
            return (string) config('finance.version', 'desconocida');
        }
    }
}
