@extends('layouts.vertical', ['title' => 'Spinners'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Border Spinners</h5>
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Color Spinners</h5>
                <div class="spinner-border text-primary me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border text-secondar me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border text-success me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border text-info me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border text-warning me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border text-danger me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <h5 class="card-title mb-4">Growing Spinners</h5>
                    <div class="spinner-grow text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Color Growing Spinners</h5>
                <div class="spinner-grow text-primary me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-secondary me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-success me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-info me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-warning me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-danger me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Alignment</h5>
                <div class="d-flex justify-content-center p-2">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Size</h5>
                <div class="spinner-border text-primary spinner-border-sm me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-primary spinner-grow-sm me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border text-primary spinner-border me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow text-primary spinner-grow me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Placement</h5>
                <div class="d-flex align-items-center p-2">
                    <strong>Loading...</strong>
                    <div aria-hidden="true" class="spinner-border text-primary ms-auto" role="status"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Buttons Spinner</h5>
                <button class="btn btn-primary me-1" disabled="" type="button">
                    <span aria-hidden="true" class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Loading...
                </button>
                <button class="btn btn-primary" disabled="" type="button">
                    <span aria-hidden="true" class="spinner-grow spinner-grow-sm me-1" role="status"></span>
                    Loading...
                </button>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection