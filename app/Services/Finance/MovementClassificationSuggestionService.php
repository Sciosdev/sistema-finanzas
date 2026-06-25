<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Motor dinámico de sugerencias de clasificación de movimientos por
 * concepto/descripción.
 *
 * NO inventa catálogos: solo puede sugerir categorías, personas y cuentas que
 * ya existen para el usuario. NO modifica datos ni cálculos financieros: solo
 * analiza texto y devuelve sugerencias con confianza y razón.
 *
 * Fuentes de coincidencia (de mayor a menor confianza):
 *  - Nombre de categoría / persona / alias / cuenta (alta).
 *  - Palabras clave propias de la categoría (alta).
 *  - Grupo de la categoría (media).
 *  - Léxico interno de palabras relacionadas, resuelto SIEMPRE hacia una
 *    categoría existente del usuario (media).
 *  - Historial: patrones de movimientos ya clasificados del propio usuario (baja).
 */
class MovementClassificationSuggestionService
{
    /**
     * Palabras relacionadas agrupadas por un término "ancla". El ancla se
     * resuelve dinámicamente hacia una categoría existente del usuario (por
     * nombre, grupo o palabra clave). Si el ancla no existe como categoría del
     * usuario, ese grupo simplemente no aplica (no se inventa nada).
     *
     * @var array<string, list<string>>
     */
    private const RELATED_WORDS = [
        'comida' => ['hamburguesa', 'hamburguesas', 'pizza', 'pizzas', 'taco', 'tacos', 'torta', 'tortas', 'sushi', 'pollo', 'restaurante', 'restaurant', 'cena', 'desayuno', 'almuerzo', 'espagueti', 'espaguetis', 'burrito', 'antojitos', 'lonche', 'quesadilla', 'mariscos', 'hotdog', 'hot dog'],
        'gasolina' => ['magna', 'premium', 'pemex', 'shell', 'gasolinera', 'combustible', 'mobil'],
        'telefonia' => ['saldo', 'recarga', 'telcel', 'movistar', 'unefon', 'bait', 'paquete', 'datos', 'celular', 'telefono', 'weex'],
        'farmacia' => ['farmacia', 'medicamento', 'medicina', 'san pablo', 'similares', 'benavides'],
        'transporte' => ['caseta', 'casetas', 'peaje', 'metro', 'metrobus', 'autobus', 'camion', 'pasaje', 'combi'],
        'tienda' => ['oxxo', 'seven eleven', 'abarrotes', 'minisuper', 'tienda', 'kiosko'],
        'super' => ['walmart', 'soriana', 'chedraui', 'aurrera', 'heb', 'costco', 'sams', 'mercado'],
    ];

    private const CONFIDENCE_HIGH = 'alta';
    private const CONFIDENCE_MEDIUM = 'media';
    private const CONFIDENCE_LOW = 'baja';

    /**
     * Devuelve sugerencias indexadas por id de movimiento.
     *
     * @param iterable<int, Movement> $movements
     * @return array<int, array<string, mixed>>
     */
    public function suggest(User $user, iterable $movements): array
    {
        $context = $this->buildContext($user);

        $suggestions = [];
        foreach ($movements as $movement) {
            $suggestions[$movement->id] = $this->suggestForMovement($movement, $context);
        }

        return $suggestions;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function suggestForMovement(Movement $movement, array $context): array
    {
        $text = $this->normalize((string) $movement->description);

        $category = $this->suggestCategory($text, $context);
        $person = $this->suggestPerson($text, $context);
        $account = $this->suggestAccount($text, $context);

        $flags = [];
        if ($category) {
            /** @var Category $model */
            $model = $context['categoriesById'][$category['id']];
            if ($model->is_san_juan) {
                $flags['is_san_juan'] = true;
            }
            if ($model->is_rent) {
                $flags['is_rent'] = true;
            }
        }

        return [
            'category' => $category,
            'person' => $person,
            'account' => $account,
            'flags' => $flags,
            'has_any' => (bool) ($category || $person || $account),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    private function suggestCategory(string $text, array $context): ?array
    {
        if ($text === '') {
            return null;
        }

        /** @var array{category: Category, score: int, term: string, type: string}|null $best */
        $best = null;

        $consider = function (?Category $category, int $score, string $term, string $type) use (&$best): void {
            if (! $category) {
                return;
            }

            if ($best === null
                || $score > $best['score']
                || ($score === $best['score'] && mb_strlen($term) > mb_strlen($best['term']))
                || ($score === $best['score'] && mb_strlen($term) === mb_strlen($best['term']) && mb_strlen($category->name) < mb_strlen($best['category']->name))
            ) {
                $best = ['category' => $category, 'score' => $score, 'term' => $term, 'type' => $type];
            }
        };

        /** @var Category $category */
        foreach ($context['categories'] as $category) {
            if ($this->containsTerm($text, $category->name)) {
                $consider($category, 100, $this->normalize($category->name), 'name');
            }

            foreach ($this->splitKeywords($category->keywords) as $keyword) {
                if ($this->containsTerm($text, $keyword)) {
                    $consider($category, 90, $keyword, 'keyword');
                }
            }

            if (filled($category->group) && $this->containsTerm($text, (string) $category->group)) {
                $consider($category, 70, $this->normalize((string) $category->group), 'group');
            }
        }

        // Léxico interno -> categoría existente.
        foreach (self::RELATED_WORDS as $anchor => $words) {
            $category = $context['anchorCategories'][$anchor] ?? null;
            if (! $category) {
                continue;
            }

            foreach ($words as $word) {
                if ($this->containsTerm($text, $word)) {
                    $consider($category, 60, $this->normalize($word), 'related');
                    break;
                }
            }
        }

        // Historial del usuario.
        $history = $this->historyCategory($text, $context);
        if ($history) {
            $consider($history['category'], 40, $history['token'], 'history');
        }

        if ($best === null) {
            return null;
        }

        return [
            'id' => $best['category']->id,
            'name' => $best['category']->name,
            'confidence' => $this->confidenceFromScore($best['score']),
            'reason' => $this->categoryReason($best['type'], $best['term'], $best['category']->name),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    private function suggestPerson(string $text, array $context): ?array
    {
        if ($text === '') {
            return null;
        }

        /** @var array{person: Person, score: int, term: string, type: string}|null $best */
        $best = null;

        $consider = function (Person $person, int $score, string $term, string $type) use (&$best): void {
            if ($best === null || $score > $best['score'] || ($score === $best['score'] && mb_strlen($term) > mb_strlen($best['term']))) {
                $best = ['person' => $person, 'score' => $score, 'term' => $term, 'type' => $type];
            }
        };

        /** @var Person $person */
        foreach ($context['people'] as $person) {
            if ($this->containsTerm($text, $person->name)) {
                $consider($person, 100, $this->normalize($person->name), 'name');
            }

            if (filled($person->alias) && $this->containsTerm($text, (string) $person->alias)) {
                $consider($person, 95, $this->normalize((string) $person->alias), 'alias');
            }
        }

        $history = $this->historyPerson($text, $context);
        if ($history) {
            $consider($history['person'], 40, $history['token'], 'history');
        }

        if ($best === null) {
            return null;
        }

        return [
            'id' => $best['person']->id,
            'name' => $best['person']->name,
            'confidence' => $this->confidenceFromScore($best['score']),
            'reason' => $this->personReason($best['type'], $best['term'], $best['person']->name),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    private function suggestAccount(string $text, array $context): ?array
    {
        if ($text === '') {
            return null;
        }

        /** @var Account $account */
        foreach ($context['accounts'] as $account) {
            $name = $this->normalize((string) $account->name);

            // Cuentas con nombres muy cortos se omiten para evitar falsos positivos.
            if (mb_strlen($name) < 3) {
                continue;
            }

            if ($this->containsTerm($text, (string) $account->name)) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'confidence' => self::CONFIDENCE_LOW,
                    'reason' => "Contiene «{$name}», coincide con la cuenta {$account->name}.",
                ];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     * @return array{category: Category, token: string}|null
     */
    private function historyCategory(string $text, array $context): ?array
    {
        return $this->historyMatch($text, $context['historyCategory'], $context['categoriesById'], 'category');
    }

    /**
     * @param array<string, mixed> $context
     * @return array{person: Person, token: string}|null
     */
    private function historyPerson(string $text, array $context): ?array
    {
        return $this->historyMatch($text, $context['historyPerson'], $context['peopleById'], 'person');
    }

    /**
     * @param array<string, array<int, int>> $tokenMap
     * @param array<int, Category|Person> $modelsById
     * @return array<string, mixed>|null
     */
    private function historyMatch(string $text, array $tokenMap, array $modelsById, string $key): ?array
    {
        $tokens = array_unique(array_filter(explode(' ', $text), fn ($token) => mb_strlen($token) >= 3));

        $bestToken = null;
        $bestModelId = null;
        $bestCount = 0;

        foreach ($tokens as $token) {
            if (! isset($tokenMap[$token])) {
                continue;
            }

            foreach ($tokenMap[$token] as $modelId => $count) {
                // Se requieren al menos 2 ocurrencias previas para considerarlo patrón.
                if ($count >= 2 && $count > $bestCount && isset($modelsById[$modelId])) {
                    $bestCount = $count;
                    $bestModelId = $modelId;
                    $bestToken = $token;
                }
            }
        }

        if ($bestModelId === null) {
            return null;
        }

        return [$key => $modelsById[$bestModelId], 'token' => $bestToken];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(User $user): array
    {
        $categories = Category::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $people = Person::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $categoriesById = $categories->keyBy('id')->all();
        $peopleById = $people->keyBy('id')->all();

        return [
            'categories' => $categories,
            'people' => $people,
            'accounts' => $accounts,
            'categoriesById' => $categoriesById,
            'peopleById' => $peopleById,
            'anchorCategories' => $this->resolveAnchors($categories),
            'historyCategory' => $this->buildHistory($user, 'category_id'),
            'historyPerson' => $this->buildHistory($user, 'person_id'),
        ];
    }

    /**
     * Resuelve cada ancla del léxico hacia una categoría existente del usuario.
     *
     * @param Collection<int, Category> $categories
     * @return array<string, Category>
     */
    private function resolveAnchors(Collection $categories): array
    {
        $resolved = [];

        foreach (array_keys(self::RELATED_WORDS) as $anchor) {
            $byName = null;
            $byOther = null;

            foreach ($categories as $category) {
                if ($this->containsTerm($this->normalize((string) $category->name), $anchor)) {
                    if ($byName === null || mb_strlen($category->name) < mb_strlen($byName->name)) {
                        $byName = $category;
                    }
                    continue;
                }

                $matchesKeyword = collect($this->splitKeywords($category->keywords))
                    ->contains(fn (string $keyword) => $this->containsTerm($keyword, $anchor));
                $matchesGroup = filled($category->group) && $this->containsTerm($this->normalize((string) $category->group), $anchor);

                if (($matchesKeyword || $matchesGroup) && $byOther === null) {
                    $byOther = $category;
                }
            }

            $match = $byName ?? $byOther;
            if ($match) {
                $resolved[$anchor] = $match;
            }
        }

        return $resolved;
    }

    /**
     * Construye un mapa token => [modelId => conteo] de movimientos ya
     * clasificados del propio usuario.
     *
     * @return array<string, array<int, int>>
     */
    private function buildHistory(User $user, string $column): array
    {
        $rows = Movement::where('user_id', $user->id)
            ->whereNotNull($column)
            ->orderByDesc('id')
            ->limit(3000)
            ->get(['description', $column]);

        $map = [];

        foreach ($rows as $row) {
            $modelId = (int) $row->{$column};
            if ($modelId === 0) {
                continue;
            }

            $tokens = array_unique(explode(' ', $this->normalize((string) $row->description)));
            foreach ($tokens as $token) {
                if (mb_strlen($token) < 3) {
                    continue;
                }

                $map[$token][$modelId] = ($map[$token][$modelId] ?? 0) + 1;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function splitKeywords(?string $keywords): array
    {
        if (! filled($keywords)) {
            return [];
        }

        return collect(explode(',', $keywords))
            ->map(fn (string $keyword) => $this->normalize($keyword))
            ->filter(fn (string $keyword) => $keyword !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function confidenceFromScore(int $score): string
    {
        return match (true) {
            $score >= 90 => self::CONFIDENCE_HIGH,
            $score >= 60 => self::CONFIDENCE_MEDIUM,
            default => self::CONFIDENCE_LOW,
        };
    }

    private function categoryReason(string $type, string $term, string $name): string
    {
        return match ($type) {
            'name' => "Contiene «{$term}», coincide con la categoría {$name}.",
            'keyword' => "Contiene «{$term}», palabra clave de la categoría {$name}.",
            'group' => "Contiene «{$term}», grupo de la categoría {$name}.",
            'related' => "Contiene «{$term}», palabra relacionada con la categoría {$name}.",
            default => "Movimientos anteriores con «{$term}» suelen estar en la categoría {$name}.",
        };
    }

    private function personReason(string $type, string $term, string $name): string
    {
        return match ($type) {
            'name' => "Contiene «{$term}», coincide con la persona {$name}.",
            'alias' => "Contiene «{$term}», alias de la persona {$name}.",
            default => "Movimientos anteriores con «{$term}» suelen ser de {$name}.",
        };
    }

    /**
     * Normaliza texto: minúsculas, sin acentos, solo alfanumérico y espacios.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);

        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * Coincidencia por palabra/frase completa sobre el texto ya normalizado.
     */
    private function containsTerm(string $normalizedText, string $term): bool
    {
        $term = $this->normalize($term);

        if ($term === '' || $normalizedText === '') {
            return false;
        }

        return preg_match('/(^| )' . preg_quote($term, '/') . '( |$)/', $normalizedText) === 1;
    }
}
