@extends('layouts.auth', ['title' => 'Coming Soon'])

@section('content')
<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card auth-card text-center">
                    <div class="card-body">
                        <div class="mx-auto my-5 text-center">
                            <a href="{{ route('second', ['dashboard', 'index'])}}">
                                <img alt="logo-dark" height="30" src="/images/logo-dark.png" />
                            </a>
                        </div>
                        <h3 class="fw-bold text-uppercase">We Are Launching Soon...</h3>
                        <p class="lead mt-3 w-75 mx-auto pb-4 fst-italic">Exciting news is on the horizon! We're
                            thrilled to announce that something incredible is coming your way very soon.</p>
                        <div class="row my-5">
                            <div class="col">
                                <h3 class="fw-bold fs-60" id="days">00</h3>
                                <p class="text-uppercase fw-semibold">Days</p>
                            </div>
                            <div class="col">
                                <h3 class="fw-bold fs-60" id="hours">00</h3>
                                <p class="text-uppercase fw-semibold">Hours</p>
                            </div>
                            <div class="col">
                                <h3 class="fw-bold fs-60" id="minutes">00</h3>
                                <p class="text-uppercase fw-semibold">Minutes</p>
                            </div>
                            <div class="col">
                                <h3 class="fw-bold fs-60" id="seconds">00</h3>
                                <p class="text-uppercase fw-semibold">Seconds</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
<!-- Page Js -->
@endsection

@section('scripts')
@vite(['resources/js/pages/coming-soon.js'])
@endsection