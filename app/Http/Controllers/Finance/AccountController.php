<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceCutSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Color representativo por nombre de cuenta (clave normalizada).
     *
     * @var array<string, string>
     */
    private const COLOR_BY_NAME = [
        'efectivo' => '#16a34a',
        'nu' => '#7c3aed',
        'mpw' => '#facc15',
        'didi' => '#f97316',
        'mercadopago' => '#00b4d8',
        'bbva' => '#2563eb',
        'onix' => '#334155',
        'tarjeta' => '#6366f1',
        'amazon' => '#f59e0b',
        'santander' => '#ef4444',
        'banorte' => '#dc2626',
        'banamex' => '#0ea5e9',
        'hsbc' => '#e11d48',
        'spin' => '#ec4899',
        'oxxo' => '#e11d48',
    ];

    /**
     * Colores distintos de respaldo para cuentas sin marca conocida.
     *
     * @var list<string>
     */
    private const FALLBACK_PALETTE = [
        '#0ea5e9', '#22c55e', '#a855f7', '#fb7185', '#14b8a6', '#f59e0b',
        '#6366f1', '#ec4899', '#84cc16', '#06b6d4', '#f43f5e', '#eab308',
    ];

    public function __construct(
        private readonly FinanceCatalogService $catalogs,
        private readonly FinanceCutSuggestionService $cutSuggestions,
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
            'expectedBalances' => $this->cutSuggestions->expectedBalances($user, $accounts, today()),
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

    public function applySuggestedColors(Request $request)
    {
        $user = $request->user();
        $this->catalogs->ensureForUser($user);

        $accounts = Account::where('user_id', $user->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $updated = 0;
        $fallbackIndex = 0;

        DB::transaction(function () use ($accounts, &$updated, &$fallbackIndex) {
            foreach ($accounts as $account) {
                $normalized = $this->normalizedName($account->name);
                $color = self::COLOR_BY_NAME[$normalized]
                    ?? self::FALLBACK_PALETTE[$fallbackIndex++ % count(self::FALLBACK_PALETTE)];

                if (strcasecmp((string) $account->color, $color) !== 0) {
                    $account->update(['color' => $color]);
                    $updated++;
                }
            }
        });

        return back()->with(
            'success',
            $updated > 0
                ? "Se aplicaron colores distintos a {$updated} cuenta(s). Puedes ajustar cualquiera a mano."
                : 'Tus cuentas ya tienen colores asignados.'
        );
    }

    private function normalizedName(?string $name): string
    {
        return (string) Str::of($name ?? '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '');
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
