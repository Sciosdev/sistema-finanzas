@extends('layouts.vertical', ['title' => 'Checkbox Radio'])

@section('content')
<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Checkbox
                    </h5>
                    <div class="form-check">
                         <input class="form-check-input" id="customCheck1" type="checkbox" />
                         <label class="form-check-label" for="customCheck1">Check this custom checkbox</label>
                    </div>
                    <div class="form-check">
                         <input class="form-check-input" id="customCheck2" type="checkbox" />
                         <label class="form-check-label" for="customCheck2">Check this custom checkbox</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Inline Checkbox
                    </h5>
                    <div class="form-check form-check-inline">
                         <input class="form-check-input" id="customCheck3" type="checkbox" />
                         <label class="form-check-label" for="customCheck3">Check this custom checkbox</label>
                    </div>
                    <div class="form-check form-check-inline">
                         <input class="form-check-input" id="customCheck4" type="checkbox" />
                         <label class="form-check-label" for="customCheck4">Check this custom checkbox</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Disabled Checkbox
                    </h5>
                    <div class="form-check form-check-inline">
                         <input checked="" class="form-check-input" disabled="" id="customCheck5" type="checkbox" />
                         <label class="form-check-label" for="customCheck5">Check this custom checkbox</label>
                    </div>
                    <div class="form-check form-check-inline">
                         <input class="form-check-input" disabled="" id="customCheck6" type="checkbox" />
                         <label class="form-check-label" for="customCheck6">Check this custom checkbox</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Radio
                    </h5>
                    <div class="form-check">
                         <input class="form-check-input" id="flexRadioDefault1" name="flexRadioDefault" type="radio" />
                         <label class="form-check-label" for="flexRadioDefault1">
                              Default radio
                         </label>
                    </div>
                    <div class="form-check">
                         <input checked="" class="form-check-input" id="flexRadioDefault2" name="flexRadioDefault"
                              type="radio" />
                         <label class="form-check-label" for="flexRadioDefault2">
                              Default checked radio
                         </label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Colors Checkbox
                    </h5>
                    <!-- Colors-->
                    <div class="form-check mb-2">
                         <input checked="" class="form-check-input" id="customCheckcolor1" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor1">Default Checkbox</label>
                    </div>
                    <div class="form-check form-checkbox-success mb-2">
                         <input checked="" class="form-check-input" id="customCheckcolor2" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor2">Success Checkbox</label>
                    </div>
                    <div class="form-check form-checkbox-info mb-2">
                         <input checked="" class="form-check-input" id="customCheckcolor3" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor3">Info Checkbox</label>
                    </div>
                    <div class="form-check form-checkbox-secondary mb-2">
                         <input checked="" class="form-check-input" id="customCheckcolor6" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor6">Secondary Checkbox</label>
                    </div>
                    <div class="form-check form-checkbox-warning mb-2">
                         <input checked="" class="form-check-input" id="customCheckcolor4" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor4">Warning Checkbox</label>
                    </div>
                    <div class="form-check form-checkbox-danger mb-2">
                         <input checked="" class="form-check-input" id="customCheckcolor5" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor5">Danger Checkbox</label>
                    </div>
                    <div class="form-check form-checkbox-dark">
                         <input checked="" class="form-check-input" id="customCheckcolor7" type="checkbox" />
                         <label class="form-check-label" for="customCheckcolor7">Dark Checkbox</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Inline Radio
                    </h5>
                    <div class="form-check form-check-inline">
                         <input class="form-check-input" id="inlineRadio1" name="inlineRadioOptions" type="radio"
                              value="option1" />
                         <label class="form-check-label" for="inlineRadio1">Check this custom checkbox</label>
                    </div>
                    <div class="form-check form-check-inline">
                         <input class="form-check-input" id="inlineRadio2" name="inlineRadioOptions" type="radio"
                              value="option2" />
                         <label class="form-check-label" for="inlineRadio2">Check this custom checkbox</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Disabled Radio
                    </h5>
                    <div class="form-check form-check-inline">
                         <input checked="" class="form-check-input" disabled="" id="customCheck5" type="radio" />
                         <label class="form-check-label" for="customCheck5">Check this custom checkbox</label>
                    </div>
                    <div class="form-check form-check-inline">
                         <input class="form-check-input" disabled="" id="customCheck6" type="radio" />
                         <label class="form-check-label" for="customCheck6">Check this custom checkbox</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Colors Radio
                    </h5>
                    <div class="form-check mb-2">
                         <input checked="" class="form-check-input" id="customRadiocolor1" name="customRadiocolor1"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor1">Default Radio</label>
                    </div>
                    <div class="form-check form-radio-success mb-2">
                         <input checked="" class="form-check-input" id="customRadiocolor2" name="customRadiocolor2"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor2">Success Radio</label>
                    </div>
                    <div class="form-check form-radio-info mb-2">
                         <input checked="" class="form-check-input" id="customRadiocolor3" name="customRadiocolor3"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor3">Info Radio</label>
                    </div>
                    <div class="form-check form-radio-secondary mb-2">
                         <input checked="" class="form-check-input" id="customRadiocolor6" name="customRadiocolor6"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor6">Secondary Radio</label>
                    </div>
                    <div class="form-check form-radio-warning mb-2">
                         <input checked="" class="form-check-input" id="customRadiocolor4" name="customRadiocolor4"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor4">Warning Radio</label>
                    </div>
                    <div class="form-check form-radio-danger mb-2">
                         <input checked="" class="form-check-input" id="customRadiocolor5" name="customRadiocolor5"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor5">Danger Radio</label>
                    </div>
                    <div class="form-check form-radio-dark">
                         <input checked="" class="form-check-input" id="customRadiocolor7" name="customRadiocolor7"
                              type="radio" />
                         <label class="form-check-label" for="customRadiocolor7">Dark Radio</label>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-4">
                         Switch
                    </h5>
                    <div class="form-check form-switch">
                         <input class="form-check-input" id="flexSwitchCheckDefault" role="switch" type="checkbox" />
                         <label class="form-check-label" for="flexSwitchCheckDefault">Default switch checkbox
                              input</label>
                    </div>
                    <div class="form-check form-switch">
                         <input checked="" class="form-check-input" id="flexSwitchCheckChecked" role="switch"
                              type="checkbox" />
                         <label class="form-check-label" for="flexSwitchCheckChecked">Checked switch checkbox
                              input</label>
                    </div>
                    <div class="form-check form-switch">
                         <input class="form-check-input" disabled="" id="flexSwitchCheckDisabled" role="switch"
                              type="checkbox" />
                         <label class="form-check-label" for="flexSwitchCheckDisabled">Disabled switch checkbox
                              input</label>
                    </div>
                    <div class="form-check form-switch">
                         <input checked="" class="form-check-input" disabled="" id="flexSwitchCheckCheckedDisabled"
                              role="switch" type="checkbox" />
                         <label class="form-check-label" for="flexSwitchCheckCheckedDisabled">Disabled checked switch
                              checkbox input</label>
                    </div>
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection