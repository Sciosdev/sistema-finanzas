@vite(['resources/js/app.js', 'resources/js/layout.js'])
<script>
    window.addEventListener('load', function () {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js').catch(function () {
                // El sistema sigue funcionando aunque el navegador no permita PWA.
            });
        }
    });
</script>
@yield('scripts')
