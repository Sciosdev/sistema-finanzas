@extends('layouts.vertical', ['title' => 'Analytics'])

@section('content')

<div class="row">
     <div class="col-xl-3 col-md-6">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                         <div>
                              <p class="mb-3 card-title">Total Revenue</p>
                              <h4 class="fw-bold text-primary d-flex align-items-center gap-2 mb-0">$42,750.65</h4>
                         </div>
                         <div>
                              <i data-lucide="wallet" class="fs-32 text-primary"></i>
                         </div>
                    </div>
                    <div class="row align-items-center mt-4">
                         <div class="col-12">
                              <div id="sales_funnel" class="apex-charts"></div>
                         </div>
                    </div>
               </div>
          </div>
     </div>

     <div class="col-xl-3 col-md-6">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                         <div>
                              <p class="mb-3 card-title">Total Orders</p>
                              <h4 class="fw-bold d-flex align-items-center gap-2 mb-0">5,312</h4>
                         </div>
                         <div>
                              <i data-lucide="briefcase-conveyor-belt" class="fs-32 text-primary"></i>
                         </div>
                    </div>
                    <div class="row align-items-center mt-4">
                         <div class="col-12">
                              <div id="order_funnel" class="apex-charts"></div>
                         </div>
                    </div>
               </div>
          </div>
     </div>

     <div class="col-xl-3 col-md-6">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                         <div>
                              <p class="mb-3 card-title">Cancelled Orders</p>
                              <h4 class="fw-bold text-primary d-flex align-items-center gap-2 mb-0">1,120</h4>
                         </div>
                         <div>
                              <i data-lucide="shield-minus" class="fs-32 text-primary"></i>
                         </div>
                    </div>
                    <div class="row align-items-center mt-4">
                         <div class="col-12">
                              <div id="cancel_funnel" class="apex-charts"></div>
                         </div>
                    </div>
               </div>
          </div>
     </div>

     <div class="col-xl-3 col-md-6">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                         <div>
                              <p class="mb-3 card-title">Total Customers</p>
                              <h4 class="fw-bold d-flex align-items-center gap-2 mb-0">6,482</h4>
                         </div>
                         <div>
                              <i data-lucide="users" class="fs-32 text-primary"></i>
                         </div>
                    </div>
                    <div class="row align-items-center mt-4">
                         <div class="col-12">
                              <div id="customer_funnel" class="apex-charts"></div>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</div>

<div class="row">
     <div class="col-xl-4 col-lg-6">
          <div class="card">
               <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                         <h4 class="card-title mb-0">Top Rated Products</h4>
                    </div>
                    <div>
                         <a href="#!" class="text-dark btn btn-sm btn-link text-uppercase fw-semibold px-0">View Products <i data-lucide="arrow-right"></i> </a>
                    </div>
               </div>
               <div class="card-body">
                    <div class="text-center">
                         <p class="text-muted mb-0">Yeah! You have received <span class="text-success fw-bold">+33</span> new orders today</p>
                    </div>

                    <div id="simple-bubble" class="apex-charts"></div>
               </div>
          </div>
     </div>

     <div class="col-xl-4 col-lg-12">
          <div class="card">
               <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                         <h4 class="card-title mb-0">Order Chart</h4>
                    </div>
                    <div class="dropdown">
                         <a href="#" class="dropdown-toggle btn btn-sm btn-link text-uppercase fw-semibold px-0" data-bs-toggle="dropdown" aria-expanded="false">
                              Weekly
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Week</a>
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Months</a>
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Years</a>
                         </div>
                    </div>
               </div>

               <div class="card-body">
                    <div class="text-center">
                         <p class="text-muted mb-0">Yeah! You have received <span class="text-success fw-bold">+33</span> new orders today</p>
                    </div>
                    <div id="datalabels-column2" class="apex-charts"></div>
               </div>
          </div>
     </div>

     <div class="col-xl-4 col-lg-6">
          <div class="card">
               <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                         <h4 class="card-title mb-0">Shop by Category</h4>
                    </div>
                    <div class="dropdown">
                         <a href="#" class="dropdown-toggle btn btn-sm btn-link text-uppercase fw-semibold px-0" data-bs-toggle="dropdown" aria-expanded="false">
                              Weekly
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Week</a>
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Months</a>
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Years</a>
                         </div>
                    </div>
               </div>

               <div class="card-body ps-0">
                    <div class="text-center">
                         <p class="text-muted mb-0">Yeah! You have delivered <span class="text-primary fw-bold">910</span> orders today</p>
                    </div>
                    <div id="basic-heatmap" class="apex-charts"></div>
               </div>
          </div>
     </div>
</div>


<div class="row">
     <div class="col-xl-3 col-lg-6">
          <div class="card">
               <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                         <h4 class="card-title mb-0">New Users</h4>
                    </div>
                    <div>
                         <a href="#!" class="text-dark btn btn-sm btn-link text-uppercase fw-semibold px-0">View Users <i data-lucide="arrow-right"></i> </a>
                    </div>
               </div>

               <div style="height: 388px;" data-simplebar>
                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-1.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Liam Johnson</a>
                              <p class="mb-2">Location: New York, USA</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.8/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-2.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Emma Williams</a>
                              <p class="mb-2">Location: London, UK</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.6/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-3.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Noah Brown</a>
                              <p class="mb-2">Location: Sydney, Australia</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.7/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-4.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Olivia Garcia</a>
                              <p class="mb-2">Location: Toronto, Canada</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.5/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-5.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Elijah Martinez</a>
                              <p class="mb-2">Location: Berlin, Germany</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.9/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-6.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Sophia Lee</a>
                              <p class="mb-2">Location: Paris, France</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.4/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-7.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">James Anderson</a>
                              <p class="mb-2">Location: Tokyo, Japan</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.3/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-8.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Charlotte Kim</a>
                              <p class="mb-2">Location: Seoul, South Korea</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.8/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-9.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Michael Robinson</a>
                              <p class="mb-2">Location: Madrid, Spain</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.6/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 border-bottom p-3">
                         <div>
                              <img src="/images/users/avatar-10.jpg" alt="" class="avatar-sm rounded-circle">
                         </div>
                         <div>
                              <a href="#!" class="text-dark fs-15 fw-medium">Amelia Davis</a>
                              <p class="mb-2">Location: Dubai, UAE</p>
                              <p class="mb-0 fw-semibold"><i data-lucide="star" class="text-warning me-1 fs-15"></i> <span class="align-middle">4.7/5</span></p>
                         </div>
                         <div class="align-self-center ms-auto">
                              <a href="#!" class="btn btn-sm btn-primary">Call</a>
                         </div>
                    </div>
               </div>
          </div>
     </div>

     <div class="col-xl-4 col-lg-6">
          <div class="card">
               <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                         <h4 class="card-title mb-0">Other Outlets</h4>
                    </div>
                    <div>
                         <a href="#!" class="text-dark btn btn-sm btn-link text-uppercase fw-semibold px-0">View Outlets <i data-lucide="arrow-right"></i> </a>
                    </div>
               </div>
               <div style="height: 327px;" data-simplebar>
                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">Miami - USA <span class="ms-auto fw-medium float-end"> <i data-lucide="star" class="me-1 text-warning"></i> 4.3</span></h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">101 Ocean Dr, Miami, FL 33139</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+ 305-555-7890</a>
                              </div>
                         </div>
                    </div>

                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">New York - USA <span class="ms-auto fw-medium float-end"> <i data-lucide="star" class="me-1 text-warning"></i> 4.8</span></h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">123 Broadway Ave, New York, NY 10001</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+ 212-555-1234</a>
                              </div>
                         </div>
                    </div>

                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">Los Angeles - USA <span class="ms-auto fw-medium float-end"> <i data-lucide="star" class="me-1 text-warning"></i> 4.7</span></h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">456 Sunset Blvd, Los Angeles, CA 90028</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+ 323-555-6789</a>
                              </div>
                         </div>
                    </div>

                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">Chicago - USA <span class="ms-auto fw-medium float-end"> <i data-lucide="star" class="me-1 text-warning"></i> 4.6</span></h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">101 Michigan Ave, Chicago, IL 60601</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+ 312-555-9876</a>
                              </div>
                         </div>
                    </div>

                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">Miami - USA <span class="ms-auto fw-medium float-end"> <i data-lucide="star" class="me-1 text-warning"></i> 4.5</span></h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">234 Ocean Dr, Miami, FL 33139</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+ 305-555-4567</a>
                              </div>
                         </div>
                    </div>

                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">London - UK
                                   <span class="ms-auto fw-medium float-end">
                                        <i data-lucide="star" class="me-1 text-warning"></i> 4.6
                                   </span>
                              </h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">221B Baker Street, London, NW1 6XE</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+44 20 7946 0958</a>
                              </div>
                         </div>
                    </div>

                    <div class="border-bottom p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">Sydney - Australia
                                   <span class="ms-auto fw-medium float-end">
                                        <i data-lucide="star" class="me-1 text-warning"></i> 4.9
                                   </span>
                              </h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">123 George St, Sydney, NSW 2000</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+61 2 5550 6789</a>
                              </div>
                         </div>
                    </div>

                    <div class="p-3">
                         <div>
                              <h6 class="text-uppercase fw-bold">Chicago - USA <span class="ms-auto fw-medium float-end"> <i data-lucide="star" class="me-1 text-warning"></i> 4.6</span></h6>
                              <i data-lucide="map-pin" class="me-1 fs-15"></i>
                              <span class="fw-medium ms-1">789 Michigan Ave, Chicago, IL 60611</span>
                              <div class="mt-1">
                                   <i data-lucide="phone" class="me-1 fs-15"></i>
                                   <a href="#!" class="fw-medium link-primary ms-1">+ 312-555-4321</a>
                              </div>
                         </div>
                    </div>
               </div>

               <div class="card-footer border-top text-center p-3">
                    <a href="#!" class="link-primary text-decoration-underline fw-medium">Show More <i class="ri-arrow-right-up-line"></i></a>
               </div>
          </div>
     </div>

     <div class="col-xl-5 col-lg-12">
          <div class="card">
               <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                         <h4 class="card-title mb-0">Delivered Status</h4>
                    </div>
                    <div class="dropdown">
                         <a href="#" class="dropdown-toggle text-dark btn btn-sm btn-link text-uppercase fw-semibold px-0" data-bs-toggle="dropdown" aria-expanded="false">
                              Daily
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Week</a>
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Months</a>
                              <!-- item-->
                              <a href="#!" class="dropdown-item">Years</a>
                         </div>
                    </div>
               </div>

               <div class="card-body p-0">
                    <div class="table-responsive">
                         <table class="table table-sm table-hover mb-0">
                              <thead>
                                   <tr>
                                        <th>Date</th>
                                        <th>Payment Via</th>
                                        <th>Status</th>
                                        <th>Amount ($)</th>
                                   </tr>
                              </thead>

                              <tbody>
                                   <tr>
                                        <td>2025-01-29</td>
                                        <td>PayPal</td>
                                        <td><span class="badge badge-soft-success">Success</span></td>
                                        <td>150.75</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-28</td>
                                        <td>Bank Transfer</td>
                                        <td><span class="badge badge-soft-danger">Failed</span></td>
                                        <td>320.50</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-27</td>
                                        <td>Debit Card</td>
                                        <td><span class="badge badge-soft-warning">Pending</span></td>
                                        <td>98.00</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-26</td>
                                        <td>Credit Card</td>
                                        <td><span class="badge badge-soft-success">Success</span></td>
                                        <td>275.25</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-25</td>
                                        <td>Google Pay</td>
                                        <td><span class="badge badge-soft-danger">Failed</span></td>
                                        <td>180.00</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-24</td>
                                        <td>Apple Pay</td>
                                        <td><span class="badge badge-soft-success">Success</span></td>
                                        <td>500.00</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-23</td>
                                        <td>Bank Transfer</td>
                                        <td><span class="badge badge-soft-warning">Pending</span></td>
                                        <td>200.40</td>
                                   </tr>

                                   <tr>
                                        <td>2025-01-22</td>
                                        <td>Credit Card</td>
                                        <td><span class="badge badge-soft-success">Success</span></td>
                                        <td>350.00</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>

               <div class="card-footer border-top text-center p-3">
                    <a href="#!" class="link-primary text-decoration-underline fw-medium">Show More <i class="ri-arrow-right-up-line"></i></a>
               </div>
          </div>
     </div>
</div>
@endsection

@section('scripts')
@vite([ 'resources/js/pages/dashboard.js'])
@endsection