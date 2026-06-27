<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Category;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceDeletionSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceDeletionSnapshotService $deleteSnapshots,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $categories = Category::where('user_id', $user->id)
            ->orderBy('group')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('finance.categories.index', [
            'categories' => $categories,
            'categoryGroups' => $this->categoryGroups($categories),
            'categorySuggestions' => $this->categorySuggestions($categories),
            'duplicateCategoryGroups' => $this->duplicateCategoryGroups($categories),
            'similarCategoryPairs' => $this->similarCategoryPairs($categories),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories', 'name')
                    ->where(fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('type', $request->input('type'))),
            ],
            'type' => ['required', 'in:income,expense,yield'],
            'group' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'keywords' => ['nullable', 'string'],
            'is_san_juan' => ['nullable', 'boolean'],
            'is_rent' => ['nullable', 'boolean'],
        ]);

        Category::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'group' => $data['group'] ?: null,
            'color' => $data['color'] ?: '#4d5761',
            'keywords' => $data['keywords'] ?? null,
            'is_san_juan' => (bool) ($data['is_san_juan'] ?? false),
            'is_rent' => (bool) ($data['is_rent'] ?? false),
            'is_active' => true,
        ]);

        return back()->with('success', 'Categoría agregada.');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $user = $request->user();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories', 'name')
                    ->where(fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('type', $request->input('type')))
                    ->ignore($category->id),
            ],
            'type' => ['required', 'in:income,expense,yield'],
            'group' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'keywords' => ['nullable', 'string'],
            'is_san_juan' => ['nullable', 'boolean'],
            'is_rent' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'group' => $data['group'] ?: null,
            'color' => $data['color'] ?: '#4d5761',
            'keywords' => $data['keywords'] ?? null,
            'is_san_juan' => (bool) ($data['is_san_juan'] ?? false),
            'is_rent' => (bool) ($data['is_rent'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        if ($this->categoryIsUsed($category)) {
            $snapshot = DB::transaction(function () use ($request, $category) {
                $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $category, 'category');
                $category->update(['is_active' => false]);

                return $snapshot;
            });

            return back()
                ->with('success', 'Categoría desactivada para conservar el historial.')
                ->with('undo_delete', [
                    'token' => $snapshot->token,
                    'label' => 'Deshacer',
                    'expires_at' => $snapshot->expires_at->toDateTimeString(),
                ]);
        }

        $snapshot = DB::transaction(function () use ($request, $category) {
            $snapshot = $this->deleteSnapshots->captureBeforeDelete($request->user(), $category, 'category');
            $category->delete();

            return $snapshot;
        });

        return back()
            ->with('success', 'Categoría eliminada.')
            ->with('undo_delete', [
                'token' => $snapshot->token,
                'label' => 'Deshacer',
                'expires_at' => $snapshot->expires_at->toDateTimeString(),
            ]);
    }

    /**
     * Paleta representativa por nombre de categoría (clave normalizada sin
     * espacios ni acentos). Si el nombre no está aquí, se usa el color por grupo.
     *
     * @var array<string, string>
     */
    private const COLOR_BY_NAME = [
        'rentassanjuan' => '#16a34a',
        'andreacomida' => '#14b8a6',
        'sciosfesi' => '#0d9488',
        'rendimientonu' => '#a855f7',
        'rendimientompw' => '#eab308',
        'comida' => '#f97316',
        'casa' => '#64748b',
        'transporte' => '#0ea5e9',
        'saldotelefonia' => '#06b6d4',
        'gasolina' => '#ef4444',
        'gasolinademoto' => '#f59e0b',
        'ubercarro' => '#3b82f6',
        'didicarro' => '#fb923c',
        'ubercomida' => '#fdba74',
        'didicomida' => '#fbbf24',
        'rappi' => '#e11d48',
        'sanjuangeneral' => '#dc2626',
        'japam' => '#0891b2',
        'limpiezajorge' => '#84cc16',
        'creditotarjeta' => '#7c3aed',
        'desconocido' => '#374151',
        'entretenimiento' => '#2563eb',
        'propinas' => '#f472b6',
        'ropa' => '#ec4899',
        'salud' => '#10b981',
        'servicios' => '#6366f1',
        'trabajo' => '#22c55e',
        'rendimientos' => '#eab308',
    ];

    /**
     * Color de respaldo por grupo (clave normalizada).
     *
     * @var array<string, string>
     */
    private const COLOR_BY_GROUP = [
        'casa' => '#64748b',
        'comida' => '#f97316',
        'creditos' => '#7c3aed',
        'diario' => '#22d3ee',
        'gasolina' => '#ef4444',
        'sanjuan' => '#dc2626',
        'transporte' => '#0ea5e9',
        'rendimientos' => '#eab308',
        'trabajo' => '#10b981',
        'revision' => '#374151',
        'servicios' => '#6366f1',
        'personal' => '#ec4899',
    ];

    public function applySuggestedColors(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $categories = Category::where('user_id', $user->id)->get();
        $updated = 0;

        DB::transaction(function () use ($categories, &$updated) {
            foreach ($categories as $category) {
                $color = $this->suggestedColorFor($category);

                if ($color !== null && strcasecmp((string) $category->color, $color) !== 0) {
                    $category->update(['color' => $color]);
                    $updated++;
                }
            }
        });

        return back()->with(
            'success',
            $updated > 0
                ? "Se aplicaron colores sugeridos a {$updated} categoría(s). Puedes ajustar cualquiera a mano."
                : 'Tus categorías ya tienen los colores sugeridos.'
        );
    }

    private function suggestedColorFor(Category $category): ?string
    {
        $name = $this->normalizedName($category->name);
        if (isset(self::COLOR_BY_NAME[$name])) {
            return self::COLOR_BY_NAME[$name];
        }

        $group = $this->normalizedName($category->group);
        if ($group !== '' && isset(self::COLOR_BY_GROUP[$group])) {
            return self::COLOR_BY_GROUP[$group];
        }

        return null;
    }

    public function merge(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $user = $request->user();
        $data = $request->validate([
            'source_category_ids' => ['required', 'array', 'min:1'],
            'source_category_ids.*' => ['integer'],
            'confirm_merge' => ['accepted'],
        ]);

        $sourceIds = collect($data['source_category_ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $category->id)
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            return back()->with('error', 'Selecciona al menos una categoría distinta para unificar.');
        }

        $sources = Category::where('user_id', $user->id)
            ->whereIn('id', $sourceIds)
            ->get();

        if ($sources->count() !== $sourceIds->count()) {
            return back()->with('error', 'No se pudo unificar porque una categoría origen no existe.');
        }

        if ($sources->contains(fn (Category $source) => $source->type !== $category->type)) {
            return back()->with('error', 'Solo se pueden unificar categorías del mismo tipo contable.');
        }

        DB::transaction(function () use ($user, $category, $sourceIds, $sources) {
            Movement::where('user_id', $user->id)
                ->whereIn('category_id', $sourceIds)
                ->update(['category_id' => $category->id]);

            PlannedPayment::where('user_id', $user->id)
                ->whereIn('category_id', $sourceIds)
                ->update(['category_id' => $category->id]);

            ExpectedIncome::where('user_id', $user->id)
                ->whereIn('category_id', $sourceIds)
                ->update(['category_id' => $category->id]);

            CreditPurchase::where('user_id', $user->id)
                ->whereIn('category_id', $sourceIds)
                ->update(['category_id' => $category->id]);

            $category->update([
                'is_active' => true,
                'keywords' => collect([$category->keywords])
                    ->merge($sources->pluck('keywords'))
                    ->filter()
                    ->implode(','),
            ]);

            Category::where('user_id', $user->id)
                ->whereIn('id', $sourceIds)
                ->update(['is_active' => false]);
        });

        return back()->with('success', 'Categorías unificadas. El historial ahora apunta a "' . $category->name . '" y las categorías origen quedaron inactivas.');
    }

    private function categoryIsUsed(Category $category): bool
    {
        return Movement::where('category_id', $category->id)->exists()
            || PlannedPayment::where('category_id', $category->id)->exists()
            || ExpectedIncome::where('category_id', $category->id)->exists()
            || CreditPurchase::where('category_id', $category->id)->exists();
    }

    private function categoryGroups(Collection $categories): Collection
    {
        return $categories
            ->groupBy(fn (Category $category) => ($category->type ?: 'expense') . ':' . ($category->group ?: 'Sin grupo'))
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'type' => $first->type,
                    'group' => $first->group ?: 'Sin grupo',
                    'count' => $rows->count(),
                    'active_count' => $rows->where('is_active', true)->count(),
                    'colors' => $rows
                        ->pluck('color')
                        ->filter()
                        ->unique()
                        ->take(5)
                        ->values(),
                ];
            })
            ->sortBy(fn (array $row) => $row['type'] . ':' . $row['group'])
            ->values();
    }

    private function categorySuggestions(Collection $categories): Collection
    {
        return collect([
            ['name' => 'Comida', 'type' => 'expense', 'group' => 'Comida', 'color' => '#f97316', 'keywords' => 'comida,taqueria,restaurant,uber eats,didi comida,rappi,oxxo'],
            ['name' => 'Saldo / Telefonía', 'type' => 'expense', 'group' => 'Servicios', 'color' => '#06b6d4', 'keywords' => 'saldo,telcel,weex,recarga,telefono,telefonia'],
            ['name' => 'Gasolina', 'type' => 'expense', 'group' => 'Transporte', 'color' => '#ef4444', 'keywords' => 'gasolina,costco gasolina,gasolina carro'],
            ['name' => 'Gasolina de moto', 'type' => 'expense', 'group' => 'Transporte', 'color' => '#f59e0b', 'keywords' => 'gasolina moto,gasolina de moto,moto'],
            ['name' => 'Ropa', 'type' => 'expense', 'group' => 'Personal', 'color' => '#ec4899', 'keywords' => 'ropa,zapato,playera,pantalon,tenis,shein,zara'],
            ['name' => 'Salud', 'type' => 'expense', 'group' => 'Personal', 'color' => '#14b8a6', 'keywords' => 'doctor,farmacia,medicina,salud,dentista'],
            ['name' => 'Servicios', 'type' => 'expense', 'group' => 'Casa', 'color' => '#6366f1', 'keywords' => 'luz,agua,internet,totalplay,telmex,google one,youtube,amazon music'],
            ['name' => 'Créditos / tarjetas', 'type' => 'expense', 'group' => 'Créditos', 'color' => '#7c3aed', 'keywords' => 'credito,creditos,tarjeta,nu credito,didi credito,mpw credito'],
            ['name' => 'San Juan general', 'type' => 'expense', 'group' => 'San Juan', 'color' => '#dc3545', 'keywords' => 'san juan,snj,japam,jorge,limpieza', 'is_san_juan' => true],
            ['name' => 'Trabajo', 'type' => 'income', 'group' => 'Trabajo', 'color' => '#22c55e', 'keywords' => 'trabajo,pago,scios,fesi,ittla'],
            ['name' => 'Rentas San Juan', 'type' => 'income', 'group' => 'San Juan', 'color' => '#22b956', 'keywords' => 'renta,rentas,cesar,alma,wendy,lazaro,josue,oswaldo', 'is_rent' => true],
            ['name' => 'Rendimientos', 'type' => 'yield', 'group' => 'Rendimientos', 'color' => '#eab308', 'keywords' => 'rendimiento,rendimientos,nu,mpw'],
        ])->map(function (array $suggestion) use ($categories) {
            $existing = $categories->first(fn (Category $category) => $category->type === $suggestion['type']
                && $this->normalizedName($category->name) === $this->normalizedName($suggestion['name']));

            return $suggestion + [
                'is_san_juan' => (bool) ($suggestion['is_san_juan'] ?? false),
                'is_rent' => (bool) ($suggestion['is_rent'] ?? false),
                'exists' => (bool) $existing,
                'existing_id' => $existing?->id,
            ];
        });
    }

    private function duplicateCategoryGroups(Collection $categories): Collection
    {
        return $categories
            ->groupBy(fn (Category $category) => $category->type . ':' . $this->normalizedName($category->name))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->values();
    }

    private function similarCategoryPairs(Collection $categories): Collection
    {
        $pairs = collect();
        $byType = $categories->groupBy('type');

        foreach ($byType as $typeCategories) {
            $values = $typeCategories->values();

            for ($leftIndex = 0; $leftIndex < $values->count(); $leftIndex++) {
                for ($rightIndex = $leftIndex + 1; $rightIndex < $values->count(); $rightIndex++) {
                    $left = $values[$leftIndex];
                    $right = $values[$rightIndex];
                    $leftName = $this->normalizedName($left->name);
                    $rightName = $this->normalizedName($right->name);

                    if ($leftName === '' || $rightName === '' || $leftName === $rightName) {
                        continue;
                    }

                    $distance = levenshtein($leftName, $rightName);
                    $contains = (str_contains($leftName, $rightName) || str_contains($rightName, $leftName))
                        && abs(strlen($leftName) - strlen($rightName)) <= 8;

                    if ($distance <= 3 || $contains) {
                        $pairs->push([
                            'left' => $left,
                            'right' => $right,
                            'reason' => $distance <= 3 ? 'Nombre muy parecido' : 'Un nombre contiene al otro',
                        ]);
                    }
                }
            }
        }

        return $pairs->take(12)->values();
    }

    private function normalizedName(?string $name): string
    {
        return (string) Str::of($name ?? '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '');
    }
}
