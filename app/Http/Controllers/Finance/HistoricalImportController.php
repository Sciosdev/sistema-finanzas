<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HistoricalImportController extends Controller
{
    public function index()
    {
        return view('finance.imports.historical', [
            'preview' => session('finance_historical_import_preview'),
        ]);
    }

    public function template()
    {
        $content = "\xEF\xBB\xBF" . implode("\n", [
            'fecha,tipo,descripcion,monto,cuenta,categoria,persona,notas,san_juan,renta,desconocido,diferencia_conciliacion',
            '2025-12-31,egreso,Saldo Telcel,50,NU,Servicios,,Ejemplo conciliado,0,0,0,0',
            '2026-06-27,ingreso,Renta Cesar,2200,NU,Rentas San Juan,Cesar,Renta mensual,1,1,0,0',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-importacion-historica.csv"',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Selecciona un archivo CSV para revisar.',
            'file.file' => 'El archivo no es válido.',
            'file.max' => 'El archivo no debe pesar más de 10 MB.',
        ]);

        $result = $this->parseCsv($request->user(), $request->file('file')->getRealPath());

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No se pudo leer el CSV.');
        }

        session()->put('finance_historical_import_preview', $result['preview']);

        return redirect()
            ->route('finance.imports.historical.index')
            ->with('success', 'Vista previa lista. Revisa los datos antes de guardar.');
    }

    public function store(Request $request)
    {
        $preview = session('finance_historical_import_preview');

        if (! is_array($preview) || empty($preview['rows'])) {
            return back()->with('error', 'Primero genera una vista previa del archivo.');
        }

        $user = $request->user();
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($preview, $user, &$created, &$skipped) {
            foreach ($preview['rows'] as $row) {
                if (! ($row['valid'] ?? false) || ($row['duplicate'] ?? false)) {
                    $skipped++;
                    continue;
                }

                if (Movement::where('user_id', $user->id)->where('import_key', $row['import_key'])->exists()) {
                    $skipped++;
                    continue;
                }

                Movement::create([
                    'user_id' => $user->id,
                    'happened_on' => $row['happened_on'],
                    'movement_type' => $row['movement_type'],
                    'amount' => $row['amount'],
                    'description' => $row['description'],
                    'account_id' => $this->safeUserId(Account::class, $user, $row['account_id'] ?? null),
                    'category_id' => $this->safeUserId(Category::class, $user, $row['category_id'] ?? null),
                    'person_id' => $this->safeUserId(Person::class, $user, $row['person_id'] ?? null),
                    'is_san_juan' => (bool) ($row['is_san_juan'] ?? false),
                    'is_rent' => (bool) ($row['is_rent'] ?? false),
                    'is_unknown' => (bool) ($row['is_unknown'] ?? false),
                    'source' => 'historical_import',
                    'import_key' => $row['import_key'],
                    'notes' => $row['notes'] ?? null,
                ]);

                $created++;
            }
        });

        session()->forget('finance_historical_import_preview');

        return redirect()
            ->route('finance.movements.index')
            ->with('success', "Importación histórica guardada. Movimientos creados: {$created}. Omitidos: {$skipped}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCsv(User $user, string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return ['ok' => false, 'message' => 'No se pudo abrir el archivo CSV.'];
        }

        $firstLine = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $headers = fgetcsv($handle, 0, $delimiter);

        if (! is_array($headers)) {
            fclose($handle);

            return ['ok' => false, 'message' => 'El CSV no tiene encabezados.'];
        }

        $headerMap = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized !== '') {
                $headerMap[$normalized] = $index;
            }
        }

        $required = ['fecha', 'tipo', 'descripcion', 'monto'];
        $missing = collect($required)->reject(fn (string $column) => array_key_exists($column, $headerMap))->values();

        if ($missing->isNotEmpty()) {
            fclose($handle);

            return [
                'ok' => false,
                'message' => 'Faltan columnas obligatorias: ' . $missing->implode(', ') . '.',
            ];
        }

        $rows = [];
        $line = 1;

        while (($raw = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if ($this->isEmptyRow($raw)) {
                continue;
            }

            $rows[] = $this->normalizeRow($user, $raw, $headerMap, $line);
        }

        fclose($handle);

        return [
            'ok' => true,
            'preview' => [
                'rows' => $rows,
                'valid_count' => collect($rows)->where('valid', true)->where('duplicate', false)->count(),
                'warning_count' => collect($rows)->sum(fn (array $row) => count($row['warnings'] ?? [])),
                'error_count' => collect($rows)->where('valid', false)->count(),
                'expected_columns' => ['fecha', 'tipo', 'descripcion', 'monto', 'cuenta', 'categoria', 'persona', 'notas', 'san_juan', 'renta', 'desconocido', 'diferencia_conciliacion'],
            ],
        ];
    }

    /**
     * @param array<int, string|null> $raw
     * @param array<string, int> $headerMap
     * @return array<string, mixed>
     */
    private function normalizeRow(User $user, array $raw, array $headerMap, int $line): array
    {
        $errors = [];
        $warnings = [];

        $date = $this->parseDate($this->value($raw, $headerMap, 'fecha'));
        $type = $this->parseType($this->value($raw, $headerMap, 'tipo'));
        $amount = $this->parseAmount($this->value($raw, $headerMap, 'monto'));
        $description = trim($this->value($raw, $headerMap, 'descripcion'));

        if (! $date) {
            $errors[] = 'Fecha inválida.';
        }

        if (! $type) {
            $errors[] = 'Tipo inválido. Usa ingreso, egreso, rendimiento, transferencia o ajuste.';
        }

        if ($amount <= 0) {
            $errors[] = 'Monto inválido.';
        }

        if ($description === '') {
            $errors[] = 'Descripción vacía.';
        }

        $accountName = trim($this->value($raw, $headerMap, 'cuenta'));
        $categoryName = trim($this->value($raw, $headerMap, 'categoria'));
        $personName = trim($this->value($raw, $headerMap, 'persona'));
        $account = $this->findByName(Account::class, $user, $accountName);
        $category = $this->findByName(Category::class, $user, $categoryName);
        $person = $this->findByName(Person::class, $user, $personName);

        if ($accountName !== '' && ! $account) {
            $warnings[] = 'Cuenta no encontrada; se guardará sin cuenta.';
        }

        if ($categoryName !== '' && ! $category) {
            $warnings[] = 'Categoría no encontrada; se guardará sin categoría.';
        }

        if ($personName !== '' && ! $person) {
            $warnings[] = 'Persona no encontrada; se guardará sin persona.';
        }

        $conciliation = $this->parseAmount($this->value($raw, $headerMap, 'diferencia_conciliacion'), true);
        if ($conciliation !== null && abs($conciliation) > 0.01) {
            $errors[] = 'Diferencia de conciliación distinta de 0; requiere revisión antes de importar.';
        }

        $importKey = 'historico:' . hash('sha256', implode('|', [
            $user->id,
            $date?->format('Y-m-d') ?? '',
            $type ?? '',
            $description,
            number_format($amount, 2, '.', ''),
            $line,
        ]));
        $duplicate = Movement::where('user_id', $user->id)->where('import_key', $importKey)->exists();

        if ($duplicate) {
            $warnings[] = 'Ya existe un movimiento importado con esta llave.';
        }

        return [
            'line' => $line,
            'valid' => $errors === [],
            'duplicate' => $duplicate,
            'errors' => $errors,
            'warnings' => $warnings,
            'happened_on' => $date?->format('Y-m-d'),
            'movement_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'account_id' => $account?->id,
            'account_name' => $accountName,
            'category_id' => $category?->id,
            'category_name' => $categoryName,
            'person_id' => $person?->id,
            'person_name' => $personName,
            'notes' => trim($this->value($raw, $headerMap, 'notas')) ?: null,
            'is_san_juan' => $this->parseBool($this->value($raw, $headerMap, 'san_juan')),
            'is_rent' => $this->parseBool($this->value($raw, $headerMap, 'renta')),
            'is_unknown' => $this->parseBool($this->value($raw, $headerMap, 'desconocido')),
            'conciliation_difference' => $conciliation,
            'import_key' => $importKey,
        ];
    }

    private function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = Str::of($value)->ascii()->lower()->trim()->replace([' ', '-', '.'], '_')->toString();

        return match ($value) {
            'descripcion', 'description', 'concepto' => 'descripcion',
            'categoria', 'category' => 'categoria',
            'persona', 'person' => 'persona',
            'cuenta', 'account' => 'cuenta',
            'fecha', 'date' => 'fecha',
            'tipo', 'type' => 'tipo',
            'monto', 'amount' => 'monto',
            'notas', 'notes' => 'notas',
            'san_juan', 'snj' => 'san_juan',
            'renta', 'rent' => 'renta',
            'desconocido', 'unknown', '?' => 'desconocido',
            'diferencia_conciliacion', 'resta_corte' => 'diferencia_conciliacion',
            default => $value,
        };
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Throwable) {
            }
        }

        try {
            return $value !== '' ? Carbon::parse($value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseType(string $value): ?string
    {
        return match (Str::of($value)->ascii()->lower()->trim()->toString()) {
            'ingreso', 'income', 'renta' => 'income',
            'egreso', 'gasto', 'expense' => 'expense',
            'rendimiento', 'yield' => 'yield',
            'transferencia', 'transfer' => 'transfer',
            'ajuste', 'adjustment' => 'adjustment',
            default => null,
        };
    }

    private function parseAmount(string $value, bool $nullable = false): ?float
    {
        $value = trim(str_replace(['$', ' '], '', $value));

        if ($value === '') {
            return $nullable ? null : 0.0;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace(',', '', $value);
        } elseif (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        $value = preg_replace('/[^0-9.\-]/', '', $value) ?? '';

        return is_numeric($value) ? round((float) $value, 2) : ($nullable ? null : 0.0);
    }

    private function parseBool(string $value): bool
    {
        return in_array(Str::of($value)->ascii()->lower()->trim()->toString(), ['1', 'si', 'sí', 'yes', 'true', 'x'], true);
    }

    /**
     * @param array<int, string|null> $raw
     * @param array<string, int> $headerMap
     */
    private function value(array $raw, array $headerMap, string $column): string
    {
        $index = $headerMap[$column] ?? null;

        return $index === null ? '' : trim((string) ($raw[$index] ?? ''));
    }

    /**
     * @param array<int, string|null> $raw
     */
    private function isEmptyRow(array $raw): bool
    {
        return trim(implode('', array_map(fn ($value) => (string) $value, $raw))) === '';
    }

    private function findByName(string $model, User $user, string $name)
    {
        if ($name === '') {
            return null;
        }

        return $model::where('user_id', $user->id)->where('name', $name)->first();
    }

    private function safeUserId(string $model, User $user, mixed $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $model::where('user_id', $user->id)->whereKey($id)->value('id');
    }
}
