@extends('layouts.vertical', ['title' => 'Badge'])

@section('content')
<!-- start badges -->
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Heading
                </h5>
                <h1>h1.Example heading <span class="badge bg-primary">New</span></h1>
                <h2>h2.Example heading <span class="badge bg-secondary">New</span></h2>
                <h3>h3.Example heading <span class="badge bg-success">New</span></h3>
                <h4>h4.Example heading <span class="badge bg-info">New</span></h4>
                <h5>h5.Example heading <span class="badge bg-warning">New</span></h5>
                <h6 class="mb-0">h6.Example heading <span class="badge bg-danger">New</span></h6>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Outline &amp; Outline Pill Badges
                </h5>
                <!-- Outline Badges -->
                <div class="mb-2">
                    <span class="badge badge-outline-primary me-1">Primary</span>
                    <span class="badge badge-outline-secondary me-1">Secondary</span>
                    <span class="badge badge-outline-success me-1">Success</span>
                    <span class="badge badge-outline-info me-1">Info</span>
                    <span class="badge badge-outline-warning me-1">Warning</span>
                    <span class="badge badge-outline-danger me-1">Danger</span>
                    <span class="badge badge-outline-dark me-1">Dark</span>
                    <span class="badge badge-outline-purple me-1">Purple</span>
                    <span class="badge badge-outline-pink me-1">Pink</span>
                    <span class="badge badge-outline-orange me-1">Orange</span>
                </div>
                <!-- Outline Pill Badges -->
                <div>
                    <span class="badge badge-outline-primary rounded-pill me-1">Primary</span>
                    <span class="badge badge-outline-secondary rounded-pill me-1">Secondary</span>
                    <span class="badge badge-outline-success rounded-pill me-1">Success</span>
                    <span class="badge badge-outline-info rounded-pill me-1">Info</span>
                    <span class="badge badge-outline-warning rounded-pill me-1">Warning</span>
                    <span class="badge badge-outline-danger rounded-pill me-1">Danger</span>
                    <span class="badge badge-outline-dark rounded-pill me-1">Dark</span>
                    <span class="badge badge-outline-purple rounded-pill me-1">Purple</span>
                    <span class="badge badge-outline-pink rounded-pill me-1">Pink</span>
                    <span class="badge badge-outline-orange rounded-pill me-1">Orange</span>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Soft &amp; Soft Pill Badges
                </h5>
                <!--  Soft Badges -->
                <div class="mb-2">
                    <span class="badge badge-soft-primary me-1">Primary</span>
                    <span class="badge badge-soft-secondary me-1">Secondary</span>
                    <span class="badge badge-soft-success me-1">Success</span>
                    <span class="badge badge-soft-info me-1">Info</span>
                    <span class="badge badge-soft-warning me-1">Warning</span>
                    <span class="badge badge-soft-danger me-1">Danger</span>
                    <span class="badge badge-soft-dark me-1">Dark</span>
                    <span class="badge badge-soft-purple me-1">Purple</span>
                    <span class="badge badge-soft-pink me-1">Pink</span>
                    <span class="badge badge-soft-orange me-1">Orange</span>
                </div>
                <!--  Soft Pill Badges -->
                <div>
                    <span class="badge badge-soft-primary rounded-pill me-1">Primary</span>
                    <span class="badge badge-soft-secondary rounded-pill me-1">Secondary</span>
                    <span class="badge badge-soft-success rounded-pill me-1">Success</span>
                    <span class="badge badge-soft-info rounded-pill me-1">Info</span>
                    <span class="badge badge-soft-warning rounded-pill me-1">Warning</span>
                    <span class="badge badge-soft-danger rounded-pill me-1">Danger</span>
                    <span class="badge badge-soft-dark rounded-pill me-1">Dark</span>
                    <span class="badge badge-soft-purple rounded-pill me-1">Purple</span>
                    <span class="badge badge-soft-pink rounded-pill me-1">Pink</span>
                    <span class="badge badge-soft-orange rounded-pill me-1">Orange</span>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Default &amp; Pill Badges
                </h5>
                <!-- Default Badges -->
                <div class="mb-2">
                    <span class="badge bg-primary me-1">Primary</span>
                    <span class="badge bg-secondary me-1">Secondary</span>
                    <span class="badge bg-success me-1">Success</span>
                    <span class="badge bg-info me-1">Info</span>
                    <span class="badge bg-warning me-1">Warning</span>
                    <span class="badge bg-danger me-1">Danger</span>
                    <span class="badge bg-dark me-1">Dark</span>
                    <span class="badge bg-purple me-1">Purple</span>
                    <span class="badge bg-pink me-1">Pink</span>
                    <span class="badge bg-orange me-1">Orange</span>
                </div>
                <!-- Pill Badges -->
                <div>
                    <span class="badge bg-primary rounded-pill me-1">Primary</span>
                    <span class="badge bg-secondary rounded-pill me-1">Secondary</span>
                    <span class="badge bg-success rounded-pill me-1">Success</span>
                    <span class="badge bg-info rounded-pill me-1">Info</span>
                    <span class="badge bg-warning rounded-pill me-1">Warning</span>
                    <span class="badge bg-danger rounded-pill me-1">Danger</span>
                    <span class="badge bg-dark rounded-pill me-1">Dark</span>
                    <span class="badge bg-purple rounded-pill me-1">Purple</span>
                    <span class="badge bg-pink rounded-pill me-1">Pink</span>
                    <span class="badge bg-orange rounded-pill me-1">Orange</span>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Buttons &amp; Position
                </h5>
                <div class="mb-2">
                    <button class="btn btn-primary me-1 mb-1" type="button">
                        Notifications <span class="badge bg-danger ms-1">4</span>
                    </button>
                    <button class="btn btn-outline-primary me-1 mb-1" type="button">
                        Notifications <span class="badge bg-primary ms-1">new</span>
                    </button>
                    <button class="btn btn-soft-primary me-1 mb-1" type="button">
                        Notifications <span class="badge bg-primary ms-1">11</span>
                    </button>
                    <a class="btn me-1 mb-1" href="javascript:void(0);">
                        Notifications <span class="badge bg-primary ms-1">90+</span>
                    </a>
                </div>
                <div>
                    <button class="btn btn-primary position-relative me-3" type="button">
                        Inbox
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">99+</span>
                    </button>
                    <button class="btn btn-primary position-relative" type="button">
                        Profile
                        <span
                            class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    </button>
                </div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection