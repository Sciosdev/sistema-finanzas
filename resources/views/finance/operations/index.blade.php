@extends('layouts.vertical', ['title' => 'Operación'])

@section('content')
@include('finance.partials.flash')

<div class="row align-items-center mb-3">
    <div class="col-md-8">
        <h4 class="mb-0 fw-semibold">Operación diaria</h4>
        <p class="text-muted mb-0">Preparación para uso como app web, Ubuntu con Tailscale y GitHub.</p>
    </div>
    <div class="col-md-4 text-md-end mt-2 mt-md-0">
        <a href="{{ route('finance.reminders.index') }}" class="btn btn-outline-primary">
            <i data-lucide="bell-ring" class="me-1"></i>Recordatorios
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Notificaciones web</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Base mínima lista: el navegador puede pedir permiso y mostrar avisos locales. En servidor real requiere HTTPS; en localhost funciona para pruebas.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-primary" id="financeNotificationPermission">
                        <i data-lucide="bell" class="me-1"></i>Activar permiso
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="financeNotificationTest">
                        Probar aviso
                    </button>
                </div>
                <div class="alert {{ $isSecureContextHint ? 'alert-success' : 'alert-warning' }} mb-0">
                    {{ $isSecureContextHint ? 'Contexto válido para probar notificaciones.' : 'Para notificaciones reales necesitas HTTPS o abrirlo en localhost.' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Ubuntu + Tailscale</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Se puede montar en una computadora con Ubuntu y acceder por Tailscale sin IP fija. La idea práctica:
                </p>
                <ul class="mb-0">
                    <li>Ubuntu con PHP 8.3+, Composer, MySQL/MariaDB y Nginx o Apache.</li>
                    <li>Tailscale instalado y sesión iniciada en la misma red privada.</li>
                    <li>Dominio interno tipo <code>finanzas.tailnet.ts.net</code> o IP Tailscale.</li>
                    <li>HTTPS con <code>tailscale cert</code> si quieres notificaciones web reales fuera de localhost.</li>
                    <li>Deploy pendiente: no se ejecuta hasta que lo confirmes.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">GitHub</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Estado local del repositorio:
                    @if ($gitInitialized)
                        <span class="badge bg-success-subtle text-success">Git iniciado</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning">Git no iniciado en esta carpeta</span>
                    @endif
                </p>
                <p class="mb-2">Cuando quieras subirlo, el flujo recomendado es:</p>
                <ol class="mb-0">
                    <li>Inicializar Git dentro del proyecto.</li>
                    <li>Crear un repositorio privado en GitHub.</li>
                    <li>Agregar <code>.env</code> al <code>.gitignore</code> y revisar que no suba credenciales.</li>
                    <li>Hacer commit inicial y push.</li>
                </ol>
                <p class="text-muted mt-3 mb-0">No subí nada ni ejecuté comandos de Git.</p>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title mb-0">Uso diario recomendado</h4>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Registrar movimientos reales cuando ocurren.</li>
                    <li>Hacer corte diario para cuadrar tarjetas y efectivo.</li>
                    <li>Revisar obligaciones, ingresos esperados y recordatorios al inicio del día.</li>
                    <li>Usar Seguridad para backups antes de cambios grandes.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const permissionButton = document.getElementById('financeNotificationPermission');
        const testButton = document.getElementById('financeNotificationTest');

        const showNotification = () => {
            if (!('Notification' in window)) {
                alert('Este navegador no soporta notificaciones web.');
                return;
            }

            if (Notification.permission !== 'granted') {
                alert('Primero activa el permiso de notificaciones.');
                return;
            }

            new Notification('Finanzas', {
                body: 'Aviso de prueba: revisa tus pagos, ingresos o recordatorios próximos.',
            });
        };

        permissionButton?.addEventListener('click', async () => {
            if (!('Notification' in window)) {
                alert('Este navegador no soporta notificaciones web.');
                return;
            }

            const permission = await Notification.requestPermission();
            alert(permission === 'granted' ? 'Permiso activado.' : 'Permiso no activado.');
        });

        testButton?.addEventListener('click', showNotification);
    });
</script>
@endsection
