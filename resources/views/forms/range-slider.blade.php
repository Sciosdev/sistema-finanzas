@extends('layouts.vertical', ['title' => 'Range Slider'])

@section('css')
    @vite(['node_modules/nouislider/dist/nouislider.min.css'])
@endsection

@section('content')
<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Basic Range Slider
                    </h5>
                    <div id="rangeslider_basic"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Multi Elements Range
                    </h5>
                    <div id="rangeslider_multielement"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Vertical Range Slider
                    </h5>
                    <div id="rangeslider_vertical" style="height: 150px;"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Colorpicker
                    </h5>
                    <div class="sliders" id="red"></div>
                    <div class="sliders" id="green"></div>
                    <div class="sliders" id="blue"></div>
                    <div id="result"></div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Tooltip
                    </h5>
                    <div class="p-3 pt-5">
                         <div class="slider" id="slider-merging-tooltips"></div>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Value Range Slider
                    </h5>
                    <div id="nonlinear"></div>
                    <div class="d-flex justify-content-between">
                         <div class="example-val" id="lower-value"></div>
                         <div class="example-val" id="upper-value"></div>
                    </div>
               </div><!-- end col -->
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">Locking Sliders Together</h5>
                    <div class="slider" id="slider1"></div>
                    <span class="example-val mt-2" id="slider1-span"></span>
                    <div class="slider" id="slider2"></div>
                    <span class="example-val mt-2" id="slider2-span"></span>
                    <button class="btn btn-primary" id="lockbutton">Lock</button>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Soft Limits
                    </h5>
                    <div class="pb-5">
                         <div class="slider" id="soft"></div>
                    </div>
               </div> <!-- end card -->
          </div> <!-- end row -->
     </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/form-slider.js'])
@endsection