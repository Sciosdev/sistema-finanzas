@extends('layouts.vertical', ['title' => 'Placeholder'])

@section('content')

<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Default</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card">
                            <svg aria-label="Placeholder" class="bd-placeholder-img card-img-top" focusable="false"
                                height="180" preserveaspectratio="xMidYMid slice" role="img" width="100%"
                                xmlns="http://www.w3.org/2000/svg">
                                <title>Placeholder</title>
                                <rect fill="#20c997" height="100%" width="100%"></rect>
                            </svg>
                            <div class="card-body">
                                <h5 class="card-title mb-2">Card title</h5>
                                <p class="card-text">Some quick example text to build on the card title and make up the
                                    bulk of the card's content.</p>
                                <a class="btn btn-primary" href="#">Go somewhere</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div aria-hidden="true" class="card">
                            <svg aria-label="Placeholder" class="bd-placeholder-img card-img-top" focusable="false"
                                height="180" preserveaspectratio="xMidYMid slice" role="img" width="100%"
                                xmlns="http://www.w3.org/2000/svg">
                                <title>Placeholder</title>
                                <rect fill="#868e96" height="100%" width="100%"></rect>
                            </svg>
                            <div class="card-body">
                                <div class="h5 card-title placeholder-glow">
                                    <span class="placeholder col-6"></span>
                                </div>
                                <p class="card-text placeholder-glow">
                                    <span class="placeholder col-7"></span>
                                    <span class="placeholder col-4"></span>
                                    <span class="placeholder col-4"></span>
                                    <span class="placeholder col-6"></span>
                                    <span class="placeholder col-8"></span>
                                </p>
                                <a class="btn btn-primary disabled placeholder col-6" href="#" tabindex="-1"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    How it works
                </h5>
                <p aria-hidden="true">
                    <span class="placeholder col-6"></span>
                </p>
                <a aria-hidden="true" class="btn btn-primary disabled placeholder col-4" href="#" tabindex="-1"></a>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Color
                </h5>
                <span class="placeholder col-12"></span>
                <span class="placeholder col-12 bg-primary"></span>
                <span class="placeholder col-12 bg-secondary"></span>
                <span class="placeholder col-12 bg-success"></span>
                <span class="placeholder col-12 bg-danger"></span>
                <span class="placeholder col-12 bg-warning"></span>
                <span class="placeholder col-12 bg-info"></span>
                <span class="placeholder col-12 bg-light"></span>
                <span class="placeholder col-12 bg-dark"></span>
            </div> <!-- end card body -->
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Width
                </h5>
                <span class="placeholder col-6"></span>
                <span class="placeholder w-75"></span>
                <span class="placeholder" style="width: 25%;"></span>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection