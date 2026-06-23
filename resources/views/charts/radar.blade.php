@extends('layouts.vertical', ['title' => 'Radar'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Basic Radar Chart</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="basic-radar"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Radar with Polygon-fill</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="radar-polygon"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Radar – Multiple Series</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="radar-multiple-series"></div>
                    <div class="text-center mt-2">
                        <button class="btn btn-sm btn-primary" onclick="update()">Update</button>
                    </div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div> <!-- end col -->
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/components/apexchart-radar.js'])
@endsection