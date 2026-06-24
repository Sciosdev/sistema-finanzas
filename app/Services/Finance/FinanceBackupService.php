<?php

namespace App\Services\Finance;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class FinanceBackupService
{
    private const BACKUP_ROOT = 'finance-backups';

    public function __construct(private readonly ?string $mysqldumpPath = null)
    {
    }

    public function createDatabaseBackup(): array
    {
        $filename = 'finanzas-db-' . now()->format('Ymd-His') . '.sql';
        $directory = $this->databaseBackupDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        try {
            File::ensureDirectoryExists($directory);
            $connection = $this->databaseConnection();

            if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
                throw new RuntimeException('El backup SQL automatico solo esta disponible para MySQL/MariaDB.');
            }

            $this->runMysqlDump($connection, $path);

            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('mysqldump no genero un archivo valido.');
            }

            return $this->successPayload('database', $filename, $path, 'Backup de BD creado.');
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                @unlink($path);
            }

            return [
                'ok' => false,
                'message' => 'No se pudo crear el backup de BD: ' . $exception->getMessage(),
            ];
        }
    }

    public function createFullBackup(bool $includeEnv = false): array
    {
        $filename = 'finanzas-full-' . now()->format('Ymd-His') . '.zip';
        $directory = $this->fullBackupDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        try {
            File::ensureDirectoryExists($directory);

            $databaseBackup = $this->createDatabaseBackup();
            if (! ($databaseBackup['ok'] ?? false)) {
                return $databaseBackup;
            }

            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo abrir el archivo zip.');
            }

            $zip->addFile($databaseBackup['absolute_path'], 'database-backup/' . $databaseBackup['name']);
            $this->addProjectFilesToZip($zip, $includeEnv);
            $zip->close();

            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('No se genero un zip valido.');
            }

            return $this->successPayload('full', $filename, $path, 'Backup completo creado.');
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                @unlink($path);
            }

            return [
                'ok' => false,
                'message' => 'No se pudo crear el backup completo: ' . $exception->getMessage(),
            ];
        }
    }

    public function createMigrationPackage(): array
    {
        $timestamp = now()->format('Ymd-His');
        $filename = 'finanzas-migration-' . $timestamp . '.zip';
        $directory = $this->migrationBackupDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $sqlPath = $directory . DIRECTORY_SEPARATOR . 'sistema-finanzas-' . $timestamp . '.sql';

        try {
            File::ensureDirectoryExists($directory);

            $databaseExport = $this->createPortableDatabaseExport($sqlPath);

            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo abrir el archivo zip.');
            }

            $manifest = [
                'type' => 'sistema-finanzas-migration',
                'generated_at' => now()->toIso8601String(),
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
                'environment' => app()->environment(),
                'database' => [
                    'driver' => $databaseExport['driver'],
                    'name' => $databaseExport['database'],
                    'tables' => $databaseExport['tables'],
                    'rows' => $databaseExport['rows'],
                ],
                'restore' => [
                    'target' => 'local',
                    'method' => 'Importar database/sistema-finanzas.sql en una base local vacia.',
                ],
            ];

            $zip->addFile($sqlPath, 'database/sistema-finanzas.sql');
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
            $zip->addFromString('RESTORE_LOCAL.md', $this->migrationRestoreGuide($databaseExport));
            $zip->close();

            @unlink($sqlPath);

            if (! is_file($path) || filesize($path) === 0) {
                throw new RuntimeException('No se genero un zip valido.');
            }

            return $this->successPayload('migration', $filename, $path, 'Paquete de migracion creado.');
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                @unlink($path);
            }

            if (is_file($sqlPath)) {
                @unlink($sqlPath);
            }

            return [
                'ok' => false,
                'message' => 'No se pudo crear el paquete de migracion: ' . $exception->getMessage(),
            ];
        }
    }

    public function createDatabaseBackupExternal(): array
    {
        $backup = $this->createDatabaseBackup();

        if (! ($backup['ok'] ?? false)) {
            return $backup;
        }

        return $this->copyBackupPayloadToExternal($backup, 'Backup de BD copiado a ruta externa.');
    }

    public function createFullBackupExternal(bool $includeEnv = false): array
    {
        $backup = $this->createFullBackup($includeEnv);

        if (! ($backup['ok'] ?? false)) {
            return $backup;
        }

        return $this->copyBackupPayloadToExternal($backup, 'Backup completo copiado a ruta externa.');
    }

    public function copyLatestBackupToExternal(): array
    {
        try {
            $latest = collect($this->listBackups()['database'] ?? [])
                ->merge($this->listBackups()['full'] ?? [])
                ->sortByDesc('created_at')
                ->first();

            if (! $latest) {
                throw new RuntimeException('No hay backups locales para copiar.');
            }

            $path = $this->downloadPath($latest['type'], $latest['name']);

            return $this->copyBackupPayloadToExternal([
                'ok' => true,
                'type' => $latest['type'],
                'name' => $latest['name'],
                'absolute_path' => $path,
                'size' => filesize($path),
                'created_at' => $latest['created_at'],
            ], 'Último backup copiado a ruta externa.');
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'No se pudo copiar el backup externo: ' . $exception->getMessage(),
            ];
        }
    }

    public function listBackups(): array
    {
        return [
            'database' => $this->filesForType('database'),
            'full' => $this->filesForType('full'),
            'migration' => $this->filesForType('migration'),
        ];
    }

    public function downloadPath(string $type, string $filename): string
    {
        $directory = match ($type) {
            'database' => $this->databaseBackupDirectory(),
            'full' => $this->fullBackupDirectory(),
            'migration' => $this->migrationBackupDirectory(),
            default => throw new RuntimeException('Tipo de backup invalido.'),
        };

        if (basename($filename) !== $filename || str_contains($filename, '\\')) {
            throw new RuntimeException('Nombre de archivo invalido.');
        }

        $expectedExtension = $type === 'database' ? '.sql' : '.zip';
        if (! str_ends_with(strtolower($filename), $expectedExtension)) {
            throw new RuntimeException('Extension de backup invalida.');
        }

        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $realDirectory = realpath($directory);
        $realPath = realpath($path);

        if (! $realDirectory || ! $realPath || ! is_file($realPath)) {
            throw new RuntimeException('Backup no encontrado.');
        }

        $normalizedDirectory = strtolower(str_replace('\\', '/', $realDirectory));
        $normalizedPath = strtolower(str_replace('\\', '/', $realPath));

        if (! str_starts_with($normalizedPath, $normalizedDirectory . '/')) {
            throw new RuntimeException('Ruta de backup invalida.');
        }

        return $realPath;
    }

    protected function createPortableDatabaseExport(string $path): array
    {
        $connectionConfig = $this->databaseConnection();

        if (! in_array($connectionConfig['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('El paquete de migracion solo esta disponible para MySQL/MariaDB.');
        }

        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $database = (string) $connection->getDatabaseName();

        if ($database === '') {
            throw new RuntimeException('La base de datos no esta configurada.');
        }

        $tables = collect($connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']))
            ->map(function (object $row) {
                $values = array_values((array) $row);

                return (string) ($values[0] ?? '');
            })
            ->filter()
            ->values();

        $handle = fopen($path, 'wb');
        if (! $handle) {
            throw new RuntimeException('No se pudo crear el archivo SQL.');
        }

        $rowCount = 0;

        try {
            fwrite($handle, "-- Sistema de Finanzas migration package\n");
            fwrite($handle, '-- Generated at: ' . now()->toIso8601String() . "\n");
            fwrite($handle, "-- Source database: {$database}\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($tables as $table) {
                fwrite($handle, 'DROP TABLE IF EXISTS ' . $this->quoteIdentifier($table) . ";\n");
            }

            fwrite($handle, "\n");

            foreach ($tables as $table) {
                $createRow = (array) $connection->selectOne('SHOW CREATE TABLE ' . $this->quoteIdentifier($table));
                $createSql = (string) ($createRow['Create Table'] ?? array_values($createRow)[1] ?? '');

                if ($createSql === '') {
                    throw new RuntimeException("No se pudo leer la estructura de {$table}.");
                }

                fwrite($handle, "--\n-- Table structure for {$table}\n--\n\n");
                fwrite($handle, $createSql . ";\n\n");

                $columns = collect($connection->select('SHOW COLUMNS FROM ' . $this->quoteIdentifier($table)))
                    ->pluck('Field')
                    ->map(fn (string $column) => $this->quoteIdentifier($column))
                    ->implode(', ');

                if ($columns === '') {
                    continue;
                }

                $rows = $connection->table($table)->get();
                if ($rows->isEmpty()) {
                    continue;
                }

                fwrite($handle, "--\n-- Data for {$table}\n--\n\n");

                foreach ($rows->chunk(100) as $chunk) {
                    $values = $chunk
                        ->map(function (object $row) use ($pdo) {
                            return '(' . collect((array) $row)
                                ->map(fn ($value) => $this->sqlValue($pdo, $value))
                                ->implode(', ') . ')';
                        })
                        ->implode(",\n");

                    fwrite($handle, 'INSERT INTO ' . $this->quoteIdentifier($table) . " ({$columns}) VALUES\n{$values};\n\n");
                    $rowCount += $chunk->count();
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('No se genero un SQL valido.');
        }

        return [
            'driver' => (string) $connectionConfig['driver'],
            'database' => $database,
            'tables' => $tables->count(),
            'rows' => $rowCount,
        ];
    }

    protected function runMysqlDump(array $connection, string $path): void
    {
        $binary = $this->resolveMysqlDumpBinary();
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('La base de datos no esta configurada.');
        }

        $arguments = [
            $binary,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--default-character-set=' . ($connection['charset'] ?? 'utf8mb4'),
            '--result-file=' . $path,
        ];

        if (! empty($connection['unix_socket'])) {
            $arguments[] = '--socket=' . $connection['unix_socket'];
        } else {
            $arguments[] = '--host=' . ($connection['host'] ?? '127.0.0.1');
            $arguments[] = '--port=' . ($connection['port'] ?? '3306');
        }

        $arguments[] = '--user=' . ($connection['username'] ?? 'root');
        $arguments[] = $database;

        $environment = [];
        if (($connection['password'] ?? '') !== '') {
            $environment['MYSQL_PWD'] = (string) $connection['password'];
        }

        $process = new Process($arguments, base_path(), $environment);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'mysqldump fallo.');
        }
    }

    protected function resolveMysqlDumpBinary(): string
    {
        if ($this->mysqldumpPath !== null) {
            if (is_file($this->mysqldumpPath)) {
                return $this->mysqldumpPath;
            }

            throw new RuntimeException('mysqldump no esta disponible en la ruta configurada.');
        }

        $pathCommand = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'command -v mysqldump';
        $process = Process::fromShellCommandline($pathCommand);
        $process->run();

        if ($process->isSuccessful()) {
            $candidate = trim(explode(PHP_EOL, $process->getOutput())[0] ?? '');
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        $laragonCandidate = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
        if (is_file($laragonCandidate)) {
            return $laragonCandidate;
        }

        throw new RuntimeException('mysqldump no esta disponible en este servidor.');
    }

    private function databaseConnection(): array
    {
        $name = config('database.default');

        return config("database.connections.{$name}", []);
    }

    private function successPayload(string $type, string $filename, string $path, string $message): array
    {
        return [
            'ok' => true,
            'type' => $type,
            'name' => $filename,
            'path' => self::BACKUP_ROOT . '/' . $this->directoryNameForType($type) . '/' . $filename,
            'absolute_path' => $path,
            'size' => filesize($path),
            'created_at' => Carbon::now(),
            'message' => $message,
        ];
    }

    /**
     * @param array<string, mixed> $backup
     * @return array<string, mixed>
     */
    private function copyBackupPayloadToExternal(array $backup, string $message): array
    {
        try {
            $source = (string) ($backup['absolute_path'] ?? '');
            if ($source === '' || ! is_file($source)) {
                throw new RuntimeException('El archivo de backup local no existe.');
            }

            $targetDirectory = $this->externalBackupDirectory((string) ($backup['type'] ?? 'database'));
            $target = $targetDirectory . DIRECTORY_SEPARATOR . basename((string) $backup['name']);

            if (! File::copy($source, $target)) {
                throw new RuntimeException('No se pudo copiar el archivo a la ruta externa.');
            }

            return [
                'ok' => true,
                'type' => $backup['type'],
                'name' => basename((string) $backup['name']),
                'absolute_path' => $target,
                'external_path' => $target,
                'size' => filesize($target),
                'created_at' => now(),
                'message' => $message,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'No se pudo guardar el backup externo: ' . $exception->getMessage(),
            ];
        }
    }

    private function addProjectFilesToZip(ZipArchive $zip, bool $includeEnv): void
    {
        $directories = ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes'];
        foreach ($directories as $directory) {
            $this->addDirectoryToZip($zip, base_path($directory), $directory);
        }

        $files = ['composer.json', 'composer.lock', 'package.json', 'vite.config.js', 'artisan'];
        if ($includeEnv) {
            $files[] = '.env';
        }

        foreach ($files as $file) {
            $path = base_path($file);
            if (is_file($path)) {
                $zip->addFile($path, $file);
            }
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPrefix): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->isLink() || $this->shouldExcludePath($file->getPathname())) {
                continue;
            }

            $relative = $zipPrefix . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            $zip->addFile($file->getPathname(), $relative);
        }
    }

    private function shouldExcludePath(string $path): bool
    {
        $relative = str_replace('\\', '/', str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path));

        foreach ([
            'vendor/',
            'node_modules/',
            '.git/',
            'storage/logs/',
            'storage/framework/cache/',
            'storage/framework/sessions/',
            'storage/framework/views/',
            'storage/app/private/finance-backups/',
        ] as $excluded) {
            if (str_starts_with($relative, $excluded)) {
                return true;
            }
        }

        return str_ends_with($relative, '.tmp') || str_ends_with($relative, '.temp');
    }

    private function filesForType(string $type): array
    {
        $directory = match ($type) {
            'database' => $this->databaseBackupDirectory(),
            'full' => $this->fullBackupDirectory(),
            'migration' => $this->migrationBackupDirectory(),
            default => throw new RuntimeException('Tipo de backup invalido.'),
        };

        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(fn (\SplFileInfo $file) => [
                'type' => $type,
                'name' => $file->getFilename(),
                'path' => self::BACKUP_ROOT . '/' . $type . '/' . $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    private function databaseBackupDirectory(): string
    {
        return storage_path('app/private/' . self::BACKUP_ROOT . '/database');
    }

    private function fullBackupDirectory(): string
    {
        return storage_path('app/private/' . self::BACKUP_ROOT . '/full');
    }

    private function migrationBackupDirectory(): string
    {
        return storage_path('app/private/' . self::BACKUP_ROOT . '/migration');
    }

    private function directoryNameForType(string $type): string
    {
        return match ($type) {
            'database' => 'database',
            'full' => 'full',
            'migration' => 'migration',
            default => throw new RuntimeException('Tipo de backup invalido.'),
        };
    }

    private function migrationRestoreGuide(array $databaseExport): string
    {
        return implode("\n", [
            '# Restaurar copia local',
            '',
            '1. Crea una base vacia en Laragon o selecciona una base de pruebas.',
            '2. En HeidiSQL o phpMyAdmin importa `database/sistema-finanzas.sql`.',
            '3. Ajusta tu `.env` local para apuntar a esa base.',
            '4. Ejecuta `php artisan optimize:clear`.',
            '5. Entra con el usuario que venia en la copia exportada.',
            '',
            'Datos del paquete:',
            '- Motor: ' . ($databaseExport['driver'] ?? '-'),
            '- Base origen: ' . ($databaseExport['database'] ?? '-'),
            '- Tablas: ' . ($databaseExport['tables'] ?? 0),
            '- Filas: ' . ($databaseExport['rows'] ?? 0),
            '',
            'Este paquete no incluye `.env` ni credenciales.',
            '',
        ]);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function sqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private function externalBackupDirectory(string $type): string
    {
        $configuredPath = trim((string) config('finance.external_backup_path'));

        if ($configuredPath === '') {
            throw new RuntimeException('FINANCE_EXTERNAL_BACKUP_PATH no está configurado.');
        }

        if (! is_dir($configuredPath)) {
            throw new RuntimeException('La ruta externa configurada no existe.');
        }

        if (! is_writable($configuredPath)) {
            throw new RuntimeException('La ruta externa configurada no tiene permisos de escritura.');
        }

        $safeType = $type === 'full' ? 'full' : 'database';
        $target = rtrim($configuredPath, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . self::BACKUP_ROOT . DIRECTORY_SEPARATOR . $safeType;
        File::ensureDirectoryExists($target);

        if (! is_writable($target)) {
            throw new RuntimeException('La carpeta externa de backups no tiene permisos de escritura.');
        }

        return $target;
    }
}
