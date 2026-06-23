<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AutomaticYieldService
{
    /**
     * @param array<int, float> $balances
     */
    public function syncForCut(User $user, Carbon $cutDate, array $balances): void
    {
        $accounts = Account::where('user_id', $user->id)
            ->whereIn('name', ['NU', 'MPW'])
            ->get()
            ->keyBy('id');

        if ($accounts->isEmpty()) {
            return;
        }

        $previousCut = DailyCut::with('balances')
            ->where('user_id', $user->id)
            ->whereDate('cut_date', '<', $cutDate->toDateString())
            ->orderByDesc('cut_date')
            ->first();

        if (! $previousCut) {
            return;
        }

        $previousBalances = $previousCut->balances->keyBy('account_id');

        foreach ($accounts as $account) {
            if (! array_key_exists($account->id, $balances) || ! $previousBalances->has($account->id)) {
                continue;
            }

            $yield = $this->calculateYield(
                $user,
                $account,
                $previousCut->cut_date,
                $cutDate,
                (float) $previousBalances->get($account->id)->balance,
                (float) $balances[$account->id],
            );

            $this->syncMovement($user, $account, $cutDate, $yield);
        }
    }

    private function calculateYield(User $user, Account $account, Carbon $previousDate, Carbon $cutDate, float $previousBalance, float $currentBalance): float
    {
        $movements = Movement::where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->where('movement_type', '!=', 'yield')
            ->whereDate('happened_on', '>', $previousDate->toDateString())
            ->whereDate('happened_on', '<=', $cutDate->toDateString())
            ->get();

        $expectedBalance = $previousBalance + $this->netAccountChange($movements);

        return round($currentBalance - $expectedBalance, 2);
    }

    private function netAccountChange(Collection $movements): float
    {
        return $movements->sum(function (Movement $movement) {
            return match ($movement->movement_type) {
                'income' => (float) $movement->amount,
                'expense' => -1 * (float) $movement->amount,
                default => 0.0,
            };
        });
    }

    private function syncMovement(User $user, Account $account, Carbon $cutDate, float $yield): void
    {
        $existing = Movement::where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->where('movement_type', 'yield')
            ->whereDate('happened_on', $cutDate->toDateString())
            ->where('description', 'Rendimiento ' . $account->name)
            ->first();

        if ($yield <= 0.01) {
            if ($existing && $existing->source !== 'manual') {
                $existing->delete();
            }

            return;
        }

        $category = Category::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Rendimiento ' . $account->name, 'type' => 'yield'],
            [
                'user_id' => $user->id,
                'name' => 'Rendimiento ' . $account->name,
                'type' => 'yield',
                'group' => 'Rendimientos',
                'color' => '#ffc107',
                'keywords' => 'rendimiento ' . strtolower($account->name),
            ],
        );

        $payload = [
            'user_id' => $user->id,
            'happened_on' => $cutDate->toDateString(),
            'movement_type' => 'yield',
            'amount' => $yield,
            'description' => 'Rendimiento ' . $account->name,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'auto:daily-cut',
            'notes' => 'Calculado automaticamente desde el corte diario.',
        ];

        if ($existing) {
            $existing->update($payload);

            return;
        }

        Movement::updateOrCreate(
            ['user_id' => $user->id, 'import_key' => 'auto:yield:' . $account->id . ':' . $cutDate->toDateString()],
            $payload,
        );
    }
}
