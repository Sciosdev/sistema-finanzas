<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(
        private readonly FinanceCatalogService $catalogs,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $accounts = Account::where('user_id', $user->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('finance.accounts.index', [
            'accounts' => $accounts,
            'typeOptions' => Account::typeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $this->validatedData($request);

        Account::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => Account::normalizeType($data['type']),
            'color' => ($data['color'] ?? null) ?: '#4d5761',
            'credit_limit' => $data['credit_limit'] ?? null,
            'statement_day' => $data['statement_day'] ?? null,
            'payment_day' => $data['payment_day'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Cuenta agregada.');
    }

    public function update(Request $request, Account $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $data = $this->validatedData($request, $account);

        $account->update([
            'name' => $data['name'],
            'type' => Account::normalizeType($data['type']),
            'color' => ($data['color'] ?? null) ?: '#4d5761',
            'credit_limit' => $data['credit_limit'] ?? null,
            'statement_day' => $data['statement_day'] ?? null,
            'payment_day' => $data['payment_day'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Cuenta actualizada.');
    }

    private function validatedData(Request $request, ?Account $account = null): array
    {
        $user = $request->user();

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_accounts', 'name')
                    ->where(fn ($query) => $query->where('user_id', $user->id))
                    ->ignore($account?->id),
            ],
            'type' => ['required', Rule::in(array_keys(Account::TYPE_ALIASES))],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'statement_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ], [
            'name.unique' => 'Ya existe una cuenta con ese nombre.',
            'color.regex' => 'El color debe tener formato hexadecimal, por ejemplo #0f766e.',
        ]);
    }
}
