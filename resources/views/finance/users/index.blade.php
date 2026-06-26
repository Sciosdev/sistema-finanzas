@extends('layouts.vertical', ['title' => 'Usuarios'])

@section('content')
@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-12">
        <h4 class="mb-0 fw-semibold">Usuarios</h4>
        <div class="text-muted">Alta de cuentas reservada al administrador. El registro público está cerrado.</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0"><i data-lucide="user-plus" class="me-1"></i>Crear usuario</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('finance.users.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" maxlength="255" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8" required autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Confirmar</label>
                <input type="password" name="password_confirmation" class="form-control" minlength="8" required autocomplete="new-password">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="user-plus" class="me-1"></i>Crear usuario
                </button>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2">
            El administrador es quien tenga el correo configurado en <code>FINANCE_OWNER_EMAIL</code>. Los usuarios creados aquí son cuentas normales.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h4 class="card-title mb-0">Cuentas registradas</h4>
        <span class="badge badge-soft-secondary">{{ $users->count() }} usuario(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Alta</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($ownerEmail !== '' && strcasecmp($user->email, $ownerEmail) === 0)
                                    <span class="badge badge-soft-primary">Administrador</span>
                                @else
                                    <span class="badge badge-soft-secondary">Usuario</span>
                                @endif
                            </td>
                            <td>{{ optional($user->created_at)->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Sin usuarios</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
