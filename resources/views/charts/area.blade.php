@extends('layouts.vertical', ['title' => 'Basic Area'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Basic Area Chart</h5>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="basic-area"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Spline Area</h5>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="spline-area"></div>
                </div>
            </div><!-- end card-body -->
        </div><!-- end card -->
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h5 class="card-title">
                        Area Chart - Datetime X-axis
                    </h5>
                    <div class="toolbar apex-toolbar">
                        <button class="btn btn-sm btn-soft-secondary" id="one_month">1M</button>
                        <button class="btn btn-sm btn-soft-secondary" id="six_months">6M</button>
                        <button class="btn btn-sm btn-soft-secondary active" id="one_year">1Y</button>
                        <button class="btn btn-sm btn-soft-secondary" id="ytd">YTD</button>
                        <button class="btn btn-sm btn-soft-secondary" id="all">ALL</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="area-chart-datetime"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Area with Negative Values
                </h5>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="area-chart-negative"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Selection - Github Style
                </h5>
            </div>
            <div class="card-body">
                <div class="apex-charts" id="area-chart-github"></div>
                <div class="pt-2 pb-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img alt="file-image" class="avatar-xs rounded" src="/images/users/avatar-2.jpg" />
                        </div>
                        <div class="col ps-0">
                            <a class="text-muted fw-bold" href="javascript:void(0);">FoxPixel</a>
                        </div>
                    </div>
                </div>
                <div class="apex-charts" id="area-chart-github2"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Stacked Area
                </h5>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="stacked-area"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Irregular TimeSeries
                </h5>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="area-timeSeries"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    Area Chart with Null values
                </h5>
            </div>
            <div class="card-body">
                <div dir="ltr">
                    <div class="apex-charts" id="area-chart-nullvalues"></div>
                </div>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div><!-- end col -->
</div> <!-- end row -->
@endsection

@section('scripts')
<script src="https://apexcharts.com/samples/assets/stock-prices.js"></script>
<script src="https://apexcharts.com/samples/assets/series1000.js"></script>
<script src="https://apexcharts.com/samples/assets/github-data.js"></script>
<script src="https://apexcharts.com/samples/assets/irregular-data-series.js"></script>
@vite(['resources/js/components/apexchart-area.js'])
@endsection