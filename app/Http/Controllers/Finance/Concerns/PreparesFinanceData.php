<?php

namespace App\Http\Controllers\Finance\Concerns;

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Person;
use App\Models\User;
use Illuminate\Support\Str;

trait PreparesFinanceData
{
    private function accountsFor(User $user)
    {
        return Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    private function categoriesFor(User $user, ?string $type = null)
    {
        return Category::where('user_id', $user->id)
            ->where('is_active', true)
            ->when($type, fn ($query) => $query->whereIn('type', [$type, 'yield']))
            ->orderBy('group')
            ->orderBy('name')
            ->get();
    }

    private function peopleFor(User $user)
    {
        return Person::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function findUserModel(string $model, User $user, mixed $id)
    {
        if (!$id) {
            return null;
        }

        return $model::where('user_id', $user->id)->find($id);
    }

    private function classifyFlags(User $user, array $data): array
    {
        $category = $this->findUserModel(Category::class, $user, $data['category_id'] ?? null);
        $person = $this->findUserModel(Person::class, $user, $data['person_id'] ?? null);
        $description = (string) ($data['description'] ?? $data['name'] ?? '');
        $text = Str::lower($description);

        $sanJuanKeywords = ['snj', 'san juan', 'japam', 'jorge', 'limpieza', 'cloro', 'jabon', 'escoba'];
        $rentKeywords = ['renta', 'rentas'];

        $isSanJuan = (bool) ($data['is_san_juan'] ?? false) || (bool) $category?->is_san_juan;
        $isRent = (bool) ($data['is_rent'] ?? false) || (bool) $category?->is_rent || (bool) $person?->is_tenant;

        foreach ($sanJuanKeywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $isSanJuan = true;
                break;
            }
        }

        foreach ($rentKeywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $isRent = true;
                break;
            }
        }

        return [
            'is_san_juan' => $isSanJuan,
            'is_rent' => $isRent,
            'is_unknown' => (bool) ($data['is_unknown'] ?? false) || trim($description) === '?',
        ];
    }
}
