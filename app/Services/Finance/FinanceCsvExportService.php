<?php

namespace App\Services\Finance;

use App\Models\Finance\Movement;
use App\Support\FinanceLabels;
use Illuminate\Support\Facades\File;

class FinanceCsvExportService
{
    private const EXPORT_ROOT = 'finance-exports';

    /**
     * @param iterable<Movement> $movements
     * @param array<string, string> $metadata
     * @return array<string, mixed>
     */
    public function exportMovements(string $prefix, iterable $movements, array $metadata = []): array
    {
        $filename = $this->safeFilename($prefix) . '-' . now()->format('Ymd-His') . '.csv';
        $directory = $this->exportDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        try {
            File::ensureDirectoryExists($directory);

            $handle = fopen($path, 'wb');
            if ($handle === false) {
                throw new \RuntimeException('No se pudo crear el archivo CSV.');
            }

            // BOM para que Excel abra acentos y eñes correctamente.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($metadata as $label => $value) {
                fputcsv($handle, [$label, $value]);
            }

            if ($metadata !== []) {
                fputcsv($handle, []);
            }

            fputcsv($handle, [
                'Fecha',
                'Tipo',
                'Cuenta',
                'Categoría',
                'Persona',
                'Descripción',
                'Monto',
                'Notas',
            ]);

            foreach ($movements as $movement) {
                fputcsv($handle, [
                    $movement->happened_on?->format('Y-m-d'),
                    FinanceLabels::movementType($movement->movement_type),
                    $movement->account?->name ?? '',
                    $movement->category?->name ?? '',
                    $movement->person?->name ?? '',
                    $movement->description,
                    number_format((float) $movement->amount, 2, '.', ''),
                    $movement->notes ?? '',
                ]);
            }

            fclose($handle);

            return [
                'ok' => true,
                'name' => $filename,
                'absolute_path' => $path,
                'size' => filesize($path),
                'message' => 'Exportación CSV creada.',
            ];
        } catch (\Throwable $exception) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            if (is_file($path)) {
                @unlink($path);
            }

            return [
                'ok' => false,
                'message' => 'No se pudo exportar CSV: ' . $exception->getMessage(),
            ];
        }
    }

    public function exportDirectory(): string
    {
        return storage_path('app/private/' . self::EXPORT_ROOT);
    }

    private function safeFilename(string $prefix): string
    {
        $safe = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($prefix)) ?: 'exportacion';

        return trim($safe, '-') ?: 'exportacion';
    }
}
