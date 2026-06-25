<?php

namespace App\Console\Commands;

use App\Services\Finance\FinanceReleasePackager;
use Illuminate\Console\Command;

class FinanceBuildReleaseCommand extends Command
{
    /**
     * Pensado para ejecutarse EN LOCAL (no en HostGator). Genera un ZIP de
     * producción con vendor/ y public/build/ incluidos.
     */
    protected $signature = 'finance:build-release
        {--output= : Carpeta donde se guardará el ZIP (por defecto storage/app/releases)}';

    protected $description = 'Genera un ZIP de producción listo para subir a HostGator (incluye vendor/ y public/build/).';

    public function handle(): int
    {
        $packager = new FinanceReleasePackager(base_path());

        $problems = $packager->validate();
        if ($problems !== []) {
            $this->error('No se puede generar el paquete de producción:');
            foreach ($problems as $problem) {
                $this->line('  - ' . $problem);
            }

            return self::FAILURE;
        }

        $outputDirectory = $this->option('output')
            ? (string) $this->option('output')
            : storage_path('app/releases');

        $this->info('Generando paquete de producción...');
        $this->line('Esto puede tardar: el ZIP incluye vendor/ y public/build/.');

        $result = $packager->build($outputDirectory);

        if (! ($result['ok'] ?? false)) {
            $this->error($result['message'] ?? 'No se pudo generar el paquete.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Paquete generado correctamente.');
        $this->line('  Archivo: ' . $result['name']);
        $this->line('  Ruta:    ' . $result['path']);
        $this->line('  Tamaño:  ' . $this->humanSize((int) ($result['size'] ?? 0)));
        $this->line('  Archivos incluidos: ' . ($result['files'] ?? 0));
        $this->newLine();
        $this->line('Sube este ZIP por cPanel y sigue DEPLOY_HOSTGATOR.md (incluido dentro del ZIP).');

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
