<div class="main-nav">
     <div class="d-flex justify-content-between main-logo-box">
          <div class="logo-box">
               <a href="{{ route('finance.dashboard') }}" class="logo-dark">
                    <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm">
                    <span class="logo-lg fw-bold text-dark fs-20">Finanzas</span>
               </a>

               <a href="{{ route('finance.dashboard') }}" class="logo-light">
                    <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm">
                    <span class="logo-lg fw-bold text-white fs-20">Finanzas</span>
               </a>
          </div>

          <button type="button" class="btn btn-link d-flex button-sm-hover button-toggle-menu" aria-label="Mostrar menú completo" aria-expanded="false">
               <i data-lucide="menu" class="button-sm-hover-icon"></i>
          </button>
     </div>

     <div class="h-100" data-simplebar>
          <ul class="navbar-nav" id="navbar-nav">
               <li class="menu-title">Sistema</li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.dashboard') }}">
                         <span class="nav-icon">
                              <i data-lucide="layout-dashboard"></i>
                         </span>
                         <span class="nav-text">Resumen</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.movements.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="list-plus"></i>
                         </span>
                         <span class="nav-text">Movimientos</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.reports.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="bar-chart-3"></i>
                         </span>
                         <span class="nav-text">Reportes</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.imports.historical.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="file-input"></i>
                         </span>
                         <span class="nav-text">Importar histórico</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.monthly-review.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="spell-check-2"></i>
                         </span>
                         <span class="nav-text">Corrector mensual</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.pending.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="list-checks"></i>
                         </span>
                         <span class="nav-text">Pendientes</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.cuts.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="scale"></i>
                         </span>
                         <span class="nav-text">Cortes</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.planned.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="calendar-check"></i>
                         </span>
                         <span class="nav-text">Flujo planeado</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.expected-incomes.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="calendar-plus"></i>
                         </span>
                         <span class="nav-text">Ingresos esperados</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.reminders.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="bell-ring"></i>
                         </span>
                         <span class="nav-text">Recordatorios</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.credits.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="credit-card"></i>
                         </span>
                         <span class="nav-text">Créditos</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.san-juan.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="home"></i>
                         </span>
                         <span class="nav-text">San Juan</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.categories.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="tags"></i>
                         </span>
                         <span class="nav-text">Categorías</span>
                    </a>
               </li>

               @if (auth()->user()?->isFinanceOwner())
               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.users.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="users"></i>
                         </span>
                         <span class="nav-text">Usuarios</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.security.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="shield-check"></i>
                         </span>
                         <span class="nav-text">Seguridad</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.health.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="activity"></i>
                         </span>
                         <span class="nav-text">Diagnóstico</span>
                    </a>
               </li>
               @endif

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.accounts.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="wallet-cards"></i>
                         </span>
                         <span class="nav-text">Cuentas</span>
                    </a>
               </li>

               <li class="menu-item">
                    <a class="menu-link" href="{{ route('finance.operations.index') }}">
                         <span class="nav-icon">
                              <i data-lucide="settings-2"></i>
                         </span>
                         <span class="nav-text">Operación</span>
                    </a>
               </li>

               <li class="menu-title">Cuenta</li>

               <li class="menu-item">
                    <form method="POST" action="{{ route('logout') }}">
                         @csrf
                         <button type="submit" class="menu-link border-0 bg-transparent w-100 text-start">
                              <span class="nav-icon">
                                   <i data-lucide="log-out"></i>
                              </span>
                              <span class="nav-text">Salir</span>
                         </button>
                    </form>
               </li>
          </ul>
     </div>
</div>
