@extends('layouts.vertical', ['title' => 'File Uploads'])

@section('css')
    @vite(['node_modules/flatpickr/dist/flatpickr.min.css'])
@endsection

@section('content')
<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Basic
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="basic-datepicker" placeholder="Basic datepicker" type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         DateTime
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="datetime-datepicker" placeholder="Date and Time" type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Human-friendly Dates
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="humanfd-datepicker" placeholder="October 9, 2018"
                              type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         MinDate and MaxDate
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="minmax-datepicker" placeholder="mindate - maxdate"
                              type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Disabling dates
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="disable-datepicker" placeholder="Disabling dates"
                              type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Selecting multiple dates
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="multiple-datepicker" placeholder="Multiple dates"
                              type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Selecting multiple dates - Conjunction
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="conjunction-datepicker" placeholder="2018-10-10 :: 2018-10-11"
                              type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Inline Calendar
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="inline-datepicker" placeholder="Inline calendar" type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Range Calendar
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="range-datepicker" placeholder="2018-10-03 to 2018-10-10"
                              type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Basic Timepicker
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="basic-timepicker" placeholder="Basic timepicker" type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         24-hour Time Picker
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="24hours-timepicker" placeholder="16:21" type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Preloading Time
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="preloading-timepicker" placeholder="Pick a time" type="text" />
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Time Picker w/ Limits
                    </h5>
                    <div class="w-50">
                         <input class="form-control" id="minmax-timepicker" placeholder="Limits" type="text" />
                    </div>
               </div><!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/form-flatepicker.js'])
@endsection