@extends('layouts.vertical', ['title' => 'Buttons'])

@section('content')
<div class="card">
    <div class="card-body">
        <!-- start button -->
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Default Buttons
                </h5>
                <div class="button-list">
                    <button class="btn btn-primary" type="button">Primary</button>
                    <button class="btn btn-secondary" type="button">Secondary</button>
                    <button class="btn btn-success" type="button">Success</button>
                    <button class="btn btn-info" type="button">Info</button>
                    <button class="btn btn-warning" type="button">Warning</button>
                    <button class="btn btn-danger" type="button">Danger</button>
                    <button class="btn btn-dark" type="button">Dark</button>
                    <button class="btn btn-light" type="button">Light</button>
                    <button class="btn btn-link" type="button">Link</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Rounded Buttons
                </h5>
                <div class="button-list">
                    <button class="btn btn-primary rounded-pill" type="button">Primary</button>
                    <button class="btn btn-secondary rounded-pill" type="button">Secondary</button>
                    <button class="btn btn-success rounded-pill" type="button">Success</button>
                    <button class="btn btn-info rounded-pill" type="button">Info</button>
                    <button class="btn btn-warning rounded-pill" type="button">Warning</button>
                    <button class="btn btn-danger rounded-pill" type="button">Danger</button>
                    <button class="btn btn-dark rounded-pill" type="button">Dark</button>
                    <button class="btn btn-light rounded-pill" type="button">Light</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Outline Buttons
                </h5>
                <div class="button-list">
                    <button class="btn btn-outline-primary" type="button">Primary</button>
                    <button class="btn btn-outline-secondary" type="button">Secondary</button>
                    <button class="btn btn-outline-success" type="button">Success</button>
                    <button class="btn btn-outline-info" type="button">Info</button>
                    <button class="btn btn-outline-warning" type="button">Warning</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Outline Rounded Buttons
                </h5>
                <div class="mb-3">
                    <div class="button-list">
                        <button class="btn btn-outline-primary rounded-pill" type="button">Primary</button>
                        <button class="btn btn-outline-secondary rounded-pill" type="button">Secondary</button>
                        <button class="btn btn-outline-success rounded-pill" type="button">Success</button>
                        <button class="btn btn-outline-info rounded-pill" type="button">Info</button>
                        <button class="btn btn-outline-warning rounded-pill" type="button">Warning</button>
                    </div>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Soft Buttons
                </h5>
                <div class="button-list">
                    <button class="btn btn-soft-primary" type="button">Primary</button>
                    <button class="btn btn-soft-secondary" type="button">Secondary</button>
                    <button class="btn btn-soft-success" type="button">Success</button>
                    <button class="btn btn-soft-info" type="button">Info</button>
                    <button class="btn btn-soft-warning" type="button">Warning</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Soft Rounded Buttons
                </h5>
                <div class="button-list">
                    <button class="btn btn-soft-primary rounded-pill" type="button">Primary</button>
                    <button class="btn btn-soft-secondary rounded-pill" type="button">Secondary</button>
                    <button class="btn btn-soft-success rounded-pill" type="button">Success</button>
                    <button class="btn btn-soft-info rounded-pill" type="button">Info</button>
                    <button class="btn btn-soft-warning rounded-pill" type="button">Warning</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Button Width
                </h5>
                <div class="button-list">
                    <button class="btn btn-primary width-xl" type="button">Extra Large</button>
                    <button class="btn btn-secondary width-lg" type="button">Large</button>
                    <button class="btn btn-success width-md" type="button">Middle</button>
                    <button class="btn btn-info width-sm" type="button">Small</button>
                    <button class="btn btn-warning width-xs" type="button">Xs</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Button Sizes
                </h5>
                <div class="button-list">
                    <button class="btn btn-primary btn-lg" type="button">Large</button>
                    <button class="btn btn-secondary" type="button">Normal</button>
                    <button class="btn btn-success btn-sm" type="button">Small</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Disabled Button
                </h5>
                <div class="button-list">
                    <button class="btn btn-primary" disabled="" type="button">Primary</button>
                    <button class="btn btn-secondary" disabled="" type="button">Secondary</button>
                    <button class="btn btn-success" disabled="" type="button">Success</button>
                    <button class="btn btn-info" disabled="" type="button">Info</button>
                    <button class="btn btn-warning" disabled="" type="button">Warning</button>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Button Group
                </h5>
                <div class="row g-4">
                    <div class="col-md-12">
                        <!-- btn-group horizontal -->
                        <div class="btn-group mb-1 me-1">
                            <button class="btn btn-light" type="button">Left</button>
                            <button class="btn btn-light" type="button">Middle</button>
                            <button class="btn btn-light" type="button">Right</button>
                        </div>
                        <div class="btn-group mb-1 me-1">
                            <button class="btn btn-light" type="button">1</button>
                            <button class="btn btn-light" type="button">2</button>
                            <button class="btn btn-secondary" type="button">3</button>
                            <button class="btn btn-light" type="button">4</button>
                        </div>
                        <div class="btn-group mb-1 me-1">
                            <button class="btn btn-light" type="button">5</button>
                            <button class="btn btn-secondary" type="button">6</button>
                            <button class="btn btn-light" type="button">7</button>
                            <button aria-expanded="false" class="btn btn-light dropdown-toggle"
                                data-bs-toggle="dropdown" id="dropdown" type="button">
                                Dropdown
                            </button>
                            <ul aria-labelledby="dropdown" class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <!-- btn-group vertical -->
                        <div class="btn-group-vertical me-4">
                            <button class="btn btn-light" type="button">Top</button>
                            <button class="btn btn-light" type="button">Middle</button>
                            <button class="btn btn-light" type="button">Bottom</button>
                        </div>
                        <div class="btn-group-vertical">
                            <button class="btn btn-light" type="button">Button 1</button>
                            <button class="btn btn-light" type="button">Button 2</button>
                            <button aria-expanded="false" class="btn btn-light dropdown-toggle"
                                data-bs-toggle="dropdown" id="verticalDropdown" type="button">
                                Button 3
                            </button>
                            <ul aria-labelledby="verticalDropdown" class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Dropdown link</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div><!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Block Button
                </h5>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" type="button">Block Button</button>
                    <button class="btn btn-secondary" type="button">Block Button</button>
                    <button class="btn btn-light btn-sm" type="button">Block Button</button>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
        <!-- end button -->
    </div>
</div>
@endsection