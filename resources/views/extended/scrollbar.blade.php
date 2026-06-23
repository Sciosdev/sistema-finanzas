@extends('layouts.vertical', ['title' => 'Scrollbar'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Default Scroll Example
                </h5>
                <div class="border rounded py-3">
                    <div class="px-3" data-simplebar="" style="max-height: 250px;">
                        SimpleBar does only one thing: replace the browser's default scrollbar
                        with a custom CSS-styled one without losing performances.
                        Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                        Javascript, causing janks and strange scrolling behaviours...
                        You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll
                                behaviour</strong>. It keeps the <strong>native</strong>
                            <code>overflow: auto</code> scroll and <strong>only</strong> replace
                            the scrollbar visual appearance.
                        </p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                            customize it as you want! Or even have multiple style on the same
                            page...or just keep the default style ("Mac OS" scrollbar style).
                        </p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                            scrolling. You keep the performances/behaviours of the native
                            scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p>SimpleBar has been tested on the following browsers: Chrome, Firefox,
                            Safari, Edge, IE11.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    RTL Position
                </h5>
                <div class="border rounded py-3">
                    <div class="px-3" data-simplebar="" data-simplebar-direction="rtl" style="max-height: 250px;">
                        SimpleBar does only one thing: replace the browser's default scrollbar
                        with a custom CSS-styled one without losing performances.
                        Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                        Javascript, causing janks and strange scrolling behaviours...
                        You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll
                                behaviour</strong>. It keeps the <strong>native</strong>
                            <code>overflow: auto</code> scroll and <strong>only</strong> replace
                            the scrollbar visual appearance.
                        </p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                            customize it as you want! Or even have multiple style on the same
                            page...or just keep the default style ("Mac OS" scrollbar style).
                        </p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                            scrolling. You keep the performances/behaviours of the native
                            scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p>SimpleBar has been tested on the following browsers: Chrome, Firefox,
                            Safari, Edge, IE11.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Scroll Size
                </h5>
                <div class="border rounded py-3">
                    <div class="px-3" data-simplebar="" data-simplebar-lg="" style="max-height: 250px;">
                        SimpleBar does only one thing: replace the browser's default scrollbar
                        with a custom CSS-styled one without losing performances.
                        Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                        Javascript, causing janks and strange scrolling behaviours...
                        You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll
                                behaviour</strong>. It keeps the <strong>native</strong>
                            <code>overflow: auto</code> scroll and <strong>only</strong> replace
                            the scrollbar visual appearance.
                        </p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                            customize it as you want! Or even have multiple style on the same
                            page...or just keep the default style ("Mac OS" scrollbar style).
                        </p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                            scrolling. You keep the performances/behaviours of the native
                            scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p>SimpleBar has been tested on the following browsers: Chrome, Firefox,
                            Safari, Edge, IE11.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Scroll Color
                </h5>
                <div class="border rounded py-3">
                    <div class="px-3" data-simplebar="" data-simplebar-primary="" style="max-height: 250px;">
                        SimpleBar does only one thing: replace the browser's default scrollbar
                        with a custom CSS-styled one without losing performances.
                        Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                        Javascript, causing janks and strange scrolling behaviours...
                        You keep the awesomeness of native scrolling...with a custom scrollbar!
                        <p>SimpleBar <strong>does NOT implement a custom scroll
                                behaviour</strong>. It keeps the <strong>native</strong>
                            <code>overflow: auto</code> scroll and <strong>only</strong> replace
                            the scrollbar visual appearance.
                        </p>
                        <h5>Design it as you want</h5>
                        <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                            customize it as you want! Or even have multiple style on the same
                            page...or just keep the default style ("Mac OS" scrollbar style).
                        </p>
                        <h5>Lightweight and performant</h5>
                        <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                            scrolling. You keep the performances/behaviours of the native
                            scroll.</p>
                        <h5>Supported everywhere</h5>
                        <p>SimpleBar has been tested on the following browsers: Chrome, Firefox,
                            Safari, Edge, IE11.</p>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection