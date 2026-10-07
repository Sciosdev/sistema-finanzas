@yield('css')
@vite(['resources/scss/app.scss'])
@vite(['resources/js/config.js'])
<link rel="stylesheet" href="{{ asset('css/finance-mobile.css') }}?v={{ config('finance.version') }}">
@yield('css-after')
