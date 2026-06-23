@extends('layouts.vertical', ['title' => 'File Uploads'])

@section('content')
<div class="row">
     <div class="col-xl-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Dropzone File Upload
                    </h5>
                    <p class="card-subtitle">
                         DropzoneJS is an open source library that provides drag’n’drop file uploads with image
                         previews.
                    </p>
               </div>
               <div class="card-body">
                    <div class="dropzone">
                         <div class="fallback">
                              <input multiple="multiple" name="file" type="file" />
                         </div>
                         <div class="dz-message needsclick">
                              <i class="" data-lucide="cloud-upload"></i>
                              <h3>Drop files here or click to upload.</h3>
                              <span class="text-muted fs-13">
                                   (This is just a demo dropzone. Selected files are <strong>not</strong> actually
                                   uploaded.)
                              </span>
                         </div>
                    </div>
                    <ul class="list-unstyled mb-0" id="dropzone-preview">
                         <li class="mt-2" id="dropzone-preview-list">
                              <!-- This is used as the file preview template -->
                              <div class="border rounded">
                                   <div class="d-flex align-items-center p-2">
                                        <div class="flex-shrink-0 me-3">
                                             <div
                                                  class="avatar-sm d-flex justify-content-center align-items-center bg-light rounded">
                                                  <i class="fs-28" data-dz-thumbnail="" data-lucide="file"></i>
                                             </div>
                                        </div>
                                        <div class="flex-grow-1">
                                             <div class="pt-1">
                                                  <h5 class="fs-14 mb-1" data-dz-name=""> </h5>
                                                  <p class="fs-13 text-muted mb-0" data-dz-size=""></p>
                                                  <strong class="error text-danger" data-dz-errormessage=""></strong>
                                             </div>
                                        </div>
                                        <div class="flex-shrink-0 ms-3">
                                             <button class="btn btn-sm btn-danger" data-dz-remove="">Delete</button>
                                        </div>
                                   </div>
                              </div>
                         </li>
                    </ul>
                    <!-- end dropzon-preview -->
               </div> <!-- end card body -->
          </div> <!-- end card -->
     </div> <!-- end col -->
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/components/form-fileupload.js'])
@endsection