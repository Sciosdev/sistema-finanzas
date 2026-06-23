@extends('layouts.auth', ['title' => 'Sign Up'])

@section('content')
<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
     <div class="container">
          <div class="row justify-content-center">
               <div class="col-xl-5">
                    <div class="card auth-card">
                         <div class="card-body">
                              <div class="p-3">
                                   <div class="mx-auto mb-5 auth-logo text-center">
                                        <a class="logo-dark" href="{{ route('second', [ 'dashboard' , 'index']) }}">
                                             <img alt="logo dark" height="30" src="/images/logo-dark.png" />
                                        </a>
                                        <a class="logo-light" href="{{ route('second', [ 'dashboard' , 'index']) }}">
                                             <img alt="logo light" height="30" src="/images/logo-white.png" />
                                        </a>
                                   </div>
                                   <div class="text-center">
                                        <h3 class="fw-bold text-dark fs-20">Hi , Sign Up 👋 </h3>
                                        <p class="text-muted mt-1 mb-4">New to our platform? Sign up now! It only takes a minute.</p>
                                   </div>
                                   <div class="p-3">
                                        <form action="index.html" class="authentication-form">
                                             <div class="mb-3">
                                                  <label class="form-label" for="example-name">Name</label>
                                                  <div class="position-relative w-100">
                                                       <input class="form-control form-control-lg rounded" id="UserEmail" placeholder="Enter User Name" required="" type="email" />
                                                       <p class="text-muted p-0 position-absolute end-0 top-50 border-0 fs-4 translate-middle-y me-2 mb-0">
                                                            <iconify-icon class="fs-20 mt-1 text-muted" icon="solar:user-bold-duotone"></iconify-icon>
                                                       </p>
                                                  </div>
                                             </div>
                                             <div class="mb-3">
                                                  <label class="form-label" for="example-email">Email</label>
                                                  <div class="position-relative w-100">
                                                       <input class="form-control form-control-lg rounded" id="UserEmail" placeholder="Enter Email" required="" type="email" />
                                                       <p class="text-muted p-0 position-absolute end-0 top-50 border-0 fs-4 translate-middle-y me-2 mb-0">
                                                            <iconify-icon class="fs-20 mt-1 text-muted" icon="solar:letter-bold-duotone"></iconify-icon>
                                                       </p>
                                                  </div>
                                             </div>
                                             <div class="mb-3">
                                                  <label class="form-label" for="example-password">Password</label>
                                                  <div class="position-relative w-100">
                                                       <input class="form-control form-control-lg rounded" id="UserPass" placeholder="Enter password" required="" type="password" />
                                                       <button class="btn text-muted p-0 position-absolute end-0 top-50 border-0 fs-4 translate-middle-y me-2" type="button">
                                                            <iconify-icon class="fs-20 mt-1 text-muted" icon="solar:eye-bold-duotone"></iconify-icon>
                                                       </button>
                                                  </div>
                                             </div>
                                             <div class="mb-3">
                                                  <div class="form-check">
                                                       <input class="form-check-input" id="checkbox-signin" type="checkbox" />
                                                       <label class="form-check-label" for="checkbox-signin">I accept Terms and Condition</label>
                                                  </div>
                                             </div>
                                             <div class="mb-1 text-center d-grid">
                                                  <button class="btn btn-primary" type="submit">Sign Up</button>
                                             </div>
                                        </form>
                                        <p class="mt-3 fw-semibold no-span">Or Sign In with</p>
                                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-center gap-2 gap-sm-3 text-center">
                                             <a class="btn btn-outline-danger shadow px-2 d-flex align-items-center justify-content-center gap-1 fw-medium" href="javascript:void(0);">
                                                  <iconify-icon class="fs-20" icon="flat-color-icons:google">
                                                  </iconify-icon>
                                                  Google
                                             </a>
                                             <a class="btn btn-outline-primary shadow px-2 d-flex align-items-center justify-content-center gap-1 fw-medium" href="javascript:void(0);">
                                                  <iconify-icon class="fs-20" icon="logos:facebook"></iconify-icon>
                                                  Facebook
                                             </a>
                                             <a class="btn btn-outline-dark shadow px-2 d-flex align-items-center justify-content-center gap-1 fw-medium" href="javascript:void(0);">
                                                  <iconify-icon class="fs-20" icon="mdi:github"></iconify-icon>
                                                  Github
                                             </a>
                                        </div>
                                   </div>
                                   <p class="text-muted text-center mt-4 mb-0">I already have an account <a class="link-primary fst-italic text-decoration-underline fw-semibold" href="{{ route('second', ['auth', 'login']) }}">Sign In</a></p>
                              </div> <!-- end col -->
                         </div> <!-- end card-body -->
                    </div> <!-- end card -->
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>

@endsection