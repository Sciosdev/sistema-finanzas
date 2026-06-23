@extends('layouts.vertical', ['title' => 'Basic Bar'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Basic Bar Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="basic-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Grouped Bar Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts text-white" id="grouped-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Stacked Bar Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" dir="ltr" id="stacked-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    100% Stacked Bar Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="full-stacked-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Bar with Negative Values
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="negative-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Reversed Bar Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="reversed-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Bar with Image Fill
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="image-fill-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Custom DataLabels Bar
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="datalables-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Patterned Bar Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="pattern-bar"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Bar with Markers
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="bar-markers"></div>
            </div>
        </div>
    </div><!-- end col -->
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/components/apexchart-bar.js'])
@endsection