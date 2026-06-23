@extends('layouts.vertical', ['title' => 'Invoices'])

@section('content')
<!-- Start here.... -->
<div class="row">
     <div class="col">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                         <h5 class="card-title mb-0">My Invoices</h5>
                         <div class="search-bar ms-auto">
                              <span style="top: 2px;"><i class="" data-lucide="search"></i></span>
                              <input class="form-control form-control-sm" id="search" placeholder="Search..."
                                   type="search" />
                         </div>
                         <div>
                              <a class="btn btn-sm btn-success" href="#!">
                                   New Invoice
                              </a>
                         </div>
                    </div> <!-- end row -->
               </div>
               <div>
                    <div class="table-responsive table-centered">
                         <table class="table table-striped text-nowrap mb-0">
                              <thead class="text-uppercase fs-12">
                                   <tr>
                                        <th class="border-0 py-2 text-dark">Invoice ID</th>
                                        <th class="border-0 py-2 text-dark">Customer</th>
                                        <th class="border-0 py-2 text-dark">Created Date</th>
                                        <th class="border-0 py-2 text-dark">Due Date</th>
                                        <th class="border-0 py-2 text-dark">Amount</th>
                                        <th class="border-0 py-2 text-dark">Payment Status</th>
                                        <th class="border-0 py-2 text-dark">Via</th>
                                        <th class="border-0 py-2 text-dark">Action</th>
                                   </tr>
                              </thead> <!-- end thead-->
                              <tbody>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN9023</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-8.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Ethan Walker</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>15 Mar, 2025 <small>10:30 AM</small></td>
                                        <td>22 Mar, 2025</td>
                                        <td>$1,250.75</td>
                                        <td>
                                             <span class="badge badge-soft-warning">Unpaid</span>
                                        </td>
                                        <td>Credit Card</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN3147</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-9.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Sophia Adams</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>07 Feb, 2025 <small>02:45 PM</small></td>
                                        <td>15 Feb, 2025</td>
                                        <td>$980.00</td>
                                        <td>
                                             <span class="badge badge-soft-danger">Overdue</span>
                                        </td>
                                        <td>PayPal</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN7654</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-10.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Daniel Carter</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>28 Jan, 2025 <small>11:10 AM</small></td>
                                        <td>05 Feb, 2025</td>
                                        <td>$715.25</td>
                                        <td>
                                             <span class="badge badge-soft-success">Paid</span>
                                        </td>
                                        <td>Wire Transfer</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN5532</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-1.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Mia Johnson</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>10 Apr, 2025 <small>09:50 AM</small></td>
                                        <td>18 Apr, 2025</td>
                                        <td>$560.90</td>
                                        <td>
                                             <span class="badge badge-soft-warning">Unpaid</span>
                                        </td>
                                        <td>Bank Transfer</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN7823</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-2.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">James Anderson</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>20 Feb, 2025 <small>02:15 PM</small></td>
                                        <td>28 Feb, 2025</td>
                                        <td>$1230.50</td>
                                        <td>
                                             <span class="badge badge-soft-warning">Unpaid</span>
                                        </td>
                                        <td>Stripe</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN9124</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-3.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Charlotte Brown</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>18 Feb, 2025 <small>11:45 AM</small></td>
                                        <td>28 Mar, 2025</td>
                                        <td>$875.00</td>
                                        <td>
                                             <span class="badge badge-soft-success">Paid</span>
                                        </td>
                                        <td>Payoneer</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN2345</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-4.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Benjamin Wilson</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>15 Feb, 2025 <small>03:30 PM</small></td>
                                        <td>25 Feb, 2025</td>
                                        <td>$650.75</td>
                                        <td>
                                             <span class="badge badge-soft-danger">Overdue</span>
                                        </td>
                                        <td>Bank Transfer</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN5689</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-5.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Amelia Clark</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>10 Feb, 2025 <small>01:10 PM</small></td>
                                        <td>20 Feb, 2025</td>
                                        <td>$350.00</td>
                                        <td>
                                             <span class="badge badge-soft-warning">Unpaid</span>
                                        </td>
                                        <td>Wise</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN7482</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-6.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Lucas Harris</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>08 Feb, 2025 <small>09:20 AM</small></td>
                                        <td>18 Feb, 2025</td>
                                        <td>$780.99</td>
                                        <td>
                                             <span class="badge badge-soft-success">Paid</span>
                                        </td>
                                        <td>Stripe</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <a class="fw-medium"
                                                  href="{{ route('second', ['invoice', 'details']) }}">#IN9823</a>
                                        </td>
                                        <td>
                                             <div class="d-flex align-items-center">
                                                  <img alt="" class="avatar-xs rounded-circle me-2"
                                                       src="/images/users/avatar-7.jpg" />
                                                  <div>
                                                       <h5 class="fs-14 m-0 fw-normal">Mia Robinson</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>05 Feb, 2025 <small>05:45 PM</small></td>
                                        <td>15 Feb, 2025</td>
                                        <td>$920.00</td>
                                        <td>
                                             <span class="badge badge-soft-danger">Overdue</span>
                                        </td>
                                        <td>PayPal</td>
                                        <td>
                                             <button class="btn btn-sm btn-soft-secondary me-1" type="button"><i
                                                       class="align-middle fs-16" data-lucide="square-pen"></i></button>
                                             <button class="btn btn-sm btn-soft-danger" type="button"><i
                                                       class="align-middle fs-16" data-lucide="trash-2"></i></button>
                                        </td>
                                   </tr>
                              </tbody> <!-- end tbody -->
                         </table> <!-- end table -->
                    </div> <!-- table responsive -->
                    <div
                         class="align-items-center justify-content-between row g-0 text-center text-sm-start p-3 border-top">
                         <div class="col-sm">
                              <div class="text-muted">
                                   Showing <span class="fw-semibold">10</span> of <span class="fw-semibold">52</span>
                                   invoices
                              </div>
                         </div>
                         <div class="col-sm-auto mt-3 mt-sm-0">
                              <ul class="pagination justify-content-end mb-0">
                                   <li class="page-item">
                                        <a class="page-link" href="javascript:void(0);">
                                             <iconify-icon class="fs-18" icon="lucide:chevron-left"></iconify-icon>
                                        </a>
                                   </li>
                                   <li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
                                   <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                   <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                   <li class="page-item">
                                        <a class="page-link" href="javascript:void(0);">
                                             <iconify-icon class="fs-18" icon="lucide:chevron-right"></iconify-icon>
                                        </a>
                                   </li>
                              </ul>
                         </div>
                    </div>
               </div> <!-- end card body -->
          </div> <!-- end card -->
     </div> <!-- end col -->
</div> <!-- end row -->
@endsection