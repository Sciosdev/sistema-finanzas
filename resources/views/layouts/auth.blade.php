<!DOCTYPE html>
<html lang="es">

<head>
    @include('layouts.partials/title-meta', ['title' => $title])
    @include('layouts.partials/head-css')
</head>

<body>

    <div class="account-pages min-vh-100 d-flex align-items-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                @yield('content')
            </div>
        </div>
    </div>

    @include('layouts.partials/vendor-scripts')

</body>

</html>
