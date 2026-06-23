@extends('layouts.vertical', ['title' => 'Sweetalert'])

@section('content')
<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Basic
                    </h5>
                    <button class="btn btn-primary" id="sweetalert-basic" type="button">Click me</button>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         A Title with a Text Under
                    </h5>
                    <button class="btn btn-primary" id="sweetalert-title" type="button">Click me</button>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Message
                    </h5>
                    <div class="hstack gap-2">
                         <button class="btn btn-success" id="sweetalert-success" type="button">Success</button>
                         <button class="btn btn-warning" id="sweetalert-warning" type="button">Warning</button>
                         <button class="btn btn-info" id="sweetalert-info" type="button">Info</button>
                         <button class="btn btn-danger" id="sweetalert-error" type="button">Error</button>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Long content Images Message
                    </h5>
                    <button class="btn btn-primary" id="sweetalert-longcontent" type="button">Click me</button>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Parameter
                    </h5>
                    <button class="btn btn-primary" id="sweetalert-params" type="button">Click me</button>
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/extended-sweetalert.js'])
@endsection