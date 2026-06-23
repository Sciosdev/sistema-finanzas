@extends('layouts.vertical', ['title' => 'Mixed'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="line-column">Line &amp; Column Chart</h4>
                <div dir="ltr">
                    <div class="apex-charts" id="line-column-mixed"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Multiple Y-Axis Chart</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="multiple-yaxis-mixed"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Line &amp; Area Chart</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="line-area-mixed"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Line, Column &amp; Area Chart</h4>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="all-mixed"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/components/apexchart-mixed.js'])
@endsection