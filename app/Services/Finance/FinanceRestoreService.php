<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Restauración estilo "All-in-One": toma un paquete .zip (paquete de migración o
 * backup de BD) que contiene un volcado .sql y lo importa, REEMPLAZANDO la base
 * de datos actual.
 *
 * Operación destructiva e irreversible: el controlador exige dueño, confirmación
 * escrita y crea un backup automático antes de invocar este servicio.
 *
 * No usa funciones de sistema; importa el SQL con DB::unprepared sobre la
 * conexión MySQL/MariaDB configurada.
 */
class FinanceRestoreService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public function restoreFromZip(string $zipPath): array
    {
        try {
            if (! is_file($zipPath)) {
                throw new RuntimeException('No se encontró el archivo del paquete.');
            }

            $sql = $this->extractSql($zipPath);

            if (trim($sql) === '') {
                throw new RuntimeException('El paquete no contiene un respaldo SQL válido.');
            }

            $this->importSql($sql);

            return ['ok' => true, 'message' => 'Restauración completada desde el paquete.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'No se pudo restaurar: ' . $exception->getMessage()];
        }
    }

    /**
     * Extrae el contenido del primer archivo .sql del zip (priorizando database/).
     */
    private function extractSql(string $zipPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('El paquete no es un .zip válido.');
        }

        try {
            $entry = null;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);
                if (! str_ends_with(strtolower($name), '.sql')) {
                    continue;
                }

                if (str_starts_with($name, 'database/')) {
                    $entry = $name;
                    break;
                }

                $entry = $entry ?? $name;
            }

            if ($entry === null) {
                throw new RuntimeException('El paquete no incluye ningún archivo .sql.');
            }

            $sql = $zip->getFromName($entry);
            if ($sql === false) {
                throw new RuntimeException('No se pudo leer el SQL del paquete.');
            }

            return (string) $sql;
        } finally {
            $zip->close();
        }
    }

    /**
     * Importa el SQL reemplazando la base actual. Solo MySQL/MariaDB.
     */
    protected function importSql(string $sql): void
    {
        $connectionName = (string) config('database.default');
        $driver = config("database.connections.{$connectionName}.driver");

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Restaurar solo está disponible para MySQL/MariaDB.');
        }

        DB::connection()->unprepared($sql);
    }
}
