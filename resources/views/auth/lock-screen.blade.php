@extends('layouts.auth', ['title' => 'Lock Screen'])

@section('content')

<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
     <div class="container">
          <div class="row justify-content-center">
               <div class="col-xl-5">
                    <div class="card auth-card">
                         <div class="card-body px-3 py-5">
                              <div class="mx-auto mb-5 auth-logo text-center">
                                   <a class="logo-dark" href="{{ route('second', [ 'dashboard' , 'index']) }}">
                                        <img alt="logo dark" height="30" src="/images/logo-dark.png" />
                                   </a>
                                   <a class="logo-light" href="{{ route('second', [ 'dashboard' , 'index']) }}">
                                        <img alt="logo light" height="30" src="/images/logo-white.png" />
                                   </a>
                              </div>
                              <h2 class="fw-bold text-center fs-18">Hi ! Gaston</h2>
                              <p class="text-muted text-center mt-1 mb-4">Enter your password to access the admin.</p>
                              <div class="px-4">
                                   <form action="index.html" class="authentication-form">
                                        <div class="mb-3">
                                             <label class="form-label visually-hidden" for="example-password">Password</label>
                                             <input class="form-control" id="example-password" placeholder="Enter your password" type="text" />
                                        </div>
                                        <div class="mb-1 text-center d-grid">
                                             <button class="btn btn-primary" type="submit">Sign In</button>
                                        </div>
                                   </form>
                              </div> <!-- end col -->
                         </div> <!-- end card-body -->
                    </div> <!-- end card -->
                    <p class="mb-0 text-center">Not you? return <a class="link-primary fst-italic text-decoration-underline fw-semibold" href="{{ route('second', ['auth', 'signup']) }}">Sign Up</a></p>
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>

@endsection