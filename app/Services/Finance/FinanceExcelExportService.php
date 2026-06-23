<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\RentalContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FinanceExcelExportService
{
    private const EXPORT_DIR = 'private/finance-exports';
    private const TEMP_DIR = 'finance-exports-temp';

    public function __construct(
        private readonly FinanceSummaryService $summaryService,
    ) {
    }

    /**
     * Exporta movimientos a Excel
     */
    public function exportMovements(User $user, ?string $month = null, ?string $year = null): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movimientos');

        [$movements, $dateRange] = $this->getMovementsData($user, $month, $year);
        $filename = $this->generateFilename('movimientos', $dateRange);

        // Headers
        $headers = ['Fecha', 'Tipo', 'Cuenta', 'Categoría', 'Persona', 'Descripción', 'Monto', 'Notas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        // Data
        $row = 2;
        foreach ($movements as $movement) {
            $sheet->setCellValueByColumnAndRow(1, $row, $movement->happened_on?->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, $movement->movement_type);
            $sheet->setCellValueByColumnAndRow(3, $row, $movement->account?->name ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row, $movement->category?->name ?? 'Sin categoría');
            $sheet->setCellValueByColumnAndRow(5, $row, $movement->person?->name ?? '');
            $sheet->setCellValueByColumnAndRow(6, $row, $movement->description ?? '');
            $sheet->setCellValueByColumnAndRow(7, $row, (float)$movement->amount);
            $sheet->setCellValueByColumnAndRow(8, $row, $movement->notes ?? '');
            $row++;
        }

        // Format numbers
        $sheet->getStyle('G2:G' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta cortes diarios a Excel
     */
    public function exportDailyCuts(User $user, ?string $month = null): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cortes');

        [$cuts, $dateRange] = $this->getDailyCutsData($user, $month);
        $filename = $this->generateFilename('cortes-diarios', $dateRange);

        // Headers
        $headers = ['Fecha', 'Saldo Inicial', 'Ingresos', 'Egresos', 'Saldo Final', 'Notas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        // Data
        $row = 2;
        foreach ($cuts as $cut) {
            $sheet->setCellValueByColumnAndRow(1, $row, $cut->cut_date->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, (float)$cut->opening_balance);
            $sheet->setCellValueByColumnAndRow(3, $row, (float)$cut->total_income);
            $sheet->setCellValueByColumnAndRow(4, $row, (float)$cut->total_expense);
            $sheet->setCellValueByColumnAndRow(5, $row, (float)$cut->closing_balance);
            $sheet->setCellValueByColumnAndRow(6, $row, $cut->notes ?? '');
            $row++;
        }

        // Format numbers
        $sheet->getStyle('B2:E' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta flujo planeado a Excel
     */
    public function exportPlannedPayments(User $user, ?string $month = null): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Flujo Planeado');

        [$payments, $dateRange] = $this->getPlannedPaymentsData($user, $month);
        $filename = $this->generateFilename('flujo-planeado', $dateRange);

        // Headers
        $headers = ['Fecha Planeada', 'Descripción', 'Monto', 'Estado', 'Cuenta Vinculada', 'Notas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        // Data
        $row = 2;
        foreach ($payments as $payment) {
            $sheet->setCellValueByColumnAndRow(1, $row, $payment->planned_date->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, $payment->description);
            $sheet->setCellValueByColumnAndRow(3, $row, (float)$payment->amount);
            $sheet->setCellValueByColumnAndRow(4, $row, $payment->status);
            $sheet->setCellValueByColumnAndRow(5, $row, $payment->linkedMovement?->account?->name ?? '');
            $sheet->setCellValueByColumnAndRow(6, $row, $payment->notes ?? '');
            $row++;
        }

        // Format numbers
        $sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta ingresos esperados a Excel
     */
    public function exportExpectedIncomes(User $user, ?string $month = null): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ingresos Esperados');

        [$incomes, $dateRange] = $this->getExpectedIncomesData($user, $month);
        $filename = $this->generateFilename('ingresos-esperados', $dateRange);

        // Headers
        $headers = ['Fecha Esperada', 'Descripción', 'Monto', 'Estado', 'Movimiento Vinculado', 'Notas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        // Data
        $row = 2;
        foreach ($incomes as $income) {
            $sheet->setCellValueByColumnAndRow(1, $row, $income->expected_date->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, $income->description);
            $sheet->setCellValueByColumnAndRow(3, $row, (float)$income->amount);
            $sheet->setCellValueByColumnAndRow(4, $row, $income->status);
            $sheet->setCellValueByColumnAndRow(5, $row, $income->linkedMovement?->description ?? '');
            $sheet->setCellValueByColumnAndRow(6, $row, $income->notes ?? '');
            $row++;
        }

        // Format numbers
        $sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta créditos a Excel
     */
    public function exportCredits(User $user, ?string $year = null): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Créditos');

        [$credits, $dateRange] = $this->getCreditsData($user, $year);
        $filename = $this->generateFilename('creditos', $dateRange);

        // Headers
        $headers = ['Fecha Contratación', 'Nombre', 'Monto Total', 'Plazo (meses)', 'Saldo Pendiente', 'Tasa %', 'Estado', 'Notas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        // Data
        $row = 2;
        foreach ($credits as $credit) {
            $sheet->setCellValueByColumnAndRow(1, $row, $credit->contract_date->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, $credit->name);
            $sheet->setCellValueByColumnAndRow(3, $row, (float)$credit->total_amount);
            $sheet->setCellValueByColumnAndRow(4, $row, $credit->term_months);
            $sheet->setCellValueByColumnAndRow(5, $row, (float)$credit->pending_balance);
            $sheet->setCellValueByColumnAndRow(6, $row, (float)($credit->interest_rate ?? 0));
            $sheet->setCellValueByColumnAndRow(7, $row, $credit->status);
            $sheet->setCellValueByColumnAndRow(8, $row, $credit->notes ?? '');
            $row++;
        }

        // Format numbers
        $sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode('0.00%');

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta San Juan (rentas) a Excel
     */
    public function exportSanJuan(User $user, ?string $year = null): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('San Juan');

        [$contracts, $dateRange] = $this->getSanJuanData($user, $year);
        $filename = $this->generateFilename('san-juan', $dateRange);

        // Headers
        $headers = ['Fecha Contrato', 'Inmueble', 'Inquilino', 'Renta Mensual', 'Depósito', 'Estado', 'Última Recepción', 'Notas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        // Data
        $row = 2;
        foreach ($contracts as $contract) {
            $sheet->setCellValueByColumnAndRow(1, $row, $contract->start_date->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, $contract->property_name ?? '');
            $sheet->setCellValueByColumnAndRow(3, $row, $contract->tenant_name ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row, (float)$contract->monthly_rent);
            $sheet->setCellValueByColumnAndRow(5, $row, (float)($contract->deposit ?? 0));
            $sheet->setCellValueByColumnAndRow(6, $row, $contract->status ?? 'active');
            $sheet->setCellValueByColumnAndRow(7, $row, $contract->last_payment_received_at?->format('Y-m-d') ?? '');
            $sheet->setCellValueByColumnAndRow(8, $row, $contract->notes ?? '');
            $row++;
        }

        // Format numbers
        $sheet->getStyle('D2:E' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta resumen mensual a Excel (múltiples hojas)
     */
    public function exportMonthlySummary(User $user, ?string $month = null): array
    {
        $spreadsheet = new Spreadsheet();
        $monthStr = $month ?? now()->format('Y-m');
        [$monthStart, $monthEnd] = $this->summaryService->monthRange($monthStr);
        $filename = $this->generateFilename('resumen-mensual', $monthStr);

        // Hoja 1: Resumen General
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Resumen');
        $this->addMonthlySummarySheet($sheet1, $user, $monthStart, $monthEnd);

        // Hoja 2: Movimientos
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Movimientos');
        $this->addMovementsSheet($sheet2, $user, $monthStart, $monthEnd);

        // Hoja 3: Por Categoría
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Por Categoría');
        $this->addCategoryAnalysisSheet($sheet3, $user, $monthStart, $monthEnd);

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Exporta resumen anual a Excel
     */
    public function exportYearlySummary(User $user, ?int $year = null): array
    {
        $spreadsheet = new Spreadsheet();
        $yearValue = $year ?? now()->year;
        $filename = $this->generateFilename('resumen-anual', (string)$yearValue);

        // Hoja 1: Resumen por mes
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Resumen Mensual');
        $this->addYearlySummarySheet($sheet1, $user, $yearValue);

        // Hoja 2: Totales anuales
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Totales');
        $this->addAnnualTotalsSheet($sheet2, $user, $yearValue);

        return $this->saveAndReturnPath($spreadsheet, $filename);
    }

    /**
     * Lista archivos de exportación disponibles para descarga
     */
    public function listExports(): array
    {
        $path = storage_path('app/' . self::EXPORT_DIR);

        if (!is_dir($path)) {
            return [];
        }

        $files = array_diff(scandir($path), ['.', '..']);
        $exports = [];

        foreach ($files as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $exports[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'created_at' => Carbon::createFromTimestamp(filemtime($filePath)),
                    'download_url' => route('finance.exports.download', ['filename' => $file]),
                ];
            }
        }

        usort($exports, fn($a, $b) => $b['created_at']->timestamp - $a['created_at']->timestamp);

        return $exports;
    }

    /**
     * Descarga un archivo de exportación
     */
    public function downloadExport(string $filename): string
    {
        $filename = basename($filename);
        $path = storage_path('app/' . self::EXPORT_DIR . '/' . $filename);

        if (!file_exists($path)) {
            throw new \Exception('Export file not found.');
        }

        return $path;
    }

    // ==================== Métodos Privados ====================

    private function getMovementsData(User $user, ?string $month, ?string $year): array
    {
        $query = Movement::query()
            ->where('user_id', $user->id)
            ->with(['account', 'category', 'person'])
            ->orderBy('happened_on', 'desc');

        if ($month) {
            [$start, $end] = $this->summaryService->monthRange($month);
            $query->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()]);
            $dateRange = $month;
        } elseif ($year) {
            $start = Carbon::create($year, 1, 1);
            $end = Carbon::create($year, 12, 31);
            $query->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()]);
            $dateRange = (string)$year;
        } else {
            $dateRange = 'completo';
        }

        return [$query->get(), $dateRange];
    }

    private function getDailyCutsData(User $user, ?string $month): array
    {
        $query = DailyCut::query()
            ->where('user_id', $user->id)
            ->orderBy('cut_date', 'desc');

        if ($month) {
            [$start, $end] = $this->summaryService->monthRange($month);
            $query->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()]);
            $dateRange = $month;
        } else {
            $dateRange = 'completo';
        }

        return [$query->get(), $dateRange];
    }

    private function getPlannedPaymentsData(User $user, ?string $month): array
    {
        $query = PlannedPayment::query()
            ->where('user_id', $user->id)
            ->with(['linkedMovement.account'])
            ->orderBy('planned_date', 'desc');

        if ($month) {
            [$start, $end] = $this->summaryService->monthRange($month);
            $query->whereBetween('planned_date', [$start->toDateString(), $end->toDateString()]);
            $dateRange = $month;
        } else {
            $dateRange = 'completo';
        }

        return [$query->get(), $dateRange];
    }

    private function getExpectedIncomesData(User $user, ?string $month): array
    {
        $query = ExpectedIncome::query()
            ->where('user_id', $user->id)
            ->with(['linkedMovement'])
            ->orderBy('expected_date', 'desc');

        if ($month) {
            [$start, $end] = $this->summaryService->monthRange($month);
            $query->whereBetween('expected_date', [$start->toDateString(), $end->toDateString()]);
            $dateRange = $month;
        } else {
            $dateRange = 'completo';
        }

        return [$query->get(), $dateRange];
    }

    private function getCreditsData(User $user, ?string $year): array
    {
        $query = CreditPurchase::query()
            ->where('user_id', $user->id)
            ->orderBy('contract_date', 'desc');

        if ($year) {
            $start = Carbon::create($year, 1, 1);
            $end = Carbon::create($year, 12, 31);
            $query->whereBetween('contract_date', [$start->toDateString(), $end->toDateString()]);
            $dateRange = $year;
        } else {
            $dateRange = 'completo';
        }

        return [$query->get(), $dateRange];
    }

    private function getSanJuanData(User $user, ?string $year): array
    {
        $query = RentalContract::query()
            ->where('user_id', $user->id)
            ->orderBy('start_date', 'desc');

        if ($year) {
            $start = Carbon::create($year, 1, 1);
            $end = Carbon::create($year, 12, 31);
            $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()]);
            $dateRange = $year;
        } else {
            $dateRange = 'completo';
        }

        return [$query->get(), $dateRange];
    }

    private function addMonthlySummarySheet($sheet, User $user, Carbon $start, Carbon $end): void
    {
        $totals = $this->calculateTotals($user, $start, $end);

        $sheet->setCellValue('A1', 'RESUMEN MENSUAL');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:D1');

        $sheet->setCellValue('A2', 'Período: ' . $start->format('Y-m-d') . ' al ' . $end->format('Y-m-d'));
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $row = 4;
        $sheet->setCellValue('A' . $row, 'Concepto');
        $sheet->setCellValue('B' . $row, 'Monto');
        $this->styleHeader($sheet, $row, 2);

        $row++;
        foreach ($totals as $key => $value) {
            $sheet->setCellValue('A' . $row, ucfirst($key));
            $sheet->setCellValue('B' . $row, (float)$value);
            $row++;
        }

        $sheet->getStyle('B5:B' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
    }

    private function addMovementsSheet($sheet, User $user, Carbon $start, Carbon $end): void
    {
        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->with(['category', 'account'])
            ->orderBy('happened_on')
            ->get();

        $headers = ['Fecha', 'Tipo', 'Categoría', 'Descripción', 'Monto'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        $row = 2;
        foreach ($movements as $movement) {
            $sheet->setCellValueByColumnAndRow(1, $row, $movement->happened_on?->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(2, $row, $movement->movement_type);
            $sheet->setCellValueByColumnAndRow(3, $row, $movement->category?->name ?? 'Sin categoría');
            $sheet->setCellValueByColumnAndRow(4, $row, $movement->description ?? '');
            $sheet->setCellValueByColumnAndRow(5, $row, (float)$movement->amount);
            $row++;
        }

        $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function addCategoryAnalysisSheet($sheet, User $user, Carbon $start, Carbon $end): void
    {
        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->with('category')
            ->get();

        $grouped = $movements->groupBy(fn(Movement $m) => $m->category?->name ?: 'Sin categoría')
            ->map(fn($group) => [
                'category' => $group->first()?->category?->name ?: 'Sin categoría',
                'amount' => $group->sum(fn(Movement $m) => (float)$m->amount),
                'count' => $group->count(),
            ])
            ->sortByDesc('amount');

        $headers = ['Categoría', 'Monto', 'Cantidad'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(20);
        }
        $this->styleHeader($sheet, 1, count($headers));

        $row = 2;
        foreach ($grouped as $item) {
            $sheet->setCellValueByColumnAndRow(1, $row, $item['category']);
            $sheet->setCellValueByColumnAndRow(2, $row, (float)$item['amount']);
            $sheet->setCellValueByColumnAndRow(3, $row, $item['count']);
            $row++;
        }

        $sheet->getStyle('B2:B' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function addYearlySummarySheet($sheet, User $user, int $year): void
    {
        $headers = ['Mes', 'Ingresos', 'Egresos', 'Neto'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
        }
        $this->styleHeader($sheet, 1, count($headers));

        $monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $row = 2;
        foreach (range(1, 12) as $month) {
            $start = Carbon::create($year, $month, 1);
            $end = $start->copy()->endOfMonth();
            $totals = $this->calculateTotals($user, $start, $end);

            $sheet->setCellValueByColumnAndRow(1, $row, $monthNames[$month - 1]);
            $sheet->setCellValueByColumnAndRow(2, $row, (float)($totals['income'] ?? 0));
            $sheet->setCellValueByColumnAndRow(3, $row, (float)($totals['expense'] ?? 0));
            $sheet->setCellValueByColumnAndRow(4, $row, (float)($totals['net'] ?? 0));
            $row++;
        }

        $sheet->getStyle('B2:D13')->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function addAnnualTotalsSheet($sheet, User $user, int $year): void
    {
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);
        $totals = $this->calculateTotals($user, $start, $end);

        $sheet->setCellValue('A1', 'TOTALES ANUALES ' . $year);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 3;
        foreach ($totals as $key => $value) {
            $sheet->setCellValue('A' . $row, ucfirst($key));
            $sheet->setCellValue('B' . $row, (float)$value);
            $row++;
        }

        $sheet->getStyle('B3:B' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
    }

    private function calculateTotals(User $user, Carbon $start, Carbon $end): array
    {
        $movements = Movement::query()
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->get();

        $income = $movements
            ->whereIn('movement_type', ['income', 'yield'])
            ->sum(fn(Movement $m) => (float)$m->amount);

        $expense = $movements
            ->where('movement_type', 'expense')
            ->sum(fn(Movement $m) => (float)$m->amount);

        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'net' => round($income - $expense, 2),
        ];
    }

    private function styleHeader($sheet, int $row, int $colCount): void
    {
        for ($col = 1; $col <= $colCount; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $cell->getStyle()
                ->getFont()
                ->setBold(true)
                ->setColor(['rgb' => 'FFFFFF']);
            $cell->getStyle()
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('366092');
            $cell->getStyle()
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $cell->getStyle()
                ->getBorder()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }
    }

    private function generateFilename(string $type, string $dateRange): string
    {
        return sprintf(
            'finanzas-%s-%s-%s.xlsx',
            $type,
            $dateRange,
            now()->format('Y-m-d-His')
        );
    }

    private function saveAndReturnPath(Spreadsheet $spreadsheet, string $filename): array
    {
        $directory = storage_path('app/' . self::EXPORT_DIR);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory . DIRECTORY_SEPARATOR . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        $spreadsheet->disconnectWorksheets();

        return [
            'ok' => true,
            'filename' => $filename,
            'path' => $filepath,
            'size' => filesize($filepath),
            'url' => route('finance.exports.download', ['filename' => $filename]),
        ];
    }
}
