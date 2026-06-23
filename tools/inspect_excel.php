<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$files = [
    'D:/Github/Finanzas/Gastos 2026.xlsx',
    'D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx',
];

foreach ($files as $file) {
    echo PHP_EOL . basename($file) . PHP_EOL;

    $reader = IOFactory::createReaderForFile($file);
    $reader->setReadDataOnly(false);
    $book = $reader->load($file);

    foreach ($book->getWorksheetIterator() as $sheet) {
        echo $sheet->getTitle() . ' ' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow() . PHP_EOL;
    }
}

function dumpRange(string $file, string $sheetName, string $range): void
{
    $reader = IOFactory::createReaderForFile($file);
    $reader->setReadDataOnly(false);
    $book = $reader->load($file);
    $sheet = $book->getSheetByName($sheetName);

    echo PHP_EOL . basename($file) . " :: {$sheetName}!{$range}" . PHP_EOL;

    foreach ($sheet->rangeToArray($range, null, true, true, true) as $rowNumber => $row) {
        $values = [];

        foreach ($row as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $values[] = $column . $rowNumber . '=' . trim((string) $value);
        }

        if ($values !== []) {
            echo implode(' | ', $values) . PHP_EOL;
        }
    }
}

dumpRange('D:/Github/Finanzas/Gastos 2026.xlsx', 'Junio', 'A1:L75');
dumpRange('D:/Github/Finanzas/Gastos 2026.xlsx', 'Junio', 'F69:K99');
dumpRange('D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx', 'Ingresos Reales', 'A1:I25');
dumpRange('D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx', 'Gastos Reales', 'A1:L30');
dumpRange('D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx', 'Conciliacion Diaria', 'A1:S20');
dumpRange('D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx', 'Flujo Planeado', 'A1:L30');
dumpRange('D:/Github/Finanzas/Nuevos Gastos 2026 v4 CORREGIDO.xlsx', 'Rendimientos', 'A1:F20');
