@yield('css')
@vite(['resources/scss/app.scss'])
@vite(['resources/js/config.js'])
<link rel="stylesheet" href="{{ asset('css/finance-mobile.css') }}?v=1">
@yield('css-after')