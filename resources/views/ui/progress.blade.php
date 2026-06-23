@extends('layouts.vertical', ['title' => 'Progress'])

@section('content')
<div class="card">
    <div class="card-body">
        <!-- start progress -->
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    How it works
                </h5>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="0" class="progress-bar"
                        role="progressbar"></div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="25" class="progress-bar"
                        role="progressbar" style="width: 35%"></div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar"
                        role="progressbar" style="width: 50%"></div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="75" class="progress-bar"
                        role="progressbar" style="width: 75%"></div>
                </div>
                <div class="progress">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="25" class="progress-bar"
                        role="progressbar" style="width: 25%">25%</div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Backgrounds Color
                </h5>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="25" class="progress-bar bg-primary"
                        role="progressbar" style="width: 25%"></div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar bg-secondary"
                        role="progressbar" style="width: 50%"></div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="75" class="progress-bar bg-success"
                        role="progressbar" style="width: 75%"></div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100" class="progress-bar bg-info"
                        role="progressbar" style="width: 100%"></div>
                </div>
                <div class="progress">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="15" class="progress-bar"
                        role="progressbar" style="width: 15%"></div>
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="30" class="progress-bar bg-secondary"
                        role="progressbar" style="width: 30%"></div>
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="20" class="progress-bar bg-success"
                        role="progressbar" style="width: 20%"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Striped Progress Bar
                </h5>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="25"
                        class="progress-bar bg-primary progress-bar-striped" role="progressbar" style="width: 25%">
                    </div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50"
                        class="progress-bar bg-secondary progress-bar-striped" role="progressbar" style="width: 50%">
                    </div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="75"
                        class="progress-bar bg-success progress-bar-striped" role="progressbar" style="width: 75%">
                    </div>
                </div>
                <div class="progress mb-2">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="65"
                        class="progress-bar bg-info progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width: 65%"></div>
                </div>
                <div class="progress">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                        class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width: 100%"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Height
                </h5>
                <div class="progress mb-2 progress-xs">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="25" class="progress-bar"
                        role="progressbar" style="width: 25%;"></div>
                </div>
                <div class="progress mb-2 progress-sm">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="50" class="progress-bar bg-secondary"
                        role="progressbar" style="width: 50%;"></div>
                </div>
                <div class="progress mb-2 progress-md">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="75" class="progress-bar bg-success"
                        role="progressbar" style="width: 75%;"></div>
                </div>
                <div class="progress mb-2 progress-lg">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="35" class="progress-bar bg-info"
                        role="progressbar" style="width: 35%;"></div>
                </div>
                <div class="progress progress-xl">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="60" class="progress-bar bg-warning"
                        role="progressbar" style="width: 60%;"></div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
        <!-- end progress -->
    </div>
</div>
@endsection