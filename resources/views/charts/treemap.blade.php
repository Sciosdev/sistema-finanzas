@extends('layouts.vertical', ['title' => 'Treemap'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Basic Treemap</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="basic-treemap"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Treemap Multiple Series</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="multiple-treemap"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Distributed Treemap</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="distributed-treemap"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title anchor" id="color-range">Color Range Treemap</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="color-range-treemap"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div> <!-- end col -->
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/components/apexchart-treemap.js'])
@endsection