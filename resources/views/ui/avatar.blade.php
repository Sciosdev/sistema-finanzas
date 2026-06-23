@extends('layouts.vertical', ['title' => 'Avatar'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Basic Example
                </h5>
                <div class="row">
                    <div class="col-md-3">
                        <img alt="image" class="img-fluid avatar-xs rounded" src="/images/users/avatar-2.jpg" />
                        <p>
                            <code>.avatar-xs</code>
                        </p>
                        <img alt="image" class="img-fluid avatar-sm rounded mt-2" src="/images/users/avatar-3.jpg" />
                        <p class="mb-2 mb-sm-0">
                            <code>.avatar-sm</code>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <img alt="image" class="img-fluid avatar-md rounded" src="/images/users/avatar-4.jpg">
                        <p>
                            <code>.avatar-md</code>
                        </p>
                        </img>
                    </div>
                    <div class="col-md-3">
                        <img alt="image" class="img-fluid avatar-lg rounded" src="/images/users/avatar-5.jpg">
                        <p>
                            <code>.avatar-lg</code>
                        </p>
                        </img>
                    </div>
                    <div class="col-md-3">
                        <img alt="image" class="img-fluid avatar-xl rounded" src="/images/users/avatar-6.jpg" />
                        <p class="mb-0">
                            <code>.avatar-xl</code>
                        </p>
                    </div>
                </div> <!-- end row-->
            </div> <!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Rounded Circle
                </h5>
                <div class="row">
                    <div class="col-md-4">
                        <img alt="image" class="img-fluid avatar-md rounded-circle" src="/images/users/avatar-7.jpg" />
                        <p class="mt-1">
                            <code>.avatar-md .rounded-circle</code>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <img alt="image" class="img-fluid avatar-lg rounded-circle" src="/images/users/avatar-8.jpg" />
                        <p>
                            <code>.avatar-lg .rounded-circle</code>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <img alt="image" class="img-fluid avatar-xl rounded-circle" src="/images/users/avatar-9.jpg" />
                        <p class="mb-0">
                            <code>.avatar-xl .rounded-circle</code>
                        </p>
                    </div>
                </div> <!-- end row-->
            </div> <!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Images Shapes</h5>
                <div class="d-flex flex-wrap gap-5 align-items-end">
                    <div>
                        <img alt="image" class="img-fluid rounded" src="/images/small/img-2.jpg" width="200" />
                        <p class="mb-0">
                            <code>.rounded</code>
                        </p>
                    </div>
                    <div>
                        <img alt="image" class="img-fluid rounded" src="/images/users/avatar-5.jpg" width="120" />
                        <p class="mb-0">
                            <code>.rounded</code>
                        </p>
                    </div>
                    <div>
                        <img alt="image" class="img-fluid rounded-circle" src="/images/users/avatar-7.jpg"
                            width="120" />
                        <p class="mb-0">
                            <code>.rounded-circle</code>
                        </p>
                    </div>
                    <div>
                        <img alt="image" class="img-fluid img-thumbnail" src="/images/small/img-3.jpg" width="200" />
                        <p class="mb-0">
                            <code>.img-thumbnail</code>
                        </p>
                    </div>
                    <div>
                        <img alt="image" class="img-fluid rounded-circle img-thumbnail" src="/images/users/avatar-8.jpg"
                            width="120" />
                        <p class="mb-0">
                            <code>.rounded-circle .img-thumbnail</code>
                        </p>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div>
@endsection