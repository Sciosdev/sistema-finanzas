@extends('layouts.vertical', ['title' => 'Toastify'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Basic Toastify JS Example
                </h5>
                <div class="hstack flex-wrap gap-2">
                    <button class="btn btn-light w-xs" data-toast="" data-toast-classname="primary"
                        data-toast-close="close" data-toast-duration="3000" data-toast-gravity="top"
                        data-toast-position="right" data-toast-style="style"
                        data-toast-text="Welcome Back! This is a Toast Notification" type="button">
                        Default
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-classname="success"
                        data-toast-duration="3000" data-toast-gravity="top" data-toast-position="center"
                        data-toast-text="Your application was successfully sent" type="button">
                        Success
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-classname="warning"
                        data-toast-duration="3000" data-toast-gravity="top" data-toast-position="center"
                        data-toast-text="Warning ! Something went wrong try again" type="button">
                        Warning
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-classname="danger"
                        data-toast-duration="3000" data-toast-gravity="top" data-toast-position="center"
                        data-toast-text="Error ! An error occurred." type="button">
                        Error
                    </button>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Display Position Example
                </h5>
                <div class="hstack flex-wrap gap-2">
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="top" data-toast-position="left"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Top Left
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="top" data-toast-position="center"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Top Center
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="top" data-toast-position="right"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Top Right
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="bottom" data-toast-position="left"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Bottom Left
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="bottom" data-toast-position="center"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Bottom Center
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="bottom" data-toast-position="right"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Bottom Right
                    </button>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Offset, Close Button &amp; Duration Example
                </h5>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-gravity="top" data-toast-offset="" data-toast-position="right"
                        data-toast-text="Welcome Back ! This is a Toast Notification" type="button">
                        Offset Position
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-close="close" data-toast-duration="3000"
                        data-toast-position="right" data-toast-text="Welcome Back ! This is a Toast Notification"
                        type="button">
                        Close icon Display
                    </button>
                    <button class="btn btn-light w-xs" data-toast="" data-toast-duration="5000" data-toast-gravity="top"
                        data-toast-position="right" data-toast-text="Toast Duration 5s" type="button">
                        Duration
                    </button>
                </div>
            </div><!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection