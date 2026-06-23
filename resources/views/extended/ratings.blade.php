@extends('layouts.vertical', ['title' => 'Rating'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Basic Rater Example
                </h5>
                <div dir="ltr" id="basic-rater"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Rater with Step Example
                </h5>
                <div dir="ltr" id="rater-step"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Custom Messages Example
                </h5>
                <div dir="ltr" id="rater-message"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    ReadOnly Example
                </h5>
                <div dir="ltr" id="rater-unlimitedstar"></div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    On Hover Event Example
                </h5>
                <div dir="ltr">
                    <div class="align-middle" id="rater-onhover"></div>
                    <span class="ratingnum badge bg-info align-middle ms-2"></span>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Clear/Reset Rater Example
                </h5>
                <div dir="ltr">
                    <div class="align-middle" id="raterreset"></div>
                    <span class="clear-rating"></span>
                    <button class="btn btn-light btn-sm ms-2" id="raterreset-button">Reset</button>
                </div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/extended-rating.js'])
@endsection