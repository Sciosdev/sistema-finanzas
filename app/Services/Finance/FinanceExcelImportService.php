<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\RentalContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceExcelImportService
{
    private int $expectedIncomesImported = 0;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
    ) {
    }

    public function import(User $user, string $path, bool $fresh = false): array
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("No existe el archivo: {$path}");
        }

        $this->catalogs->ensureForUser($user);

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false);
        $book = $reader->load($path);

        return DB::transaction(function () use ($book, $user, $fresh): array {
            if ($fresh) {
                $this->deletePreviousExcelImport($user);
            }

            $counts = [
                'incomes' => 0,
                'expected_incomes' => 0,
                'yields' => 0,
                'expenses' => 0,
                'planned_payments' => 0,
                'rental_contracts' => 0,
                'daily_cuts' => 0,
            ];

            $importThroughDate = $this->importThroughDate($book->getSheetByName('Conciliacion Diaria'));
            $this->expectedIncomesImported = 0;

            if ($sheet = $book->getSheetByName('Ingresos Reales')) {
                $counts['incomes'] = $this->importIncomes($user, $sheet, $importThroughDate);
                $counts['expected_incomes'] = $this->expectedIncomesImported;
            }

            if ($sheet = $book->getSheetByName('Rendimientos')) {
                $counts['yields'] = $this->importYields($user, $sheet, $importThroughDate);
            }

            if ($sheet = $book->getSheetByName('Gastos Reales')) {
                $counts['expenses'] = $this->importExpenses($user, $sheet, $importThroughDate);
            }

            if ($sheet = $book->getSheetByName('Flujo Planeado')) {
                $counts['planned_payments'] = $this->importPlannedPayments($user, $sheet);
            }

            if ($sheet = $book->getSheetByName('San Juan')) {
                $counts['rental_contracts'] = $this->importRentalContracts($user, $sheet);
            }

            if ($sheet = $book->getSheetByName('Conciliacion Diaria')) {
                $counts['daily_cuts'] = $this->importDailyCuts($user, $sheet);
            }

            return $counts;
        });
    }

    private function importIncomes(User $user, Worksheet $sheet, ?Carbon $importThroughDate): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 4; $row <= $highestRow; $row++) {
            $date = $this->dateFromCell($sheet->getCell("A{$row}"));
            $amount = $this->money($this->cell($sheet, "C{$row}"));
            $concept = $this->text($this->cell($sheet, "D{$row}"));
            $categoryName = $this->text($this->cell($sheet, "E{$row}")) ?: $this->text($this->cell($sheet, "H{$row}"));
            $use = $this->text($this->cell($sheet, "I{$row}"));

            if (!$date || !$amount || $amount <= 0 || !Str::contains(Str::lower($use), 'si')) {
                continue;
            }

            if (Str::contains(Str::lower($concept), ['rendimientos nu', 'rendimientos mpw'])) {
                continue;
            }

            $category = $this->category($user, $categoryName ?: 'Otros ingresos', 'income');
            $person = $this->personFromText($user, $concept);
            $flags = $this->flags($category, $person, $concept);

            if ($importThroughDate && $date->gt($importThroughDate)) {
                $this->upsertExpectedIncome($user, $row, $date, $amount, $concept, $category, $person, $flags, $sheet);
                continue;
            }

            Movement::updateOrCreate(
                ['user_id' => $user->id, 'import_key' => "excel:v4:ingresos:{$row}"],
                [
                    'happened_on' => $date->toDateString(),
                    'movement_type' => 'income',
                    'amount' => $amount,
                    'description' => $concept,
                    'category_id' => $category?->id,
                    'person_id' => $person?->id,
                    'is_san_juan' => $flags['is_san_juan'],
                    'is_rent' => $flags['is_rent'],
                    'is_unknown' => false,
                    'source' => 'excel:v4:ingresos',
                    'notes' => $this->text($this->cell($sheet, "G{$row}")),
                ]
            );

            $count++;
        }

        return $count;
    }

    private function importYields(User $user, Worksheet $sheet, ?Carbon $importThroughDate): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestDataRow();
        $category = $this->category($user, 'Rendimientos', 'yield');

        for ($row = 4; $row <= $highestRow; $row++) {
            $date = $this->dateFromCell($sheet->getCell("A{$row}"));
            $accountName = $this->text($this->cell($sheet, "B{$row}"));
            $amount = $this->money($this->cell($sheet, "D{$row}"));

            if (!$date || !$accountName || !$amount || $amount <= 0) {
                continue;
            }

            if ($importThroughDate && $date->gt($importThroughDate)) {
                continue;
            }

            $account = $this->account($user, $accountName, 'card');

            Movement::updateOrCreate(
                ['user_id' => $user->id, 'import_key' => "excel:v4:rendimientos:{$row}"],
                [
                    'happened_on' => $date->toDateString(),
                    'movement_type' => 'yield',
                    'amount' => $amount,
                    'description' => 'Rendimiento ' . $accountName,
                    'account_id' => $account?->id,
                    'category_id' => $category?->id,
                    'is_san_juan' => false,
                    'is_rent' => false,
                    'is_unknown' => false,
                    'source' => 'excel:v4:rendimientos',
                    'notes' => $this->text($this->cell($sheet, "E{$row}")),
                ]
            );

            $count++;
        }

        return $count;
    }

    private function importExpenses(User $user, Worksheet $sheet, ?Carbon $importThroughDate): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 4; $row <= $highestRow; $row++) {
            $date = $this->dateFromCell($sheet->getCell("A{$row}"));
            $amount = $this->money($this->cell($sheet, "D{$row}"));
            $concept = $this->text($this->cell($sheet, "E{$row}"));

            if (!$date || !$amount || $amount <= 0 || $concept === '') {
                continue;
            }

            if ($importThroughDate && $date->gt($importThroughDate)) {
                continue;
            }

            $category = $this->category($user, $this->text($this->cell($sheet, "G{$row}")) ?: 'Otros', 'expense');
            $account = $this->account($user, $this->text($this->cell($sheet, "I{$row}")) ?: null);
            $person = $this->personFromText($user, $concept);
            $flags = $this->flags($category, $person, $concept);
            $isUnknown = Str::contains(Str::lower($this->text($this->cell($sheet, "J{$row}"))), 'si') || trim($concept) === '?';

            Movement::updateOrCreate(
                ['user_id' => $user->id, 'import_key' => "excel:v4:gastos:{$row}"],
                [
                    'happened_on' => $date->toDateString(),
                    'movement_type' => 'expense',
                    'amount' => $amount,
                    'description' => $concept,
                    'account_id' => $account?->id,
                    'category_id' => $category?->id,
                    'person_id' => $person?->id,
                    'is_san_juan' => $flags['is_san_juan'] || Str::contains(Str::lower($this->text($this->cell($sheet, "H{$row}"))), 'si'),
                    'is_rent' => false,
                    'is_unknown' => $isUnknown,
                    'source' => 'excel:v4:gastos',
                    'notes' => trim($this->text($this->cell($sheet, "K{$row}")) . ' ' . $this->text($this->cell($sheet, "L{$row}"))),
                ]
            );

            $count++;
        }

        return $count;
    }

    private function importPlannedPayments(User $user, Worksheet $sheet): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 4; $row <= $highestRow; $row++) {
            $period = $this->monthFromCell($sheet->getCell("A{$row}"));
            $dueDate = $this->dateFromCell($sheet->getCell("C{$row}"));
            $type = $this->text($this->cell($sheet, "E{$row}"));
            $personName = $this->text($this->cell($sheet, "F{$row}"));
            $concept = $this->text($this->cell($sheet, "G{$row}"));
            $amount = $this->money($this->cell($sheet, "H{$row}"));

            if (!$period || !$amount || $amount <= 0 || ($personName === '' && $concept === '')) {
                continue;
            }

            $status = $this->plannedStatus($this->text($this->cell($sheet, "K{$row}")));
            $paidAmount = $this->money($this->cell($sheet, "J{$row}")) ?? 0.0;
            $name = trim($personName . ($concept !== '' ? ' - ' . $concept : ''));
            $person = $personName !== '' ? Person::firstOrCreate(
                ['user_id' => $user->id, 'name' => $personName],
                ['type' => 'other', 'is_active' => true]
            ) : null;
            $category = $this->category($user, $type ?: 'Planeado', 'expense');
            $flags = $this->flags($category, $person, $name);

            PlannedPayment::updateOrCreate(
                ['user_id' => $user->id, 'import_key' => "excel:v4:flujo:{$row}"],
                [
                    'period_month' => $period->toDateString(),
                    'due_date' => $dueDate?->toDateString(),
                    'name' => $name,
                    'amount' => $amount,
                    'paid_amount' => min($paidAmount, $amount),
                    'paid_on' => $status === 'paid' ? $dueDate?->toDateString() : null,
                    'status' => $status,
                    'category_id' => $category?->id,
                    'person_id' => $person?->id,
                    'is_credit' => Str::contains(Str::lower($type), 'credito'),
                    'is_san_juan' => $flags['is_san_juan'],
                    'notes' => $this->text($this->cell($sheet, "L{$row}")),
                ]
            );

            $count++;
        }

        return $count;
    }

    private function importDailyCuts(User $user, Worksheet $sheet): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestDataRow();
        $accountColumns = [
            'D' => 'NU',
            'E' => 'MPW',
            'F' => 'BBVA',
            'G' => 'DIDI',
            'H' => 'Mercado Pago',
            'I' => 'Otras tarjetas',
            'K' => 'Efectivo',
        ];

        for ($row = 4; $row <= $highestRow; $row++) {
            $date = $this->dateFromCell($sheet->getCell("A{$row}"));
            $realTotal = $this->money($this->cell($sheet, "L{$row}"));

            if (!$date || $realTotal === null) {
                continue;
            }

            $cash = $this->money($this->cell($sheet, "K{$row}")) ?? 0.0;
            $cards = $this->money($this->cell($sheet, "J{$row}")) ?? 0.0;
            $expected = $this->money($this->cell($sheet, "P{$row}")) ?? $this->summaryService->expectedThroughDate($user, $date);
            $difference = round($expected - $realTotal, 2);
            [$start, $end] = $this->summaryService->monthRange($date->format('Y-m'));
            $pending = $this->summaryService->pendingForMonth($user, $start, $end);

            $cut = DailyCut::updateOrCreate(
                ['user_id' => $user->id, 'cut_date' => $date->toDateString()],
                [
                    'expected_leftover' => $expected,
                    'cash_amount' => $cash,
                    'cards_amount' => $cards,
                    'real_total' => $realTotal,
                    'pending_payments' => $pending,
                    'difference' => $difference,
                    'amount_missing' => round($realTotal - $pending, 2),
                    'status' => abs($difference) <= 0.01 ? 'ok' : 'review',
                    'notes' => $this->text($this->cell($sheet, "S{$row}")),
                    'import_key' => "excel:v4:conciliacion:{$row}",
                ]
            );

            $cut->balances()->delete();

            foreach ($accountColumns as $column => $accountName) {
                $balance = $this->money($this->cell($sheet, "{$column}{$row}"));

                if ($balance === null) {
                    continue;
                }

                $account = $this->account($user, $accountName, $accountName === 'Efectivo' ? 'cash' : 'card');

                $cut->balances()->create([
                    'account_id' => $account->id,
                    'balance' => $balance,
                ]);
            }

            $count++;
        }

        return $count;
    }

    private function importRentalContracts(User $user, Worksheet $sheet): int
    {
        $count = 0;
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 4; $row <= $highestRow; $row++) {
            $name = $this->text($this->cell($sheet, "H{$row}"));
            $expectedAmount = $this->money($this->cell($sheet, "L{$row}")) ?? $this->money($this->cell($sheet, "I{$row}"));
            $dueDay = $this->money($this->cell($sheet, "K{$row}"));
            $room = $this->text($this->cell($sheet, "G{$row}"));

            if ($name === '' || !$expectedAmount || $expectedAmount <= 0 || !$dueDay) {
                continue;
            }

            $person = $this->personFromText($user, $name) ?? Person::firstOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                [
                    'type' => 'tenant',
                    'is_tenant' => true,
                    'is_active' => true,
                ]
            );

            if (!$person->is_tenant) {
                $person->forceFill([
                    'type' => 'tenant',
                    'is_tenant' => true,
                    'is_active' => true,
                ])->save();
            }

            $contract = RentalContract::firstOrNew([
                'user_id' => $user->id,
                'person_id' => $person->id,
            ]);

            if ($contract->exists && $contract->manual_override) {
                continue;
            }

            $contract->fill([
                'room' => $room !== '' && $room !== '-' ? $room : null,
                'expected_amount' => $expectedAmount,
                'due_day' => max(1, min(31, (int) $dueDay)),
                'is_active' => true,
                'manual_override' => false,
                'notes' => 'Importado desde San Juan',
            ])->save();

            $count++;
        }

        return $count;
    }

    private function deletePreviousExcelImport(User $user): void
    {
        DailyCut::where('user_id', $user->id)->whereNotNull('import_key')->delete();
        PlannedPayment::where('user_id', $user->id)->whereNotNull('import_key')->delete();
        ExpectedIncome::where('user_id', $user->id)->whereNotNull('import_key')->delete();
        Movement::where('user_id', $user->id)->whereNotNull('import_key')->delete();
    }

    private function upsertExpectedIncome(
        User $user,
        int $row,
        Carbon $date,
        float $amount,
        string $concept,
        ?Category $category,
        ?Person $person,
        array $flags,
        Worksheet $sheet
    ): void {
        $income = ExpectedIncome::firstOrNew([
            'user_id' => $user->id,
            'import_key' => "excel:v4:expected-income:{$row}",
        ]);

        $income->fill([
            'period_month' => $date->copy()->startOfMonth()->toDateString(),
            'due_date' => $date->toDateString(),
            'name' => $concept,
            'amount' => $amount,
            'category_id' => $category?->id,
            'person_id' => $person?->id,
            'is_rent' => $flags['is_rent'],
            'notes' => $this->text($this->cell($sheet, "G{$row}")),
        ]);

        if (!$income->exists) {
            $income->fill([
                'received_amount' => 0,
                'status' => 'pending',
            ]);
        }

        $income->save();
        $this->expectedIncomesImported++;
    }

    private function importThroughDate(?Worksheet $sheet): ?Carbon
    {
        if (!$sheet) {
            return null;
        }

        $latest = null;

        for ($row = 4; $row <= $sheet->getHighestDataRow(); $row++) {
            $date = $this->dateFromCell($sheet->getCell("A{$row}"));
            $realTotal = $this->money($this->cell($sheet, "L{$row}"));

            if (!$date || $realTotal === null) {
                continue;
            }

            if (!$latest || $date->gt($latest)) {
                $latest = $date;
            }
        }

        return $latest;
    }

    private function category(User $user, string $name, string $type): ?Category
    {
        $name = trim($name);

        if ($name === '' || $name === '-') {
            return null;
        }

        return Category::firstOrCreate(
            ['user_id' => $user->id, 'name' => $name, 'type' => $type],
            [
                'group' => $this->categoryGroup($name),
                'is_san_juan' => Str::contains(Str::lower($name), ['san juan', 'japam', 'snj']),
                'is_rent' => Str::contains(Str::lower($name), ['renta', 'rentas']),
                'is_active' => true,
            ]
        );
    }

    private function account(User $user, ?string $name, string $type = 'card'): ?Account
    {
        $name = trim((string) $name);

        if ($name === '' || $name === '-') {
            return null;
        }

        if ($name === 'Tarjeta') {
            $type = 'card';
        }

        return Account::firstOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            [
                'type' => $type,
                'is_active' => true,
                'display_order' => 100,
            ]
        );
    }

    private function personFromText(User $user, string $text): ?Person
    {
        $needle = Str::lower($text);

        return Person::where('user_id', $user->id)
            ->get()
            ->first(fn (Person $person) => Str::contains($needle, Str::lower($person->name)));
    }

    private function flags(?Category $category, ?Person $person, string $text): array
    {
        $lower = Str::lower($text);
        $isSanJuan = (bool) $category?->is_san_juan || Str::contains($lower, ['snj', 'san juan', 'japam', 'jorge', 'limpieza', 'cloro', 'jabon', 'escoba']);
        $isRent = (bool) $category?->is_rent || (bool) $person?->is_tenant || Str::contains($lower, ['renta', 'rentas']);

        return [
            'is_san_juan' => $isSanJuan,
            'is_rent' => $isRent,
        ];
    }

    private function categoryGroup(string $name): string
    {
        $lower = Str::lower($name);

        if (Str::contains($lower, ['san juan', 'japam', 'snj', 'renta'])) {
            return 'San Juan';
        }

        if (Str::contains($lower, ['rendimiento'])) {
            return 'Rendimientos';
        }

        return 'Importado';
    }

    private function plannedStatus(string $status): string
    {
        $lower = Str::lower(trim($status));

        return match (true) {
            Str::contains($lower, 'pagado') && !Str::contains($lower, 'no pagado') => 'paid',
            Str::contains($lower, 'vencido') => 'overdue',
            Str::contains($lower, 'no pagado') => 'skipped',
            default => 'pending',
        };
    }

    private function cell(Worksheet $sheet, string $coordinate): mixed
    {
        $cell = $sheet->getCell($coordinate);

        try {
            return $cell->getCalculatedValue();
        } catch (\Throwable) {
            return $cell->getValue();
        }
    }

    private function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $text = trim((string) $value);

        if ($text === '' || $text === '-') {
            return null;
        }

        $negative = str_starts_with($text, '(') && str_ends_with($text, ')');
        $number = preg_replace('/[^0-9.\-]/', '', $text);

        if ($number === '' || $number === '-' || !is_numeric($number)) {
            return null;
        }

        $amount = round((float) $number, 2);

        return $negative ? -abs($amount) : $amount;
    }

    private function dateFromCell(Cell $cell): ?Carbon
    {
        $value = $cell->getValue();

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function monthFromCell(Cell $cell): ?Carbon
    {
        $date = $this->dateFromCell($cell);

        if ($date) {
            return $date->startOfMonth();
        }

        $value = trim((string) $cell->getCalculatedValue());

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse('1 ' . $value)->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }
}
