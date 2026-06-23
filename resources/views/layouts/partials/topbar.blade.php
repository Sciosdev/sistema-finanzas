<header class="topbar d-flex">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary button-toggle-menu d-xl-none" aria-label="Mostrar menú" aria-expanded="false">
                         <i data-lucide="menu" class="me-1"></i>Menú
                    </button>
                    <a href="{{ route('finance.dashboard') }}" class="btn btn-sm btn-outline-primary">
                         <i data-lucide="plus" class="me-1"></i>Capturar
                    </a>
               </div>

               <div class="d-flex align-items-center gap-2 ms-auto">
                    <div class="topbar-item">
                         <button type="button" class="topbar-button fs-24" id="light-dark-mode">
                              <i data-lucide="moon" class="light-mode"></i>
                              <i data-lucide="sun" class="dark-mode"></i>
                         </button>
                    </div>

                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button p-0" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center gap-2">
                                   <span class="avatar-sm rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                                   </span>
                                   <span class="d-lg-flex flex-column gap-1 d-none">
                                        <h5 class="my-0 text-reset fs-14">{{ auth()->user()?->name ?? 'Usuario' }}</h5>
                                   </span>
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <a class="dropdown-item" href="{{ route('finance.dashboard') }}">
                                   <i data-lucide="layout-dashboard" class="fs-16 text-muted align-middle me-2"></i><span class="align-middle">Resumen</span>
                              </a>
                              <div class="dropdown-divider my-1"></div>
                              <form method="POST" action="{{ route('logout') }}">
                                   @csrf
                                   <button type="submit" class="dropdown-item">
                                        <i data-lucide="log-out" class="fs-16 text-muted align-middle me-2"></i><span class="align-middle">Salir</span>
                                   </button>
                              </form>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</header>
