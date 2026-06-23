<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\PreparesFinanceData;
use App\Models\Finance\Movement;
use App\Services\Finance\FinanceCsvExportService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceSummaryService;
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

        return view('finance.movements.edit', [
            'movement' => $movement->load(['account', 'category', 'person']),
            'monthValue' => $request->query('month', $movement->happened_on->format('Y-m')),
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

        return redirect()
            ->route('finance.movements.index', ['month' => Carbon::parse($data['happened_on'])->format('Y-m')])
            ->with('success', 'Movimiento actualizado.');
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
