@extends('layouts.vertical', ['title' => 'Pop overs'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Live demo</h5>
                <button class="btn btn-danger"
                    data-bs-content="And here's some amazing content. It's very engaging. Right?"
                    data-bs-toggle="popover" title="Popover title" type="button">Click to toggle popover</button>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Popover Directions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <!-- Top Position -->
                    <button class="btn btn-primary" data-bs-container="body"
                        data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus."
                        data-bs-placement="top" data-bs-toggle="popover" type="button">
                        Popover on top
                    </button>
                    <!-- Bottom Position -->
                    <button class="btn btn-primary" data-bs-container="body"
                        data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus."
                        data-bs-placement="bottom" data-bs-toggle="popover" type="button">
                        Popover on bottom
                    </button>
                    <!-- Left Position -->
                    <button class="btn btn-primary" data-bs-container="body"
                        data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus."
                        data-bs-placement="left" data-bs-toggle="popover" title="Popover title" type="button">
                        Popover on left
                    </button>
                    <!-- Right Position -->
                    <button class="btn btn-primary" data-bs-container="body"
                        data-bs-content="Vivamus sagittis lacus vel augue laoreet rutrum faucibus."
                        data-bs-placement="right" data-bs-toggle="popover" type="button">
                        Popover on right
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Dismiss on Next Click</h5>
                <button class="btn btn-success"
                    data-bs-content="And here's some amazing content. It's very engaging. Right?"
                    data-bs-toggle="popover" data-bs-trigger="focus" tabindex="0" title="Dismissible popover"
                    type="button">
                    Dismissible popover
                </button>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Hover</h5>
                <button class="btn btn-dark"
                    data-bs-content="And here's some amazing content. It's very engaging. Right?"
                    data-bs-toggle="popover" data-bs-trigger="hover" tabindex="0" title="Ohh Wow !" type="button">
                    Please Hover Me
                </button>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Custom Popovers</h5>
                <div class="button-list">
                    <button class="btn btn-primary" data-bs-content="This popover is themed via CSS variables."
                        data-bs-custom-class="primary-popover" data-bs-placement="top" data-bs-title="Primary popover"
                        data-bs-toggle="popover" type="button">
                        Primary popover
                    </button>
                    <button class="btn btn-success" data-bs-content="This popover is themed via CSS variables."
                        data-bs-custom-class="success-popover" data-bs-placement="top" data-bs-title="Success popover"
                        data-bs-toggle="popover" type="button">
                        Success popover
                    </button>
                    <button class="btn btn-danger" data-bs-content="This popover is themed via CSS variables."
                        data-bs-custom-class="danger-popover" data-bs-placement="top" data-bs-title="Danger popover"
                        data-bs-toggle="popover" type="button">
                        Danger popover
                    </button>
                    <button class="btn btn-info" data-bs-content="This popover is themed via CSS variables."
                        data-bs-custom-class="info-popover" data-bs-placement="top" data-bs-title="Info popover"
                        data-bs-toggle="popover" type="button">
                        Info popover
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Disabled Elements</h5>
                <span class="d-inline-block" data-bs-content="Disabled popover" data-bs-toggle="popover"
                    data-bs-trigger="hover">
                    <button class="btn btn-primary" disabled="" style="pointer-events: none;" type="button">Disabled
                        button</button>
                </span>
            </div> <!-- end card -->
        </div> <!-- end row -->
    </div>
</div>
@endsection