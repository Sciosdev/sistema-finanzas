@extends('layouts.vertical', ['title' => 'Breadcrumb'])

@section('content')
<div class="card">
    <div class="card-body">
        <!-- start breadcrumbs -->
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title">
                    Default Example
                </h5>
                <!-- Default Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb py-0">
                        <li aria-current="page" class="breadcrumb-item active">Home</li>
                    </ol>
                </nav>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb py-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li aria-current="page" class="breadcrumb-item active">Library</li>
                    </ol>
                </nav>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Library</a></li>
                        <li aria-current="page" class="breadcrumb-item active">Data</li>
                    </ol>
                </nav>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Dividers Breadcrumb
                </h5>
                <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '&gt;';">
                    <ol class="breadcrumb py-0">
                        <li aria-current="page" class="breadcrumb-item active">Home</li>
                    </ol>
                </nav>
                <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '&gt;';">
                    <ol class="breadcrumb py-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li aria-current="page" class="breadcrumb-item active">Library</li>
                    </ol>
                </nav>
                <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '&gt;';">
                    <ol class="breadcrumb mb-0 py-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Library</a></li>
                        <li aria-current="page" class="breadcrumb-item active">Data</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
<!-- end breadcrumbs -->
@endsection