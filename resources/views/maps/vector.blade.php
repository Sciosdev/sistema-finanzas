@extends('layouts.vertical', ['title' => 'Vector Map'])

@section('css')
    @vite(['node_modules/jsvectormap/dist/jsvectormap.min.css'])
@endsection

@section('content')
<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         World Vector Map
                    </h5>
                    <div id="world-map-markers" style="height: 360px"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Canada Vector Map
                    </h5>
                    <div id="canada-vector-map" style="height: 360px"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Russia Vector Map
                    </h5>
                    <div id="russia-vector-map" style="height: 360px"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Iraq Vector Map
                    </h5>
                    <div id="iraq-vector-map" style="height: 360px"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Spain Vector Map
                    </h5>
                    <div id="spain-vector-map" style="height: 360px"></div>
               </div><!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/maps-vector.js',
       'resources/js/components/maps-canada.js',
       'resources/js/components/maps-iraq.js',
       'resources/js/components/maps-russia.js',
       'resources/js/components/maps-spain.js',
       'resources/js/components/maps-in-mill-en.js',
       'resources/js/components/maps-us-aea-en.js',
       'resources/js/components/maps-us-lcc-en.js',
       'resources/js/components/maps-us-merc-en.js',
       'resources/js/components/maps-us-mill-en.js',
])
@endsection