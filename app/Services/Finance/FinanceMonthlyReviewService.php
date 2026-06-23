<?php

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceMonthlyReviewService
{
    /**
     * @return array<string, mixed>
     */
    public function review(User $user, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $movements = Movement::with(['category', 'person'])
            ->where('user_id', $user->id)
            ->whereBetween('happened_on', [$start->toDateString(), $end->toDateString()])
            ->orderBy('happened_on')
            ->orderBy('id')
            ->get();

        $categories = Category::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $people = Person::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suggestions = collect()
            ->merge($this->missingCategorySuggestions($movements, $categories))
            ->merge($this->missingPersonSuggestions($movements, $people))
            ->merge($this->descriptionCaseSuggestions($movements))
            ->merge($this->descriptionCleanupSuggestions($movements))
            ->merge($this->similarCategorySuggestions($categories))
            ->values()
            ->all();

        return [
            'month' => $start,
            'movements_count' => $movements->count(),
            'suggestions' => $suggestions,
            'applyable_count' => collect($suggestions)->where('applyable', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(User $user, Carbon $month, string $key): array
    {
        $suggestion = collect($this->review($user, $month)['suggestions'])
            ->firstWhere('key', $key);

        if (! $suggestion) {
            return [
                'ok' => false,
                'message' => 'La sugerencia ya no existe para este mes.',
            ];
        }

        if (! ($suggestion['applyable'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Esta sugerencia requiere revisión manual y no se aplica automáticamente.',
            ];
        }

        $ids = $suggestion['movement_ids'] ?? [];

        if ($ids === []) {
            return [
                'ok' => false,
                'message' => 'La sugerencia no tiene movimientos para actualizar.',
            ];
        }

        $query = Movement::where('user_id', $user->id)->whereIn('id', $ids);

        $updated = match ($suggestion['type']) {
            'category_missing' => $query->whereNull('category_id')->update([
                'category_id' => $suggestion['category_id'],
            ]),
            'person_missing' => $query->whereNull('person_id')->update([
                'person_id' => $suggestion['person_id'],
            ]),
            'description_case', 'description_cleanup' => $query->update([
                'description' => $suggestion['suggestion'],
            ]),
            default => 0,
        };

        return [
            'ok' => $updated > 0,
            'message' => $updated > 0
                ? "Corrección aplicada a {$updated} movimiento(s)."
                : 'No hubo movimientos para actualizar.',
        ];
    }

    /**
     * @param Collection<int, Movement> $movements
     * @param Collection<int, Category> $categories
     * @return array<int, array<string, mixed>>
     */
    private function missingCategorySuggestions(Collection $movements, Collection $categories): array
    {
        return $movements
            ->whereNull('category_id')
            ->mapToGroups(function (Movement $movement) use ($categories) {
                $category = $this->bestCategoryFor($movement, $categories);

                return $category
                    ? [$category->id => $movement]
                    : [];
            })
            ->map(function (Collection $group, int|string $categoryId) use ($categories) {
                $categoryId = (int) $categoryId;
                $category = $categories->firstWhere('id', $categoryId);

                return $this->suggestion(
                    'category_missing',
                    'Categoría sugerida',
                    'Sin categoría',
                    $category?->name ?? 'Categoría',
                    'La descripción contiene palabras clave de esa categoría.',
                    $group,
                    true,
                    ['category_id' => $categoryId]
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Movement> $movements
     * @param Collection<int, Person> $people
     * @return array<int, array<string, mixed>>
     */
    private function missingPersonSuggestions(Collection $movements, Collection $people): array
    {
        return $movements
            ->whereNull('person_id')
            ->mapToGroups(function (Movement $movement) use ($people) {
                $person = $this->bestPersonFor($movement, $people);

                return $person
                    ? [$person->id => $movement]
                    : [];
            })
            ->map(function (Collection $group, int|string $personId) use ($people) {
                $personId = (int) $personId;
                $person = $people->firstWhere('id', $personId);

                return $this->suggestion(
                    'person_missing',
                    'Persona sugerida',
                    'Sin persona',
                    $person?->name ?? 'Persona',
                    'La descripción menciona a esta persona.',
                    $group,
                    true,
                    ['person_id' => $personId]
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Movement> $movements
     * @return array<int, array<string, mixed>>
     */
    private function descriptionCaseSuggestions(Collection $movements): array
    {
        return $movements
            ->filter(fn (Movement $movement) => trim($movement->description) !== '')
            ->groupBy(fn (Movement $movement) => $this->textKey($movement->description))
            ->filter(fn (Collection $group) => $group->pluck('description')->unique()->count() > 1)
            ->map(function (Collection $group) {
                $suggestion = $group
                    ->pluck('description')
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();

                return $this->suggestion(
                    'description_case',
                    'Texto inconsistente',
                    $group->pluck('description')->unique()->implode(' / '),
                    $suggestion,
                    'Hay el mismo concepto escrito con mayúsculas/minúsculas distintas.',
                    $group,
                    true
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Movement> $movements
     * @return array<int, array<string, mixed>>
     */
    private function descriptionCleanupSuggestions(Collection $movements): array
    {
        return $movements
            ->filter(fn (Movement $movement) => $movement->description !== trim(preg_replace('/\s+/', ' ', $movement->description) ?? $movement->description))
            ->map(function (Movement $movement) {
                $clean = trim(preg_replace('/\s+/', ' ', $movement->description) ?? $movement->description);

                return $this->suggestion(
                    'description_cleanup',
                    'Limpiar texto',
                    $movement->description,
                    $clean,
                    'La descripción tiene espacios duplicados o al inicio/final.',
                    collect([$movement]),
                    true
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Category> $categories
     * @return array<int, array<string, mixed>>
     */
    private function similarCategorySuggestions(Collection $categories): array
    {
        return $categories
            ->groupBy(fn (Category $category) => $this->categoryKey($category->name, $category->type))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(function (Collection $group) {
                return [
                    'key' => $this->key('similar_categories', $group->pluck('id')->all(), $group->pluck('name')->implode('|')),
                    'type' => 'similar_categories',
                    'title' => 'Categorías parecidas',
                    'current' => $group->pluck('name')->implode(' / '),
                    'suggestion' => 'Revisar y unificar manualmente en Categorías',
                    'reason' => 'Son nombres muy parecidos. Por seguridad no se unifican automáticamente.',
                    'count' => $group->count(),
                    'movement_ids' => [],
                    'applyable' => false,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Movement> $movements
     * @return array<string, mixed>
     */
    private function suggestion(
        string $type,
        string $title,
        string $current,
        ?string $suggestion,
        string $reason,
        Collection $movements,
        bool $applyable,
        array $extra = []
    ): array {
        $ids = $movements->pluck('id')->sort()->values()->all();

        return [
            'key' => $this->key($type, $ids, (string) $suggestion),
            'type' => $type,
            'title' => $title,
            'current' => $current,
            'suggestion' => $suggestion,
            'reason' => $reason,
            'count' => $movements->count(),
            'movement_ids' => $ids,
            'applyable' => $applyable,
        ] + $extra;
    }

    /**
     * @param Collection<int, Category> $categories
     */
    private function bestCategoryFor(Movement $movement, Collection $categories): ?Category
    {
        $description = $this->textKey($movement->description);

        return $categories->first(function (Category $category) use ($description) {
            $terms = collect(explode(',', (string) $category->keywords))
                ->push($category->name)
                ->map(fn (string $term) => $this->textKey($term))
                ->filter(fn (string $term) => $term !== '' && mb_strlen($term) >= 3)
                ->unique();

            return $terms->contains(fn (string $term) => str_contains($description, $term));
        });
    }

    /**
     * @param Collection<int, Person> $people
     */
    private function bestPersonFor(Movement $movement, Collection $people): ?Person
    {
        $description = $this->textKey($movement->description);

        return $people->first(function (Person $person) use ($description) {
            $terms = collect([$person->name, $person->alias])
                ->map(fn (?string $term) => $this->textKey((string) $term))
                ->filter(fn (string $term) => $term !== '' && mb_strlen($term) >= 3);

            return $terms->contains(fn (string $term) => str_contains($description, $term));
        });
    }

    private function textKey(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function categoryKey(string $name, string $type): string
    {
        $name = preg_replace('/\b(de|del|la|el|los|las)\b/', '', $this->textKey($name)) ?? $name;
        $name = preg_replace('/s\b/', '', $name) ?? $name;

        return $type . ':' . trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    /**
     * @param array<int, int> $ids
     */
    private function key(string $type, array $ids, string $suggestion): string
    {
        return hash('sha256', $type . '|' . implode(',', $ids) . '|' . $suggestion);
    }
}
