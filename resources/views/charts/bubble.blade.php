@extends('layouts.vertical', ['title' => 'Simple Bubble'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Simple Bubble Chart</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="simple-bubble"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">3D Bubble Chart</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="second-bubble"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/components/apexchart-bubble.js'])
@endsection