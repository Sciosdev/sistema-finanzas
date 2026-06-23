@extends('layouts.vertical', ['title' => 'Google Maps'])

@section('content')

<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-6">
                    <h5 class="card-title mb-4">
                         Basic Example
                    </h5>
                    <div class="gmaps" id="gmaps-basic"></div>
               </div>
               <div class="col-lg-6">
                    <h5 class="card-title mb-4">
                         Markers Google Map
                    </h5>
                    <div class="gmaps" id="gmaps-markers"></div>
               </div>
               <div class="col-lg-6">
                    <h5 class="card-title mb-4">
                         Street View Panoramas Google Map
                    </h5>
                    <div class="gmaps" id="panorama"></div>
               </div>
               <div class="col-lg-6">
                    <h5 class="card-title mb-4">
                         Google Map Types
                    </h5>
                    <div class="gmaps" id="gmaps-types"></div>
               </div>
               <div class="col-lg-6">
                    <h5 class="card-title mb-4">
                         Ultra Light With Labels
                    </h5>
                    <div class="gmaps" id="ultra-light"></div>
               </div>
               <div class="col-lg-6">
                    <h5 class="card-title mb-4">
                         Dark
                    </h5>
                    <div class="gmaps" id="dark"></div>
               </div> <!-- end col -->
          </div>
     </div>
</div>
@endsection

@section('scripts')
<script src="http://maps.google.com/maps/api/js"></script>
     @vite(['resources/js/components/maps-google.js'])
@endsection