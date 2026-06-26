<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Alta de usuarios reservada al dueño financiero (admin).
 *
 * El registro público (/register) permanece cerrado; solo el administrador
 * puede crear cuentas desde aquí. La autorización la aplica el middleware
 * `finance.owner` en las rutas.
 */
class FinanceUserController extends Controller
{
    public function __construct(private readonly FinanceCatalogService $catalogs)
    {
    }

    public function index(Request $request)
    {
        return view('finance.users.index', [
            'users' => User::query()->orderBy('name')->orderBy('email')->get(),
            'ownerEmail' => (string) config('finance.owner_email'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Deja el catálogo base listo para que el usuario nuevo pueda operar.
        $this->catalogs->ensureForUser($user);

        return redirect()
            ->route('finance.users.index')
            ->with('success', 'Usuario creado: ' . $user->email);
    }
}
