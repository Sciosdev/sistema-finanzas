<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CreditOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceCreditOptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        CreditOption::create($this->attributes($request, $data) + [
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Opción de crédito registrada.');
    }

    public function update(Request $request, CreditOption $option)
    {
        abort_unless($option->user_id === $request->user()->id, 403);

        $data = $this->validatedData($request);
        $option->update($this->attributes($request, $data, $option));

        return back()->with('success', 'Opción de crédito actualizada.');
    }

    public function destroy(Request $request, CreditOption $option)
    {
        abort_unless($option->user_id === $request->user()->id, 403);

        $option->delete();

        return back()->with('success', 'Opción de crédito eliminada.');
    }

    private function validatedData(Request $request): array
    {
        $user = $request->user();
        $costType = $request->input('cost_type');

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_accounts', 'id')
                    ->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'available_amount' => ['required', 'numeric', 'min:0.01'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'cost_type' => ['required', Rule::in(CreditOption::COST_TYPES)],
            'cost_percent' => [
                Rule::requiredIf(in_array($costType, ['total_percent', 'percent_plus_fee'], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'fixed_fee' => [
                Rule::requiredIf(in_array($costType, ['fixed_fee', 'percent_plus_fee'], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'term_months' => ['required', 'integer', 'min:1', 'max:60'],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function attributes(Request $request, array $data, ?CreditOption $option = null): array
    {
        return [
            'account_id' => $data['account_id'] ?? null,
            'name' => $data['name'],
            'provider' => $data['provider'] ?? null,
            'available_amount' => round((float) $data['available_amount'], 2),
            'min_amount' => round((float) ($data['min_amount'] ?? 0), 2),
            'cost_type' => $data['cost_type'],
            'cost_percent' => round((float) ($data['cost_percent'] ?? 0), 4),
            'fixed_fee' => round((float) ($data['fixed_fee'] ?? 0), 2),
            'term_months' => (int) $data['term_months'],
            'payment_day' => $data['payment_day'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $option?->is_active ?? true),
            'notes' => $data['notes'] ?? null,
        ];
    }
}
