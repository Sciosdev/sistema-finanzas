<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\Movement;
use App\Services\Finance\FinanceCsvExportService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceSummaryService;
use App\Services\Finance\MovementClassificationSuggestionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MovementController extends Controller
{
    use PreparesFinanceData;

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceSummaryService $summaryService,
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
        private readonly FinanceCsvExportService $csvExports,
        private readonly MovementClassificationSuggestionService $suggestions,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($request->query('month', now()->format('Y-m')));
        $requestedPerPage = $request->query('per_page', 30);
        $perPage = is_numeric($requestedPerPage) ? (int) $requestedPerPage : 30;
        $perPage = max(10, min(500, $perPage));

        $movements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->when($request->query('type'), fn ($query, $type) => $query->where('movement_type', $type))
            ->when(trim((string) $request->query('q')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $amount = is_numeric(str_replace([',', '$'], '', $search))
                    ? round((float) str_replace([',', '$'], '', $search), 2)
                    : null;

                $query->where(function ($inner) use ($like, $amount) {
                    $inner->where('description', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('account', fn ($account) => $account->where('name', 'like', $like))
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $like)->orWhere('group', 'like', $like))
                        ->orWhereHas('person', fn ($person) => $person->where('name', 'like', $like)->orWhere('alias', 'like', $like));

                    if ($amount !== null) {
                        $inner->orWhereBetween('amount', [$amount - 0.01, $amount + 0.01]);
                    }
                });
            })
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('finance.movements.index', [
            'movements' => $movements,
            'monthValue' => $start->format('Y-m'),
            'perPage' => $perPage,
            'accounts' => $this->accountsFor($user),
            'categories' => $this->categoriesFor($user),
            'people' => $this->peopleFor($user),
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        [$start, $end] = $this->summaryService->monthRange($request->query('month', now()->format('Y-m')));

        $movements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->when($request->query('type'), fn ($query, $type) => $query->where('movement_type', $type))
            ->when(trim((string) $request->query('q')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $amount = is_numeric(str_replace([',', '$'], '', $search))
                    ? round((float) str_replace([',', '$'], '', $search), 2)
                    : null;

                $query->where(function ($inner) use ($like, $amount) {
                    $inner->where('description', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('account', fn ($account) => $account->where('name', 'like', $like))
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $like)->orWhere('group', 'like', $like))
                        ->orWhereHas('person', fn ($person) => $person->where('name', 'like', $like)->orWhere('alias', 'like', $like));

                    if ($amount !== null) {
                        $inner->orWhereBetween('amount', [$amount - 0.01, $amount + 0.01]);
                    }
                });
            })
            ->orderBy('happened_on')
            ->orderBy('id')
            ->get();

        $metadata = [
            'Reporte' => 'Movimientos',
            'Mes' => $start->format('Y-m'),
            'Tipo' => $request->query('type') ?: 'Todos',
            'Búsqueda' => $request->query('q') ?: 'Sin búsqueda',
        ];
        $format = $request->query('format') === 'xlsx' ? 'xlsx' : 'csv';
        $result = $format === 'xlsx'
            ? $this->csvExports->exportMovementsXlsx('movimientos-' . $start->format('Y-m'), $movements, $metadata)
            : $this->csvExports->exportMovements('movimientos-' . $start->format('Y-m'), $movements, $metadata);

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No se pudo exportar movimientos.');
        }

        return response()->download($result['absolute_path'], $result['name'], [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $this->validateMovement($request);

        $flags = $this->classifyFlags($user, $request->all());

        Movement::create($data + $flags + [
            'user_id' => $user->id,
            'source' => 'manual',
        ]);

        return back()->with('success', 'Movimiento guardado.');
    }

    public function edit(Request $request, Movement $movement)
    {
        abort_unless($movement->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $monthValue = $request->query('month', $movement->happened_on->format('Y-m'));
        $returnTo = $request->filled('return_to')
            ? $this->safeMovementsReturnTo($request, (string) $request->query('return_to'))
            : route('finance.movements.index', ['month' => $monthValue]);

        return view('finance.movements.edit', [
            'movement' => $movement->load(['account', 'category', 'person']),
            'monthValue' => $monthValue,
            'returnTo' => $returnTo,
            'accounts' => $this->accountsFor($user),
            'categories' => $this->categoriesFor($user),
            'people' => $this->peopleFor($user),
        ]);
    }

    public function update(Request $request, Movement $movement)
    {
        abort_unless($movement->user_id === $request->user()->id, 403);

        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $data = $this->validateMovement($request);
        $flags = $this->classifyFlags($user, $request->all());

        $movement->update($data + $flags);

        // Regresa al mismo contexto (página/filtros) si el formulario trae un
        // return_to interno seguro; si no, mantiene el comportamiento normal.
        $returnTo = $request->filled('return_to')
            ? $this->safeMovementsReturnTo($request, (string) $request->input('return_to'))
            : route('finance.movements.index', ['month' => Carbon::parse($data['happened_on'])->format('Y-m')]);

        return redirect($returnTo)->with('success', 'Movimiento actualizado.');
    }

    public function destroy(Request $request, Movement $movement)
    {
        abort_unless($movement->user_id === $request->user()->id, 403);

        $snapshot = DB::transaction(function () use ($request, $movement) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $movement, 'movement');
            $movement->delete();

            return $snapshot;
        });

        return back()
            ->with('success', 'Movimiento eliminado.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at->toDateTimeString(),
            ]);
    }

    public function bulkUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
            'movement_type' => ['nullable', 'in:income,expense,yield,transfer,adjustment'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'person_id' => ['nullable', 'integer', Rule::exists('finance_people', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'is_unknown' => ['nullable', 'in:0,1'],
            'is_san_juan' => ['nullable', 'in:0,1'],
            'is_rent' => ['nullable', 'in:0,1'],
            'return_to' => ['nullable', 'string'],
        ]);

        $redirectTo = $this->safeMovementsReturnTo($request, $validated['return_to'] ?? null);

        // Solo los IDs que pertenecen al usuario; cualquier otro se ignora.
        $ids = collect($validated['ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect($redirectTo)->with('error', 'No seleccionaste movimientos para actualizar.');
        }

        // Solo se cambian los campos que el usuario eligió explícitamente.
        // Un valor vacío / "No cambiar" no sobrescribe nada.
        $updates = [];

        if (filled($validated['movement_type'] ?? null)) {
            $updates['movement_type'] = $validated['movement_type'];
        }
        if (filled($validated['account_id'] ?? null)) {
            $updates['account_id'] = (int) $validated['account_id'];
        }
        if (filled($validated['category_id'] ?? null)) {
            $updates['category_id'] = (int) $validated['category_id'];
        }
        if (filled($validated['person_id'] ?? null)) {
            $updates['person_id'] = (int) $validated['person_id'];
        }
        foreach (['is_unknown', 'is_san_juan', 'is_rent'] as $flag) {
            if (($validated[$flag] ?? null) !== null && $validated[$flag] !== '') {
                $updates[$flag] = (bool) ((int) $validated[$flag]);
            }
        }

        if ($updates === []) {
            return redirect($redirectTo)->with('error', 'No elegiste ningún cambio para aplicar.');
        }

        $query = Movement::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $ids->all());

        $matched = (clone $query)->count();

        if ($matched === 0) {
            return redirect($redirectTo)->with('error', 'No se encontraron movimientos tuyos en la selección.');
        }

        DB::transaction(fn () => $query->update($updates));

        return redirect($redirectTo)->with('success', "Se actualizaron {$matched} movimiento(s).");
    }

    /**
     * Devuelve una ruta interna segura (relativa, dentro de movimientos) para
     * conservar filtros/página, evitando open redirects a sitios externos.
     */
    private function safeMovementsReturnTo(Request $request, ?string $returnTo): string
    {
        $fallback = route('finance.movements.index');

        $returnTo = trim((string) $returnTo);
        if ($returnTo === '') {
            return $fallback;
        }

        $parts = parse_url($returnTo);
        if ($parts === false) {
            return $fallback;
        }

        // Rechaza destinos externos: cualquier host distinto al actual
        // (acepta con o sin puerto, p. ej. el fullUrl() que envía la vista).
        if (isset($parts['host'])) {
            $allowedHosts = [$request->getHost(), $request->getHttpHost()];
            if (! in_array($parts['host'], $allowedHosts, true)) {
                return $fallback;
            }
        }

        $path = $parts['path'] ?? '';
        $movementsPath = parse_url($fallback, PHP_URL_PATH) ?: '/finanzas/movimientos';

        if (! str_starts_with($path, $movementsPath)) {
            return $fallback;
        }

        // Reconstruye solo path + query (descarta esquema/host) para forzar mismo origen.
        return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    public function suggestions(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        [$start, $end] = $this->summaryService->monthRange($request->query('month', now()->format('Y-m')));
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(10, min(500, $perPage));

        $movements = Movement::with(['account', 'category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->when($request->query('type'), fn ($query, $type) => $query->where('movement_type', $type))
            ->when($request->boolean('only_without_category'), fn ($query) => $query->whereNull('category_id'))
            ->when($request->boolean('only_unknown'), fn ($query) => $query->where('is_unknown', true))
            ->when(trim((string) $request->query('q')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('description', 'like', $like)->orWhere('notes', 'like', $like);
                });
            })
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $suggestions = $this->suggestions->suggest($user, $movements->getCollection());

        return view('finance.movements.suggestions', [
            'movements' => $movements,
            'suggestions' => $suggestions,
            'monthValue' => $start->format('Y-m'),
            'perPage' => $perPage,
            'filters' => [
                'q' => $request->query('q'),
                'type' => $request->query('type'),
                'confidence' => $request->query('confidence'),
                'only_without_category' => $request->boolean('only_without_category'),
                'only_unknown' => $request->boolean('only_unknown'),
                'only_with_suggestion' => $request->boolean('only_with_suggestion'),
            ],
            'returnTo' => $request->fullUrl(),
        ]);
    }

    public function applySuggestions(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
            'apply_category' => ['nullable', 'in:0,1'],
            'apply_person' => ['nullable', 'in:0,1'],
            'apply_account' => ['nullable', 'in:0,1'],
            'apply_flags' => ['nullable', 'in:0,1'],
            'return_to' => ['nullable', 'string'],
        ]);

        $redirectTo = $this->safeMovementsReturnTo($request, $validated['return_to'] ?? null);

        $ids = collect($validated['ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect($redirectTo)->with('error', 'No seleccionaste movimientos para aplicar sugerencias.');
        }

        $applyCategory = (bool) ((int) ($validated['apply_category'] ?? 0));
        $applyPerson = (bool) ((int) ($validated['apply_person'] ?? 0));
        $applyAccount = (bool) ((int) ($validated['apply_account'] ?? 0));
        $applyFlags = (bool) ((int) ($validated['apply_flags'] ?? 0));

        if (! ($applyCategory || $applyPerson || $applyAccount || $applyFlags)) {
            return redirect($redirectTo)->with('error', 'No elegiste qué sugerencias aplicar.');
        }

        // Solo movimientos del usuario; cualquier id ajeno se ignora.
        $movements = Movement::where('user_id', $user->id)
            ->whereIn('id', $ids->all())
            ->get();

        if ($movements->isEmpty()) {
            return redirect($redirectTo)->with('error', 'No se encontraron movimientos tuyos en la selección.');
        }

        // Se recalculan las sugerencias en el servidor: nunca se confía en
        // valores enviados por el cliente, solo en catálogos existentes.
        $suggestions = $this->suggestions->suggest($user, $movements);

        $updatedCount = DB::transaction(function () use ($movements, $suggestions, $applyCategory, $applyPerson, $applyAccount, $applyFlags) {
            $count = 0;

            foreach ($movements as $movement) {
                $suggestion = $suggestions[$movement->id] ?? null;
                if (! $suggestion) {
                    continue;
                }

                $changes = [];

                if ($applyCategory && ! empty($suggestion['category'])) {
                    $changes['category_id'] = $suggestion['category']['id'];
                }
                if ($applyPerson && ! empty($suggestion['person'])) {
                    $changes['person_id'] = $suggestion['person']['id'];
                }
                if ($applyAccount && ! empty($suggestion['account'])) {
                    $changes['account_id'] = $suggestion['account']['id'];
                }
                if ($applyFlags && ! empty($suggestion['flags'])) {
                    foreach ($suggestion['flags'] as $flag => $value) {
                        $changes[$flag] = (bool) $value;
                    }
                }

                if ($changes !== []) {
                    $movement->update($changes);
                    $count++;
                }
            }

            return $count;
        });

        if ($updatedCount === 0) {
            return redirect($redirectTo)->with('error', 'Los movimientos seleccionados no tenían sugerencias aplicables.');
        }

        return redirect($redirectTo)->with('success', "Se actualizaron {$updatedCount} movimiento(s) con sus sugerencias.");
    }

    private function validateMovement(Request $request): array
    {
        $user = $request->user();

        return $request->validate([
            'happened_on' => ['required', 'date'],
            'movement_type' => ['required', 'in:income,expense,yield,transfer,adjustment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'person_id' => ['nullable', 'integer', Rule::exists('finance_people', 'id')->where(fn ($query) => $query->where('user_id', $user->id))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
