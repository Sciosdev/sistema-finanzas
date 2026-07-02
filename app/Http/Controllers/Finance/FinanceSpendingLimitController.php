<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\SpendingLimit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceSpendingLimitController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        SpendingLimit::create([
            'user_id' => $request->user()->id,
            'category_id' => $data['category_id'],
            'period_type' => $data['period_type'],
            'limit_amount' => round((float) $data['limit_amount'], 2),
            'warning_threshold_percent' => round((float) ($data['warning_threshold_percent'] ?? 80), 2),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Límite de gasto creado.');
    }

    public function update(Request $request, SpendingLimit $limit)
    {
        abort_unless($limit->user_id === $request->user()->id, 403);

        $data = $this->validatedData($request);

        $limit->update([
            'category_id' => $data['category_id'],
            'period_type' => $data['period_type'],
            'limit_amount' => round((float) $data['limit_amount'], 2),
            'warning_threshold_percent' => round((float) ($data['warning_threshold_percent'] ?? 80), 2),
            'is_active' => (bool) ($data['is_active'] ?? $limit->is_active),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Límite de gasto actualizado.');
    }

    public function destroy(Request $request, SpendingLimit $limit)
    {
        abort_unless($limit->user_id === $request->user()->id, 403);

        $limit->delete();

        return back()->with('success', 'Límite de gasto eliminado.');
    }

    private function validatedData(Request $request): array
    {
        $user = $request->user();

        return $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('finance_categories', 'id')
                    ->where(fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('type', 'expense')),
            ],
            'period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'limit_amount' => ['required', 'numeric', 'min:0.01'],
            'warning_threshold_percent' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
