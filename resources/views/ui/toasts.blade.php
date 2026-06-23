@extends('layouts.vertical', ['title' => 'Toasts'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Basic Examples
                </h5>
                <p class="card-subtitle">
                    Toasts are as flexible as you need and have very little required markup. At a minimum, we require a
                    single element to contain your “toasted” content and strongly encourage a dismiss button.
                </p>
            </div>
            <div class="card-body">
                <div aria-atomic="true" aria-live="assertive" class="toast fade d-block opacity-100"
                    data-bs-toggle="toast" role="alert">
                    <div class="toast-header d-block">
                        <div class="float-end">
                            <small>11 mins ago</small>
                            <button aria-label="Close" class="ms-2 btn-close" data-bs-dismiss="toast"
                                type="button"></button>
                        </div>
                        <div class="auth-logo">
                            <img alt="logo-dark" class="logo-dark" height="18" src="/images/logo-dark.png" />
                            <img alt="logo-light" class="logo-light" height="18" src="/images/logo-white.png" />
                        </div>
                    </div>
                    <div class="toast-body">
                        Hello, world! This is a toast message.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Live example
                </h5>
                <p class="card-subtitle">Click the button below to show a toast (positioned with our utilities in the
                    lower right corner) that has been hidden by default.</p>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" id="liveToastDefaultBtn" type="button">Show live toast</button>
                <div class="toast-container position-fixed bottom-0 end-0 p-3">
                    <div aria-atomic="true" aria-live="assertive" class="toast" id="liveToastDefault" role="alert">
                        <div class="toast-header">
                            <div class="auth-logo me-auto">
                                <img alt="logo-dark" class="logo-dark" height="18" src="/images/logo-dark.png" />
                                <img alt="logo-light" class="logo-light" height="18" src="/images/logo-white.png" />
                            </div>
                            <small>11 mins ago</small>
                            <button aria-label="Close" class="btn-close" data-bs-dismiss="toast" type="button"></button>
                        </div>
                        <div class="toast-body">
                            Hello, world! This is a toast message.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Default Buttons
                </h5>
                <p class="card-subtitle">
                    Toasts are as flexible as you need and have very little required markup. At a minimum, we require a
                    single element to contain your “toasted” content and strongly encourage a dismiss button.
                </p>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" id="liveToastBtn" type="button">Show live toast</button>
                <button class="btn btn-primary" id="liveToastBtn2" type="button">Show live toast</button>
                <div class="toast-container position-fixed end-0 top-0 p-3">
                    <div aria-atomic="true" aria-live="assertive" class="toast" id="liveToast" role="alert">
                        <div class="toast-header">
                            <div class="auth-logo me-auto">
                                <img alt="logo-dark" class="logo-dark" height="18" src="/images/logo-dark.png" />
                                <img alt="logo-light" class="logo-light" height="18" src="/images/logo-white.png" />
                            </div>
                            <small class="text-muted">just now</small>
                            <button aria-label="Close" class="btn-close" data-bs-dismiss="toast" type="button"></button>
                        </div>
                        <div class="toast-body">
                            See? Just like this.
                        </div>
                    </div>
                    <div aria-atomic="true" aria-live="assertive" class="toast" id="liveToast2" role="alert">
                        <div class="toast-header">
                            <div class="auth-logo me-auto">
                                <img alt="logo-dark" class="logo-dark" height="18" src="/images/logo-dark.png" />
                                <img alt="logo-light" class="logo-light" height="18" src="/images/logo-white.png" />
                            </div>
                            <small class="text-muted">2 seconds ago</small>
                            <button aria-label="Close" class="btn-close" data-bs-dismiss="toast" type="button"></button>
                        </div>
                        <div class="toast-body">
                            Heads up, toasts will stack automatically
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Custom Content
                </h5>
                <p class="card-subtitle">
                    Alternatively, you can also add additional controls and components to toasts.
                </p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div aria-atomic="true" aria-live="assertive" class="toast fade show align-items-center mb-3"
                            role="alert">
                            <div class="d-flex">
                                <div class="toast-body">
                                    Hello, world! This is a toast message.
                                </div>
                                <button aria-label="Close" class="btn-close me-2 m-auto" data-bs-dismiss="toast"
                                    type="button"></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div aria-atomic="true" aria-live="assertive"
                            class="toast fade show align-items-center text-bg-primary mb-3" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">
                                    Hello, world! This is a toast message.
                                </div>
                                <button aria-label="Close" class="btn-close btn-close-white me-2 m-auto"
                                    data-bs-dismiss="toast" type="button"></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div aria-atomic="true" aria-live="assertive" class="toast fade show mb-3 mb-md-0" role="alert">
                            <div class="toast-body">
                                Hello, world! This is a toast message.
                                <div class="mt-2 pt-2 border-top">
                                    <button class="btn btn-primary btn-sm me-1" type="button">Take action</button>
                                    <button class="btn btn-secondary btn-sm" data-bs-dismiss="toast"
                                        type="button">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div aria-atomic="true" aria-live="assertive" class="toast fade show text-bg-primary"
                            role="alert">
                            <div class="toast-body">
                                Hello, world! This is a toast message.
                                <div class="mt-2 pt-2 border-top">
                                    <button class="btn btn-light btn-sm me-1" type="button">Take action</button>
                                    <button class="btn btn-secondary btn-sm" data-bs-dismiss="toast"
                                        type="button">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Transcluent
                </h5>
                <p class="card-subtitle">
                    Toasts are slightly translucent, too, so they blend over whatever they might appear over.
                    For browsers that support the backdrop-filter CSS property, we’ll also attempt to blur the elements
                    under a toast.
                </p>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light">
                    <div aria-atomic="true" aria-live="assertive" class="toast fade show" data-bs-toggle="toast"
                        role="alert">
                        <div class="toast-header d-block">
                            <div class="float-end">
                                <small>11 mins ago</small>
                                <button aria-label="Close" class="ms-2 btn-close" data-bs-dismiss="toast"
                                    type="button"></button>
                            </div>
                            <div class="auth-logo">
                                <img alt="logo-dark" class="logo-dark me-1" height="18" src="/images/logo-dark.png" />
                                <img alt="logo-light" class="logo-light" height="18" src="/images/logo-white.png" />
                            </div>
                        </div>
                        <div class="toast-body">
                            Hello, world! This is a toast message.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Placement
                </h5>
                <p class="card-subtitle">
                    Place toasts with custom CSS as you need them. The top right is often used for notifications,
                    as is the top middle. If you’re only ever going to show one toast at a time, put the positioning
                    styles
                    right on the <code>.toast.</code>
                </p>
            </div>
            <div class="card-body">
                <div aria-atomic="true" aria-live="polite" class="bg-light position-relative"
                    style="min-height: 350px;">
                    <div class="toast-container position-absolute p-3" id="toastPlacement">
                        <div class="toast show mb-2">
                            <div class="toast-header d-block">
                                <div class="float-end">
                                    <small>11 mins ago</small>
                                    <button aria-label="Close" class="ms-2 btn-close" data-bs-dismiss="toast"
                                        type="button"></button>
                                </div>
                                <div class="auth-logo">
                                    <img alt="logo-dark" class="logo-dark me-1" height="18"
                                        src="/images/logo-dark.png" />
                                    <img alt="logo-light" class="logo-light" height="18" src="/images/logo-white.png" />
                                </div>
                            </div>
                            <div class="toast-body">
                                Hello, world! This is a toast message.
                            </div>
                        </div>
                    </div>
                </div>
                <form>
                    <div class="mt-3">
                        <label class="form-label" for="selectToastPlacement">Toast placement</label>
                        <select class="form-select mt-2" data-choices="" id="selectToastPlacement">
                            <option selected="" value="">Select a position...</option>
                            <option value="top-0 start-0">Top left (<code class="text-danger">.top-0 .start-0</code>)
                            </option>
                            <option value="top-0 start-50 translate-middle-x">Top center</option>
                            <option value="top-0 end-0">Top right</option>
                            <option value="top-50 start-0 translate-middle-y">Middle left</option>
                            <option value="top-50 start-50 translate-middle">Middle center</option>
                            <option value="top-50 end-0 translate-middle-y">Middle right</option>
                            <option value="bottom-0 start-0">Bottom left</option>
                            <option value="bottom-0 start-50 translate-middle-x">Bottom center</option>
                            <option value="bottom-0 end-0">Bottom right</option>
                        </select>
                    </div>
                </form>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->
</div> <!-- end row -->
@endsection