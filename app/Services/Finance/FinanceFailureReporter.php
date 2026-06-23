<?php

namespace App\Services\Finance;

use App\Models\Finance\SystemFailure;
use App\Models\User;
use Illuminate\Support\Str;

class FinanceFailureReporter
{
    /**
     * @param array<string, mixed> $context
     */
    public function report(?User $user, string $module, string $action, string $message, array $context = []): SystemFailure
    {
        return SystemFailure::create([
            'user_id' => $user?->id,
            'module' => Str::limit($this->plain($module), 80, ''),
            'action' => Str::limit($this->plain($action), 120, ''),
            'message' => Str::limit($this->plain($message), 1000),
            'status' => 'open',
            'context' => $this->safeContext($context),
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function safeContext(array $context): array
    {
        $safe = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (str_contains($normalizedKey, 'password')
                || str_contains($normalizedKey, 'token')
                || str_contains($normalizedKey, 'secret')
                || str_contains($normalizedKey, 'credential')
                || $normalizedKey === 'env'
                || str_contains($normalizedKey, 'dump')) {
                continue;
            }

            $safe[(string) $key] = $this->safeValue($value);
        }

        return $safe;
    }

    private function safeValue(mixed $value): mixed
    {
        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        if (is_string($value)) {
            return Str::limit($this->plain($value), 500);
        }

        if (is_array($value)) {
            return $this->safeContext($value);
        }

        return Str::limit($this->plain((string) $value), 500);
    }

    private function plain(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
