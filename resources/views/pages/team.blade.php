@extends('layouts.vertical', ['title' => 'Our Team'])

@section('content')

     @include('partials.page-title', ['title' => 'Pages', 'sub-title' => 'Our Team'])

     <!-- Start here.... -->
     <div class="row">
          <div class="col-lg-12">
               <div class="card">
                    <div class="card-header">
                         <div class="row g-2">
                              <div class="col-sm-4">
                                   <div class="search-bar">
                                        <span><i class="" data-lucide="search-slash"></i></span>
                                        <input class="form-control" id="search" placeholder="Search ..."
                                             type="search" />
                                   </div>
                              </div>
                              <!--end col-->
                              <div class="col-sm-auto ms-auto">
                                   <div class="d-flex gap-1">
                                        <button class="btn btn-danger"><i class="me-1 align-middle"
                                                  data-lucide="plus"></i> Add Members</button>
                                        <button class="btn btn-soft-primary fs-14" type="button"><i class=""
                                                  data-lucide="grid-3x3"></i></button>
                                        <button class="btn btn-soft-primary fs-14" type="button"><i class=""
                                                  data-lucide="list"></i></button>
                                        <div class="dropdown">
                                             <button aria-expanded="false"
                                                  class="btn btn-soft-secondary fs-14"
                                                  data-bs-toggle="dropdown" id="dropdownMenuLink1"
                                                  type="button"><i class=""
                                                       data-lucide="ellipsis-vertical"></i></button>
                                             <ul aria-labelledby="dropdownMenuLink1"
                                                  class="dropdown-menu dropdown-menu-end">
                                                  <li><a class="dropdown-item" href="#">All</a></li>
                                                  <li><a class="dropdown-item" href="#">Last Week</a></li>
                                                  <li><a class="dropdown-item" href="#">Last Month</a></li>
                                                  <li><a class="dropdown-item" href="#">Last Year</a></li>
                                             </ul>
                                        </div>
                                   </div>
                              </div>
                              <!--end col-->
                         </div>
                    </div>
               </div>
               <div class="row">
                    <div class="col-xxl-4 col-md-6">
                         <div class="card">
                              <div class="position-relative">
                                   <img alt="" class="card-img rounded-bottom-0"
                                        src="/images/profile-bg.jpg" />
                                   <img alt=""
                                        class="avatar-lg rounded-circle position-absolute top-100 start-50 translate-middle border border-light border-3"
                                        src="/images/users/avatar-1.jpg" />
                              </div>
                              <div class="card-body text-center mt-4 mb-3 pt-3">
                                   <a class="text-dark fw-medium fs-18" href="#!">Willie T. Anderson</a>
                                   <p class="mb-2 mt-1"><i class="text-warning align-middle"
                                             data-lucide="mail"></i> willieandr@armyspy.com</p>
                                   <div class="my-2">
                                        <p class="badge bg-light text-dark px-2 py-1 mb-2 fs-14">HR Manager
                                        </p>
                                   </div>
                                   <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Facebook"><i class=""
                                                  data-lucide="facebook"></i></a>
                                        <a class="btn btn-soft-danger avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="danger-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Instagram"><i class=""
                                                  data-lucide="instagram"></i></a>
                                        <a class="btn btn-soft-info avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="info-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Twitter"><i class=""
                                                  data-lucide="twitter"></i></a>
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Linkedin"><i class=""
                                                  data-lucide="linkedin"></i></a>
                                   </div>
                              </div>
                              <div class="card-footer text-center border-top">
                                   <div class="row">
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">342</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide=""></i> Projects</h5>
                                        </div>
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">2y</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="calendar-days"></i>
                                                  Duration</h5>
                                        </div>
                                        <div class="col-lg-4 col-4">
                                             <h4 class="text-dark fw-medium">1032</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="clipboard-check"></i>
                                                  Tasks</h5>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-xxl-4 col-md-6">
                         <div class="card">
                              <div class="position-relative">
                                   <img alt="" class="card-img rounded-bottom-0"
                                        src="/images/profile-bg.jpg" />
                                   <img alt=""
                                        class="avatar-lg rounded-circle position-absolute top-100 start-50 translate-middle border border-light border-3"
                                        src="/images/users/avatar-2.jpg" />
                              </div>
                              <div class="card-body text-center mt-4 mb-3 pt-3">
                                   <a class="text-dark fw-medium fs-18" href="#!">Harold J. Hurley </a>
                                   <p class="mb-2 mt-1"><i class="text-warning align-middle"
                                             data-lucide="mail"></i> haroldlhurly@armyspy.com</p>
                                   <div class="my-2">
                                        <p class="badge bg-light text-dark px-2 py-1 mb-2 fs-14">Web Designer
                                        </p>
                                   </div>
                                   <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Facebook"><i class=""
                                                  data-lucide="facebook"></i></a>
                                        <a class="btn btn-soft-danger avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="danger-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Instagram"><i class=""
                                                  data-lucide="instagram"></i></a>
                                        <a class="btn btn-soft-info avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="info-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Twitter"><i class=""
                                                  data-lucide="twitter"></i></a>
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Linkedin"><i class=""
                                                  data-lucide="linkedin"></i></a>
                                   </div>
                              </div>
                              <div class="card-footer text-center border-top">
                                   <div class="row">
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">231</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="briefcase-business"></i>
                                                  Projects</h5>
                                        </div>
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">1.3y</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="calendar-days"></i>
                                                  Duration</h5>
                                        </div>
                                        <div class="col-lg-4 col-4">
                                             <h4 class="text-dark fw-medium">543</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="clipboard-check"></i>
                                                  Tasks</h5>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-xxl-4 col-md-6">
                         <div class="card">
                              <div class="position-relative">
                                   <img alt="" class="card-img rounded-bottom-0"
                                        src="/images/profile-bg.jpg" />
                                   <img alt=""
                                        class="avatar-lg rounded-circle position-absolute top-100 start-50 translate-middle border border-light border-3"
                                        src="/images/users/avatar-3.jpg" />
                              </div>
                              <div class="card-body text-center mt-4 mb-3 pt-3">
                                   <a class="text-dark fw-medium fs-18" href="#!">Sandra E. Simon</a>
                                   <p class="mb-2 mt-1"><i class="text-warning align-middle"
                                             data-lucide="mail"></i> snadraaimon@armyspy.com</p>
                                   <div class="my-2">
                                        <p class="badge bg-light text-dark px-2 py-1 mb-2 fs-14">UI/UX
                                             Designer</p>
                                   </div>
                                   <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Facebook"><i class=""
                                                  data-lucide="facebook"></i></a>
                                        <a class="btn btn-soft-danger avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="danger-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Instagram"><i class=""
                                                  data-lucide="instagram"></i></a>
                                        <a class="btn btn-soft-info avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="info-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Twitter"><i class=""
                                                  data-lucide="twitter"></i></a>
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Linkedin"><i class=""
                                                  data-lucide="linkedin"></i></a>
                                   </div>
                              </div>
                              <div class="card-footer text-center border-top">
                                   <div class="row">
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">123</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="briefcase-business"></i>
                                                  Projects</h5>
                                        </div>
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">7m</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="calendar-days"></i>
                                                  Duration</h5>
                                        </div>
                                        <div class="col-lg-4 col-4">
                                             <h4 class="text-dark fw-medium">231</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="clipboard-check"></i>
                                                  Tasks</h5>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-xxl-4 col-md-6">
                         <div class="card">
                              <div class="position-relative">
                                   <img alt="" class="card-img rounded-bottom-0"
                                        src="/images/profile-bg.jpg" />
                                   <img alt=""
                                        class="avatar-lg rounded-circle position-absolute top-100 start-50 translate-middle border border-light border-3"
                                        src="/images/users/avatar-4.jpg" />
                              </div>
                              <div class="card-body text-center mt-4 mb-3 pt-3">
                                   <a class="text-dark fw-medium fs-18" href="#!">Richard J. Lewis</a>
                                   <p class="mb-2 mt-1"><i class="text-warning align-middle"
                                             data-lucide="mail"></i> richaaedllewis@armyspy.com</p>
                                   <div class="my-2">
                                        <p class="badge bg-light text-dark px-2 py-1 mb-2 fs-14">Software
                                             Engineer</p>
                                   </div>
                                   <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Facebook"><i class=""
                                                  data-lucide="facebook"></i></a>
                                        <a class="btn btn-soft-danger avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="danger-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Instagram"><i class=""
                                                  data-lucide="instagram"></i></a>
                                        <a class="btn btn-soft-info avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="info-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Twitter"><i class=""
                                                  data-lucide="twitter"></i></a>
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Linkedin"><i class=""
                                                  data-lucide="linkedin"></i></a>
                                   </div>
                              </div>
                              <div class="card-footer text-center border-top">
                                   <div class="row">
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">452</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="briefcase-business"></i>
                                                  Projects</h5>
                                        </div>
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">4y</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="calendar-days"></i>
                                                  Duration</h5>
                                        </div>
                                        <div class="col-lg-4 col-4">
                                             <h4 class="text-dark fw-medium">331</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="clipboard-check"></i>
                                                  Tasks</h5>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-xxl-4 col-md-6">
                         <div class="card">
                              <div class="position-relative">
                                   <img alt="" class="card-img rounded-bottom-0"
                                        src="/images/profile-bg.jpg" />
                                   <img alt=""
                                        class="avatar-lg rounded-circle position-absolute top-100 start-50 translate-middle border border-light border-3"
                                        src="/images/users/avatar-5.jpg" />
                              </div>
                              <div class="card-body text-center mt-4 mb-3 pt-3">
                                   <a class="text-dark fw-medium fs-18" href="#!">Margo M. Garris</a>
                                   <p class="mb-2 mt-1"><i class="text-warning align-middle"
                                             data-lucide="mail"></i> margogarr@armyspy.com</p>
                                   <div class="my-2">
                                        <p class="badge bg-light text-dark px-2 py-1 mb-2 fs-14">Lead Product
                                             Design</p>
                                   </div>
                                   <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Facebook"><i class=""
                                                  data-lucide="facebook"></i></a>
                                        <a class="btn btn-soft-danger avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="danger-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Instagram"><i class=""
                                                  data-lucide="instagram"></i></a>
                                        <a class="btn btn-soft-info avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="info-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Twitter"><i class=""
                                                  data-lucide="twitter"></i></a>
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Linkedin"><i class=""
                                                  data-lucide="linkedin"></i></a>
                                   </div>
                              </div>
                              <div class="card-footer text-center border-top">
                                   <div class="row">
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">352</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="briefcase-business"></i>
                                                  Projects</h5>
                                        </div>
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">6m</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="calendar-days"></i>
                                                  Duration</h5>
                                        </div>
                                        <div class="col-lg-4 col-4">
                                             <h4 class="text-dark fw-medium">463</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="clipboard-check"></i>
                                                  Tasks</h5>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-xxl-4 col-md-6">
                         <div class="card">
                              <div class="position-relative">
                                   <img alt="" class="card-img rounded-bottom-0"
                                        src="/images/profile-bg.jpg" />
                                   <img alt=""
                                        class="avatar-lg rounded-circle position-absolute top-100 start-50 translate-middle border border-light border-3"
                                        src="/images/users/avatar-6.jpg" />
                              </div>
                              <div class="card-body text-center mt-4 mb-3 pt-3">
                                   <a class="text-dark fw-medium fs-18" href="#!">Ruby L. Fisher</a>
                                   <p class="mb-2 mt-1"><i class="text-warning align-middle"
                                             data-lucide="mail"></i> rubylfisher@armyspy.com</p>
                                   <div class="my-2">
                                        <p class="badge bg-light text-dark px-2 py-1 mb-2 fs-14">Project
                                             Manager</p>
                                   </div>
                                   <div class="d-flex justify-content-center gap-2">
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Facebook"><i class=""
                                                  data-lucide="facebook"></i></a>
                                        <a class="btn btn-soft-danger avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="danger-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Instagram"><i class=""
                                                  data-lucide="instagram"></i></a>
                                        <a class="btn btn-soft-info avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="info-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Twitter"><i class=""
                                                  data-lucide="twitter"></i></a>
                                        <a class="btn btn-soft-primary avatar-sm fs-20 d-inline-flex align-items-center justify-content-center p-0"
                                             data-bs-custom-class="primary-tooltip" data-bs-placement="bottom"
                                             data-bs-toggle="tooltip" href="#!" title="Linkedin"><i class=""
                                                  data-lucide="linkedin"></i></a>
                                   </div>
                              </div>
                              <div class="card-footer text-center border-top">
                                   <div class="row">
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">231</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted"
                                                       data-lucide="briefcase-business"></i>Projects</h5>
                                        </div>
                                        <div class="col-lg-4 col-4 border-end">
                                             <h4 class="text-dark fw-medium">4y</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="calendar-days"></i>
                                                  Duration</h5>
                                        </div>
                                        <div class="col-lg-4 col-4">
                                             <h4 class="text-dark fw-medium">573</h4>
                                             <h5
                                                  class="mb-0 d-flex align-items-center justify-content-center gap-1 text-muted fs-14">
                                                  <i class="text-muted" data-lucide="clipboard-check"></i>
                                                  Tasks</h5>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>

@endsection