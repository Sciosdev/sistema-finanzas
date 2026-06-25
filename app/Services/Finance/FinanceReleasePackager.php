<?php

namespace App\Services\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Genera, desde local, un paquete ZIP listo para subir a HostGator compartido
 * (sin terminal). A diferencia de los backups, este paquete SÍ incluye
 * `vendor/` y `public/build/` porque el servidor no ejecuta `composer install`
 * ni `npm run build`.
 *
 * No incluye secretos: el `.env` real, llaves, tokens, backups ni dumps quedan
 * fuera del ZIP.
 */
class FinanceReleasePackager
{
    /**
     * Directorios que se copian completos al paquete.
     *
     * @var list<string>
     */
    private const INCLUDED_DIRECTORIES = [
        'app',
        'bootstrap',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'vendor',
    ];

    /**
     * Archivos sueltos en la raíz que se incluyen si existen.
     *
     * @var list<string>
     */
    private const INCLUDED_FILES = [
        'composer.json',
        'composer.lock',
        'artisan',
        '.env.example',
    ];

    public function __construct(private readonly string $basePath)
    {
    }

    /**
     * Valida que existan los artefactos imprescindibles para un deploy.
     *
     * @return list<string> Lista de problemas (vacía si todo está bien).
     */
    public function validate(): array
    {
        $problems = [];

        if (! is_file($this->path('vendor/autoload.php'))) {
            $problems[] = 'Falta vendor/autoload.php. Ejecuta "composer install --no-dev -o" en local; HostGator no ejecuta composer install.';
        }

        if (! is_file($this->path('public/build/manifest.json'))) {
            $problems[] = 'Falta public/build/manifest.json. Ejecuta "npm run build" en local; public/build no va por Git.';
        }

        if (! is_file($this->path('.env.example'))) {
            $problems[] = 'Falta .env.example en la raíz del proyecto.';
        }

        if (! is_file($this->path('composer.lock'))) {
            $problems[] = 'Falta composer.lock en la raíz del proyecto.';
        }

        return $problems;
    }

    /**
     * Construye el ZIP de release.
     *
     * @return array{ok: bool, message: string, name?: string, path?: string, size?: int, files?: int}
     */
    public function build(string $outputDirectory): array
    {
        $problems = $this->validate();
        if ($problems !== []) {
            return [
                'ok' => false,
                'message' => 'No se puede generar el paquete: ' . implode(' ', $problems),
            ];
        }

        $filename = 'release-finanzas-hostgator-' . Carbon::now()->format('Ymd-His') . '.zip';
        $path = rtrim($outputDirectory, "/\\") . DIRECTORY_SEPARATOR . $filename;

        try {
            File::ensureDirectoryExists($outputDirectory);

            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo abrir el archivo ZIP de salida.');
            }

            $fileCount = 0;

            foreach (self::INCLUDED_DIRECTORIES as $directory) {
                $fileCount += $this->addDirectory($zip, $directory);
            }

            foreach (self::INCLUDED_FILES as $file) {
                $absolute = $this->path($file);
                if (is_file($absolute)) {
                    $zip->addFile($absolute, $file);
                    $fileCount++;
                }
            }

            $zip->addFromString('DEPLOY_HOSTGATOR.md', $this->deployGuide());
            $fileCount++;

            $zip->close();

            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('No se generó un ZIP válido.');
            }

            return [
                'ok' => true,
                'message' => 'Paquete de producción generado.',
                'name' => $filename,
                'path' => $path,
                'size' => (int) filesize($path),
                'files' => $fileCount,
            ];
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                @unlink($path);
            }

            return [
                'ok' => false,
                'message' => 'No se pudo generar el paquete: ' . $exception->getMessage(),
            ];
        }
    }

    private function addDirectory(ZipArchive $zip, string $directory): int
    {
        $absolute = $this->path($directory);

        if (! is_dir($absolute)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->isLink()) {
                continue;
            }

            $relative = $directory . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($absolute) + 1));

            if ($this->shouldExclude($relative)) {
                continue;
            }

            $zip->addFile($file->getPathname(), $relative);
            $count++;
        }

        return $count;
    }

    /**
     * Decide si una ruta relativa (con separadores "/") debe quedar fuera del ZIP.
     */
    private function shouldExclude(string $relative): bool
    {
        // Nunca incluir el .env real ni variantes con secretos (.env.example se
        // agrega aparte como archivo explícito).
        if ($relative === '.env' || (str_starts_with($relative, '.env.') && $relative !== '.env.example')) {
            return true;
        }

        $prefixes = [
            '.git/',
            'node_modules/',
            'storage/',
            'public/storage/',
            'public/hot',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        // Cache compilada de bootstrap: enviar la carpeta pero no archivos compilados.
        if (str_starts_with($relative, 'bootstrap/cache/') && ! str_ends_with($relative, '.gitignore')) {
            return true;
        }

        // Bases SQLite locales y dumps dentro de database/.
        if (str_starts_with($relative, 'database/') && str_ends_with($relative, '.sqlite')) {
            return true;
        }

        // Backups, dumps y temporales que pudieran aparecer en cualquier ruta.
        if (str_contains($relative, 'finance-backups/')) {
            return true;
        }

        return str_ends_with($relative, '.sql')
            || str_ends_with($relative, '.tmp')
            || str_ends_with($relative, '.temp');
    }

    private function path(string $relative): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function deployGuide(): string
    {
        return <<<'MD'
# Deploy a HostGator (hosting compartido, sin terminal)

Este ZIP es un paquete de producción **completo**: incluye `vendor/` y
`public/build/` porque HostGator no ejecuta `composer install` ni `npm run build`.

## Antes de subir

1. **Haz un backup** desde la app: **Seguridad → Backup completo** (o Backup de BD).
   No subas nada hasta tener el respaldo descargado.
2. Verifica que el ZIP se generó en local con `php artisan finance:build-release`.

## Subir el paquete

1. Entra a **cPanel → Administrador de archivos** y ve a la carpeta del sitio.
2. Sube este ZIP y usa **Extraer** para descomprimirlo en su lugar.
3. **NO sobrescribas el `.env` de producción.** El ZIP solo trae `.env.example`;
   tu `.env` real del servidor debe permanecer intacto.

## Permisos y carpetas de runtime

4. Asegúrate de que existan y sean escribibles (cPanel suele dar 755/775):
   - `storage/` y todas sus subcarpetas (`storage/framework/{cache,sessions,views}`, `storage/logs`).
   - `bootstrap/cache/`.
   El ZIP **no** incluye el contenido de `storage/` ni la caché compilada de
   `bootstrap/cache/`; si esas carpetas no existen aún, créalas.

## Migraciones y caché (vía Cron, no hay terminal)

5. Si hay **migraciones nuevas**, crea un Cron de una sola ejecución:
   `php /home/USUARIO/ruta-al-proyecto/artisan migrate --force`
6. Limpia caché de configuración/rutas/vistas con otro Cron de una ejecución:
   `php /home/USUARIO/ruta-al-proyecto/artisan optimize:clear`
   Elimina ambos Cron una vez ejecutados.

## Verificación post-deploy

7. Abre **https://finanzas.xaanal.com/finanzas/diagnostico** (como dueño) y revisa
   que todos los checks estén en verde, en especial `vendor`, `Manifest de assets`,
   variables de BD y permisos de `storage`/`bootstrap/cache`.
8. Si el sitio diera error 500 y el diagnóstico no cargara, usa el triage:
   **https://finanzas.xaanal.com/_health/triage?key=TU_TOKEN**
   (requiere `FINANCE_HEALTH_TOKEN` en el `.env` de producción).
9. Confirma que **`public/build/manifest.json`** existe en el servidor (debe haber
   venido en este ZIP). Si falta, los assets no cargarán.

## Qué NO trae este ZIP

`.env` real, `.git/`, `node_modules/`, contenido de `storage/`, caché de
`bootstrap/cache/`, backups, dumps `.sql` ni bases `.sqlite`.
MD;
    }
}
