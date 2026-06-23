@extends('layouts.vertical', ['title' => 'Alerts'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Basic Example</h5>
                <div class="alert alert-primary" role="alert">
                    A simple primary alert—check it out!
                </div>
                <div class="alert alert-secondary" role="alert">
                    A simple secondary alert—check it out!
                </div>
                <div class="alert alert-success" role="alert">
                    A simple success alert—check it out!
                </div>
                <div class="alert alert-danger" role="alert">
                    A simple danger alert—check it out!
                </div>
                <div class="alert alert-warning" role="alert">
                    A simple warning alert—check it out!
                </div>
                <div class="alert alert-info" role="alert">
                    A simple info alert—check it out!
                </div>
                <div class="alert alert-light" role="alert">
                    A simple light alert—check it out!
                </div>
                <div class="alert alert-dark mb-0" role="alert">
                    A simple dark alert—check it out!
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Dismissible Alerts Example
                </h5>
                <div class="alert alert-primary alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple primary alert—check it out!
                </div>
                <div class="alert alert-secondary alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple secondary alert—check it out!
                </div>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple success alert—check it out!
                </div>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple danger alert—check it out!
                </div>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple warning alert—check it out!
                </div>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple info alert—check it out!
                </div>
                <div class="alert alert-light alert-dismissible fade show" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple light alert—check it out!
                </div>
                <div class="alert alert-dark alert-dismissible fade show mb-0" role="alert">
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    A simple dark alert—check it out!
                </div>
            </div><!-- end col -->
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Alert Link Example
                </h5>
                <div class="alert alert-primary" role="alert">
                    A simple primary alert with <a class="alert-link" href="javascript:void(0);">an example link</a>.
                    Give it a click if you like.
                </div>
                <div class="alert alert-secondary" role="alert">
                    A simple secondary alert with <a class="alert-link" href="javascript:void(0);">an example link</a>.
                    Give it a click if you like.
                </div>
                <div class="alert alert-success" role="alert">
                    A simple success alert with <a class="alert-link" href="javascript:void(0);">an example link</a>.
                    Give it a click if you like.
                </div>
                <div class="alert alert-danger mb-0" role="alert">
                    A simple danger alert with <a class="alert-link" href="javascript:void(0);">an example link</a>.
                    Give it a click if you like.
                </div>
            </div><!-- end col -->
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Additional Content Alert Example
                </h5>
                <div class="row">
                    <div class="col-xl-6">
                        <div class="alert alert-primary mb-3 p-3 mb-xl-0" role="alert">
                            <h4 class="alert-heading">Well done!</h4>
                            <p class="mb-0">Aww yeah, you successfully read this important alert message. This example
                                text is going to run a bit longer so that you can see how spacing within an alert works
                                with this kind of content.</p>
                            <hr />
                            <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice
                                and tidy.</p>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="alert alert-secondary p-3 mb-0" role="alert">
                            <h4 class="alert-heading">Well done!</h4>
                            <p class="mb-0">Aww yeah, you successfully read this important alert message. This example
                                text is going to run a bit longer so that you can see how spacing within an alert works
                                with this kind of content.</p>
                            <hr />
                            <p class="mb-0">Whenever you need to, be sure to use margin utilities to keep things nice
                                and tidy.</p>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Live example
                </h5>
                <div id="liveAlertPlaceholder"></div>
                <button class="btn btn-primary" id="liveAlertBtn" type="button">Show live alert</button>
            </div><!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection