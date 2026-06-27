<?php

namespace App\Services\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Montaje seguro de `public/build` para hosting sin terminal (HostGator/cPanel).
 *
 * Recibe un .zip generado localmente con `npm run build` y lo monta como
 * `public/build`. NUNCA compila, NUNCA ejecuta npm ni comandos de sistema, y el
 * ÚNICO comando Artisan que dispara es `optimize:clear` (limpiar caché). No toca
 * la base de datos ni ejecuta migraciones.
 *
 * Destino fijo: public_path('build'). El request no puede definir rutas ni
 * carpeta destino. El ZIP se valida (rutas, extensiones peligrosas, manifest)
 * antes de montarse, se extrae primero a una carpeta de staging y se respalda el
 * build anterior para poder hacer rollback.
 */
class FinanceBuildDeployService
{
    /**
     * Nombres relativos donde Vite puede dejar el manifest.
     *
     * @var array<int, string>
     */
    private const MANIFEST_CANDIDATES = ['manifest.json', '.vite/manifest.json'];

    /**
     * Estado de solo lectura para mostrar en Seguridad. No modifica nada.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $buildPath = $this->buildPath();
        $manifest = $this->currentManifestPath();

        return [
            'zip_supported' => class_exists(ZipArchive::class),
            'exists' => $manifest !== null,
            'build_path' => $buildPath,
            'manifest_updated_at' => $manifest ? Carbon::createFromTimestamp(filemtime($manifest)) : null,
            'size' => $this->directorySize($buildPath),
            'backups' => $this->listBackups(),
        ];
    }

    /**
     * Valida y monta el build contenido en el .zip indicado.
     *
     * @return array{ok: bool, message: string, backup?: ?string, action?: string, output?: string}
     */
    public function deployFromZip(string $zipPath): array
    {
        $staging = null;

        try {
            if (! class_exists(ZipArchive::class)) {
                return $this->fail('ZipArchive no está disponible en este servidor. No se puede montar el build.');
            }

            if (! is_file($zipPath)) {
                return $this->fail('No se encontró el archivo subido.');
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return $this->fail('El archivo no es un .zip válido.');
            }

            try {
                [$names, $prefix] = $this->validateAndPlan($zip);
                $staging = $this->extractToStaging($zip, $names, $prefix);
            } finally {
                $zip->close();
            }

            if (! $this->stagingHasManifest($staging)) {
                File::deleteDirectory($staging);

                return $this->fail('El build no contiene manifest.json después de extraerlo.');
            }

            // Respaldar el build actual ANTES de montar el nuevo (red de rollback).
            $backupPath = null;
            $buildPath = $this->buildPath();
            if (is_dir($buildPath)) {
                $backupPath = $this->backupsDir() . '/' . $this->timestampName('build');
                $this->moveDir($buildPath, $backupPath);
            }

            try {
                $this->moveDir($staging, $buildPath);
                $staging = null;

                if (! $this->currentManifestPath()) {
                    throw new RuntimeException('El build montado no tiene manifest.json.');
                }
            } catch (Throwable $exception) {
                // No dejar public/build vacío: restaurar el respaldo anterior.
                File::deleteDirectory($buildPath);
                if ($backupPath && is_dir($backupPath)) {
                    $this->moveDir($backupPath, $buildPath);
                }

                throw $exception;
            }

            $optimize = $this->runOptimizeClear();

            return [
                'ok' => true,
                'message' => 'Build montado correctamente en public/build.',
                'backup' => $backupPath ? basename($backupPath) : null,
                'action' => 'build:deploy + optimize:clear',
                'output' => $optimize['output'],
            ];
        } catch (Throwable $exception) {
            if ($staging && is_dir($staging)) {
                File::deleteDirectory($staging);
            }

            return $this->fail($exception->getMessage());
        }
    }

    /**
     * Restaura un respaldo previo de build (el indicado por nombre o el más reciente).
     *
     * @return array{ok: bool, message: string, action?: string, output?: string, restored?: string}
     */
    public function rollback(?string $backupName = null): array
    {
        try {
            $backups = $this->listBackups();

            if (empty($backups)) {
                return $this->fail('No hay respaldos de build disponibles para restaurar.');
            }

            $selected = null;
            if ($backupName !== null && $backupName !== '') {
                // Nunca aceptar rutas desde el request: solo el nombre de carpeta.
                $clean = basename($backupName);
                foreach ($backups as $backup) {
                    if ($backup['name'] === $clean) {
                        $selected = $backup;
                        break;
                    }
                }

                if ($selected === null) {
                    return $this->fail('No se encontró el respaldo indicado.');
                }
            } else {
                $selected = $backups[0];
            }

            $backupPath = $this->backupsDir() . '/' . $selected['name'];
            if (! is_dir($backupPath)) {
                return $this->fail('El respaldo seleccionado ya no existe.');
            }

            $buildPath = $this->buildPath();
            $preserved = null;
            if (is_dir($buildPath)) {
                // Mover el build actual a un respaldo temporal antes de restaurar.
                $preserved = $this->backupsDir() . '/' . $this->timestampName('build-prev');
                $this->moveDir($buildPath, $preserved);
            }

            try {
                File::copyDirectory($backupPath, $buildPath);

                if (! $this->currentManifestPath()) {
                    throw new RuntimeException('El respaldo restaurado no tiene manifest.json.');
                }
            } catch (Throwable $exception) {
                File::deleteDirectory($buildPath);
                if ($preserved && is_dir($preserved)) {
                    $this->moveDir($preserved, $buildPath);
                }

                return $this->fail('No se pudo restaurar: ' . $exception->getMessage());
            }

            $optimize = $this->runOptimizeClear();

            return [
                'ok' => true,
                'message' => 'Build anterior restaurado: ' . $selected['name'],
                'action' => 'build:rollback + optimize:clear',
                'output' => $optimize['output'],
                'restored' => $selected['name'],
            ];
        } catch (Throwable $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    /**
     * Borra respaldos antiguos dejando al menos los $keep más recientes.
     * Nunca toca public/build.
     *
     * @return array{ok: bool, message: string, deleted: int, kept: int}
     */
    public function cleanupBackups(int $keep = 1): array
    {
        $keep = max(1, $keep);
        $backups = $this->listBackups();

        if (count($backups) <= $keep) {
            return [
                'ok' => true,
                'message' => 'No hay respaldos antiguos que limpiar.',
                'deleted' => 0,
                'kept' => count($backups),
            ];
        }

        $deleted = 0;
        foreach (array_slice($backups, $keep) as $backup) {
            $path = $this->backupsDir() . '/' . $backup['name'];
            if (is_dir($path) && File::deleteDirectory($path)) {
                $deleted++;
            }
        }

        return [
            'ok' => true,
            'message' => "Se eliminaron {$deleted} respaldo(s) antiguo(s). Se conservó el más reciente.",
            'deleted' => $deleted,
            'kept' => $keep,
        ];
    }

    /**
     * Lista los respaldos de build disponibles, del más reciente al más antiguo.
     *
     * @return array<int, array{name: string, size: int, created_at: Carbon}>
     */
    public function listBackups(): array
    {
        $dir = $this->backupsDir();

        if (! is_dir($dir)) {
            return [];
        }

        return collect(File::directories($dir))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size' => $this->directorySize($path),
                'created_at' => Carbon::createFromTimestamp(filemtime($path)),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /**
     * Recorre el ZIP rechazando rutas y archivos peligrosos, y detecta el formato
     * (manifest en raíz o bajo build/). Devuelve los nombres seguros y el prefijo.
     *
     * @return array{0: array<int, string>, 1: string}
     */
    private function validateAndPlan(ZipArchive $zip): array
    {
        $names = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if ($name === '') {
                continue;
            }

            $normalized = str_replace('\\', '/', $name);

            // 1. Rechazar traversal (../ o ..\).
            if (str_contains($name, '..')) {
                throw new RuntimeException('El ZIP contiene rutas no permitidas con ".." : ' . $name);
            }

            // 1b. Rechazar rutas absolutas (unix o Windows con letra de unidad).
            if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:/', $normalized) === 1) {
                throw new RuntimeException('El ZIP contiene rutas absolutas no permitidas: ' . $name);
            }

            $base = strtolower(basename($normalized));
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));

            // 2. Rechazar archivos peligrosos.
            if (in_array($ext, ['php', 'phtml', 'phar'], true)
                || $base === '.htaccess'
                || str_starts_with($base, '.env')) {
                throw new RuntimeException('El ZIP contiene un archivo no permitido: ' . $name);
            }

            // Saltar entradas de directorio y basura común de los empaquetadores.
            if (str_ends_with($normalized, '/')) {
                continue;
            }
            if (str_contains($normalized, '__MACOSX/') || $base === '.ds_store') {
                continue;
            }

            $names[] = $normalized;
        }

        $prefix = $this->detectPrefix($names);
        if ($prefix === null) {
            throw new RuntimeException('El ZIP no contiene manifest.json.');
        }

        return [$names, $prefix];
    }

    /**
     * Detecta si el manifest viene en la raíz ('') o bajo 'build/'. Si no hay
     * manifest en ninguno de los dos formatos, devuelve null.
     *
     * @param array<int, string> $names
     */
    private function detectPrefix(array $names): ?string
    {
        $set = array_flip($names);

        foreach (['', 'build/'] as $prefix) {
            foreach (self::MANIFEST_CANDIDATES as $candidate) {
                if (isset($set[$prefix . $candidate])) {
                    return $prefix;
                }
            }
        }

        return null;
    }

    /**
     * Extrae los archivos seguros del ZIP a una carpeta de staging, quitando el
     * prefijo build/ si aplica (formato B) para que quede a nivel de public/build.
     *
     * @param array<int, string> $names
     */
    private function extractToStaging(ZipArchive $zip, array $names, string $prefix): string
    {
        $staging = $this->baseDir() . '/staging-' . $this->timestampName('');
        File::ensureDirectoryExists($staging);

        $prefixLength = strlen($prefix);

        foreach ($names as $name) {
            if ($prefix !== '' && ! str_starts_with($name, $prefix)) {
                continue;
            }

            $relative = $prefix !== '' ? substr($name, $prefixLength) : $name;
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }

            $contents = $zip->getFromName($name);
            if ($contents === false) {
                throw new RuntimeException('No se pudo leer un archivo del ZIP: ' . $name);
            }

            $target = $staging . '/' . $relative;
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $contents);
        }

        return $staging;
    }

    /**
     * Ejecuta el ÚNICO comando Artisan permitido: optimize:clear.
     *
     * @return array{ok: bool, output: string}
     */
    private function runOptimizeClear(): array
    {
        try {
            $exitCode = Artisan::call('optimize:clear');

            return [
                'ok' => $exitCode === 0,
                'output' => trim(Artisan::output()) ?: 'Sin salida del comando.',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'output' => 'Error: ' . $exception->getMessage(),
            ];
        }
    }

    private function currentManifestPath(): ?string
    {
        foreach (self::MANIFEST_CANDIDATES as $candidate) {
            $path = $this->buildPath() . '/' . $candidate;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function stagingHasManifest(string $dir): bool
    {
        foreach (self::MANIFEST_CANDIDATES as $candidate) {
            if (is_file($dir . '/' . $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function directorySize(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $total = 0;
        foreach (File::allFiles($dir) as $file) {
            $total += $file->getSize();
        }

        return $total;
    }

    /**
     * Mueve un directorio de forma atómica (rename) con respaldo a copiar+borrar
     * si el rename falla (por ejemplo entre volúmenes distintos).
     */
    private function moveDir(string $from, string $to): void
    {
        File::ensureDirectoryExists(dirname($to));

        if (@rename($from, $to)) {
            return;
        }

        File::copyDirectory($from, $to);
        File::deleteDirectory($from);
    }

    private function timestampName(string $prefix): string
    {
        $stamp = now()->format('Ymd_His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        return $prefix !== '' ? $prefix . '-' . $stamp : $stamp;
    }

    /**
     * @return array{ok: false, message: string}
     */
    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    /**
     * Destino fijo del build. Sobreescribible en pruebas para no tocar el real.
     */
    protected function buildPath(): string
    {
        return public_path('build');
    }

    /**
     * Carpeta base de trabajo (staging + backups). Sobreescribible en pruebas.
     */
    protected function baseDir(): string
    {
        return storage_path('app/private/build-deploys');
    }

    protected function backupsDir(): string
    {
        return $this->baseDir() . '/backups';
    }
}
