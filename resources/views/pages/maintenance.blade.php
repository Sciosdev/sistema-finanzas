@extends('layouts.auth', ['title' => 'Coming Soon'])

@section('content')
<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
     <div class="container">
          <div class="row justify-content-center">
               <div class="col-xl-12">
                    <div class="card auth-card">
                         <div class="card-body p-0">
                              <div class="row align-items-center g-0">
                                   <div class="col-lg-6 d-none d-lg-inline-block border-end">
                                        <div class="auth-page-sidebar">
                                             <img alt="auth" class="img-fluid" src="/images/maintenance.svg" />
                                        </div>
                                   </div>
                                   <div class="col-lg-6">
                                        <div class="p-5">
                                             <div class="mx-auto mb-4 text-center auth-logo">
                                                  <a class="logo-dark" href="{{ route('second', ['dashboard', 'index'])}}">
                                                       <img alt="logo dark" height="30" src="/images/logo-dark.png" />
                                                  </a>
                                                  <a class="logo-light" href="{{ route('second', ['dashboard', 'index'])}}">
                                                       <img alt="logo light" height="30" src="/images/logo-white.png" />
                                                  </a>
                                             </div>
                                             <h2 class="fw-bold text-center lh-base">We'll be back soon!</h2>
                                             <p class="text-muted text-center mt-1 mb-4">Our website is currently undergoing scheduled maintenance. We appreciate your patience and will be back online shortly.</p>
                                             <div class="text-center">
                                                  <a class="btn btn-primary" href="#">Get Updates</a>
                                             </div>
                                        </div>
                                   </div> <!-- end col -->
                              </div> <!-- end row -->
                         </div> <!-- end card-body -->
                    </div> <!-- end card -->
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection