<?php

namespace App\Services\Finance;

use App\Models\Finance\Movement;
use App\Models\Finance\SpendingLimit;
use App\Models\User;
use Carbon\Carbon;

class FinanceSpendingLimitService
{
    public function __construct(private readonly FinancePaymentRecommendationService $recommendations) {}

    public function analyze(User $user, int $horizonDays, ?array $paymentRecommendations = null): array
    {
        $paymentRecommendations ??= $this->recommendations->recommend($user, $horizonDays);
        $availableSafeToday = $this->money((float) ($paymentRecommendations['available']['safe_today'] ?? 0));

        $limits = SpendingLimit::with('category')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('period_type')
            ->orderBy('id')
            ->get()
            ->map(fn (SpendingLimit $limit) => $this->analyzeLimit($user, $limit, $availableSafeToday))
            ->values()
            ->all();

        $summary = [
            'total_limits' => count($limits),
            'ok_count' => 0,
            'warning_count' => 0,
            'exceeded_count' => 0,
            'blocked_count' => 0,
        ];

        foreach ($limits as $limit) {
            $key = $limit['status'].'_count';
            $summary[$key] = ($summary[$key] ?? 0) + 1;
        }

        return [
            'available_safe_today' => $availableSafeToday,
            'limits' => $limits,
            'summary' => $summary,
            'messages' => array_values(array_map(fn (array $limit) => $limit['message'], $limits)),
        ];
    }

    private function analyzeLimit(User $user, SpendingLimit $limit, float $availableSafeToday): array
    {
        [$periodStart, $periodEnd] = $this->periodRange($limit->period_type);
        $spentAmount = $this->spentAmount($user, $limit, $periodStart, $periodEnd);
        $limitAmount = $this->money((float) $limit->limit_amount);
        $remainingAmount = $this->money($limitAmount - $spentAmount);
        $usedPercent = $limitAmount > 0
            ? round(($spentAmount / $limitAmount) * 100, 2)
            : 0.0;
        $remainingDays = $this->remainingDays($limit->period_type, $periodEnd);
        $dailyAllowanceByLimit = $remainingAmount > 0
            ? $this->money($remainingAmount / $remainingDays)
            : 0.0;
        $recommendedToday = $this->recommendedToday($remainingAmount, $dailyAllowanceByLimit, $availableSafeToday);
        $status = $this->status($remainingAmount, $usedPercent, (float) $limit->warning_threshold_percent, $availableSafeToday);

        return [
            'id' => $limit->id,
            'category_id' => $limit->category_id,
            'category_name' => $limit->category?->name ?? 'Sin categoría',
            'period_type' => $limit->period_type,
            'limit_amount' => $limitAmount,
            'spent_amount' => $spentAmount,
            'remaining_amount' => $remainingAmount,
            'used_percent' => $usedPercent,
            'remaining_days' => $remainingDays,
            'daily_allowance_by_limit' => $dailyAllowanceByLimit,
            'recommended_today' => $recommendedToday,
            'status' => $status,
            'warning_threshold_percent' => $this->money((float) $limit->warning_threshold_percent),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'notes' => $limit->notes,
            'message' => $this->message($limit->category?->name ?? 'Sin categoría', $status, $usedPercent, $recommendedToday),
        ];
    }

    private function periodRange(string $periodType): array
    {
        $today = today()->startOfDay();

        return match ($periodType) {
            'daily' => [$today->copy(), $today->copy()],
            'weekly' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()],
            'monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()->startOfDay()],
            default => [$today->copy(), $today->copy()],
        };
    }

    private function spentAmount(User $user, SpendingLimit $limit, Carbon $periodStart, Carbon $periodEnd): float
    {
        return $this->money((float) Movement::query()
            ->where('user_id', $user->id)
            ->where('movement_type', 'expense')
            ->where('category_id', $limit->category_id)
            ->whereDate('happened_on', '>=', $periodStart->toDateString())
            ->whereDate('happened_on', '<=', $periodEnd->toDateString())
            ->sum('amount'));
    }

    private function remainingDays(string $periodType, Carbon $periodEnd): int
    {
        if ($periodType === 'daily') {
            return 1;
        }

        return max(1, ((int) today()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay())) + 1);
    }

    private function recommendedToday(float $remainingAmount, float $dailyAllowanceByLimit, float $availableSafeToday): float
    {
        if ($remainingAmount <= 0 || $availableSafeToday <= 0) {
            return 0.0;
        }

        return $this->money(min($dailyAllowanceByLimit, $availableSafeToday));
    }

    private function status(float $remainingAmount, float $usedPercent, float $warningThreshold, float $availableSafeToday): string
    {
        if ($remainingAmount <= 0 || $usedPercent >= 100) {
            return 'exceeded';
        }

        if ($availableSafeToday <= 0) {
            return 'blocked';
        }

        if ($usedPercent >= $warningThreshold) {
            return 'warning';
        }

        return 'ok';
    }

    private function message(string $categoryName, string $status, float $usedPercent, float $recommendedToday): string
    {
        return match ($status) {
            'warning' => $categoryName.': cuidado, ya usaste '.number_format($usedPercent, 2).'% de tu límite.',
            'exceeded' => $categoryName.': ya superaste tu límite. No deberías gastar más aquí.',
            'blocked' => $categoryName.': aunque tengas límite disponible, tu disponible seguro de hoy no permite más gasto.',
            default => $categoryName.': puedes gastar hasta '.$this->formatMoney($recommendedToday).' hoy.',
        };
    }

    private function formatMoney(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
