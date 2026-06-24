<!DOCTYPE html>
<html lang="es" data-bs-theme="dark" class="topbar-light sidebar-light">

    <head>
        @include('layouts.partials.title-meta', ['title' => $title])

        @include('layouts.partials.head-css')
    </head>

    <body>
        
        <div class="wrapper">

            @include('layouts.partials.main-nav')
            @include('layouts.partials.topbar')

            <div class="page-container">
                <div class="page-content">

                    @yield('content')

                </div>
                @include('layouts.partials.footer')
            </div>

        </div>

        @include('layouts.partials.vendor-scripts')

    </body>

</html>
