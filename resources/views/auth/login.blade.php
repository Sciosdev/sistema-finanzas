@extends('layouts.auth', ['title' => 'Iniciar sesión'])

@section('content')
<div class="col-xl-5 col-lg-6 col-md-8">
     <div class="card auth-card border-0 shadow-lg">
          <div class="card-body">
               <div class="p-3 p-md-4">
                    <div class="mx-auto mb-4 auth-logo text-center">
                         <a class="logo-dark text-decoration-none" href="{{ route('finance.dashboard') }}">
                              <span class="fw-bold fs-3 text-primary">Finanzas</span>
                         </a>
                         <a class="logo-light text-decoration-none" href="{{ route('finance.dashboard') }}">
                              <span class="fw-bold fs-3 text-white">Finanzas</span>
                         </a>
                    </div>

                    <div class="text-center">
                         <h3 class="fw-bold fs-22">Bienvenido</h3>
                         <p class="text-muted mt-1 mb-4">Entra para revisar tus ingresos, egresos, cortes y recordatorios.</p>
                    </div>

                    <div class="p-0 p-md-3">
                         <form method="POST" action="{{ route('login') }}" class="authentication-form">
                              @csrf

                              @foreach ($errors->all() as $error)
                                   <p class="text-danger mb-3">{{ $error }}</p>
                              @endforeach

                              <div class="mb-4">
                                   <label class="form-label" for="emailaddress">Correo</label>
                                   <div class="position-relative w-100">
                                        <input class="form-control form-control-lg rounded" type="email" name="email" id="emailaddress" value="{{ old('email') }}" required placeholder="tu@correo.com">
                                        <p class="text-muted p-0 position-absolute end-0 top-50 border-0 fs-4 translate-middle-y me-2 mb-0">
                                             <iconify-icon class="fs-20 mt-1 text-muted" icon="solar:letter-bold-duotone"></iconify-icon>
                                        </p>
                                   </div>
                              </div>

                              <div class="mb-4">
                                   <label class="form-label" for="password">Contraseña</label>
                                   <div class="position-relative w-100">
                                        <input class="form-control form-control-lg rounded" type="password" required id="password" name="password" placeholder="Tu contraseña">
                                        <button class="btn text-muted p-0 position-absolute end-0 top-50 border-0 fs-4 translate-middle-y me-2" type="button">
                                             <iconify-icon class="fs-20 mt-1 text-muted" icon="solar:eye-bold-duotone"></iconify-icon>
                                        </button>
                                   </div>
                              </div>

                              <div class="mb-3">
                                   <div class="form-check">
                                        <input class="form-check-input" id="checkbox-signin" type="checkbox" name="remember" value="1">
                                        <label class="form-check-label" for="checkbox-signin">Mantener sesión iniciada</label>
                                   </div>
                              </div>

                              <div class="text-center d-grid">
                                   <button class="btn btn-primary d-flex align-items-center justify-content-center gap-1 fw-medium" type="submit">
                                        <i class="fs-18" data-lucide="log-in"></i> Entrar
                                   </button>
                              </div>
                         </form>
                    </div>

                    <p class="text-muted text-center mt-3 mb-0">Sistema privado de control financiero personal.</p>
               </div>
          </div>
     </div>
</div>
@endsection
