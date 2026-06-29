{{-- Barra inferior de pestañas (solo teléfono). El botón "Más" reusa el
     offcanvas del menú lateral vía la clase button-toggle-menu (layout.js). --}}
<nav class="finance-bottom-nav d-md-none" aria-label="Navegación rápida">
    <a href="{{ route('finance.dashboard') }}"
       class="finance-bottom-nav-item {{ request()->routeIs('finance.dashboard') || request()->routeIs('root') || request()->routeIs('dashboard.index') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>Resumen</span>
    </a>
    <a href="{{ route('finance.movements.index') }}"
       class="finance-bottom-nav-item {{ request()->routeIs('finance.movements.*') ? 'active' : '' }}">
        <i data-lucide="list-plus"></i><span>Movim.</span>
    </a>
    <a href="{{ route('finance.movements.index', ['capture' => 1]) }}#nuevo-movimiento"
       class="finance-bottom-nav-item finance-bottom-nav-capture" aria-label="Capturar movimiento">
        <span class="finance-bottom-nav-fab"><i data-lucide="plus"></i></span>
        <span>Capturar</span>
    </a>
    <a href="{{ route('finance.cuts.index') }}"
       class="finance-bottom-nav-item {{ request()->routeIs('finance.cuts.*') ? 'active' : '' }}">
        <i data-lucide="scale"></i><span>Cortes</span>
    </a>
    <button type="button" class="finance-bottom-nav-item button-toggle-menu" aria-label="Más opciones" aria-expanded="false">
        <i data-lucide="menu"></i><span>Más</span>
    </button>
</nav>
