@extends('layouts.vertical', ['title' => 'Grid Js Tables'])

@section('css')
    @vite(['node_modules/gridjs/dist/theme/mermaid.min.css'])
@endsection

@section('content')

<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Basic
                </h5>
                <div id="table-gridjs"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Pagination
                </h5>
                <div id="table-pagination"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Search
                </h5>
                <div id="table-search"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Sorting
                </h5>
                <div id="table-sorting"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Loading State
                </h5>
                <div id="table-loading-state"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Hidden Columns
                </h5>
                <div id="table-hidden-column"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Fixed Header
                </h5>
                <div id="table-fixed-header"></div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/table-gridjs.js'])
@endsection