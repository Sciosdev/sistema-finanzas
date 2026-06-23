@extends('layouts.vertical', ['title' => 'Contacts'])

@section('content')

<div class="row">
     <div class="col-md-6 col-xl-3">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                         <div>
                              <p class="text-dark fw-semibold fs-26 mb-1">5,230</p>
                              <p class="card-title mb-0">Total Contacts</p>
                         </div>
                         <div class="ms-auto">
                              <a class="btn btn-primary avatar-md rounded-circle d-flex align-items-center justify-content-center"
                                   href="#!">
                                   <iconify-icon class="fs-2 text-white"
                                        icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-md-6 col-xl-3">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                         <div>
                              <p class="text-dark fw-semibold fs-26 mb-1">1,045</p>
                              <p class="card-title mb-0">New Contacts</p>
                         </div>
                         <div class="ms-auto">
                              <a class="btn btn-primary avatar-md rounded-circle d-flex align-items-center justify-content-center"
                                   href="#!">
                                   <iconify-icon class="fs-2 text-white" icon="solar:user-plus-rounded-bold-duotone">
                                   </iconify-icon>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-md-6 col-xl-3">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                         <div>
                              <p class="text-dark fw-semibold fs-26 mb-1">3,890</p>
                              <p class="card-title mb-0">Active Contacts</p>
                         </div>
                         <div class="ms-auto">
                              <a class="btn btn-primary avatar-md rounded-circle d-flex align-items-center justify-content-center"
                                   href="#!">
                                   <iconify-icon class="fs-2 text-white" icon="solar:user-circle-bold-duotone">
                                   </iconify-icon>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-md-6 col-xl-3">
          <div class="card">
               <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                         <div>
                              <p class="text-dark fw-semibold fs-26 mb-1">295</p>
                              <p class="card-title mb-0">Unsubscribed Contacts</p>
                         </div>
                         <div class="ms-auto">
                              <a class="btn btn-primary avatar-md rounded-circle d-flex align-items-center justify-content-center"
                                   href="#!">
                                   <iconify-icon class="fs-2 text-white" icon="solar:user-block-rounded-bold-duotone">
                                   </iconify-icon>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</div>
<div class="row">
     <div class="col-xl-12">
          <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                         <p class="card-title mb-0">Customers</p>
                    </div>
                    <div class="dropdown">
                         <a aria-expanded="false" class="dropdown-toggle btn btn-sm btn-outline-light rounded"
                              data-bs-toggle="dropdown" href="#">
                              Reports
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <a class="dropdown-item" href="#!">Export</a>
                              <!-- item-->
                              <a class="dropdown-item" href="#!">Import</a>
                         </div>
                    </div>
               </div>
               <div class="">
                    <div class="table-responsive">
                         <table class="table align-middle mb-0 table-hover table-centered">
                              <thead class="bg-light-subtle">
                                   <tr>
                                        <th>ID</th>
                                        <th>Customer Name</th>
                                        <th>Contact No.</th>
                                        <th>Address</th>
                                        <th>Date</th>
                                        <th>Total Spent</th>
                                        <th>Action</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>#CUS-001</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-2.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Mike S. Witt</a> </td>
                                        <td>+ 717-744-2352</td>
                                        <td>44 Hide A Way Orlando. </td>
                                        <td>04/5/2024</td>
                                        <td>$390.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-002</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-3.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Amy G. Coggins</a> </td>
                                        <td>+ 336-409-9443</td>
                                        <td>4668 Havanna Street Winston Salem, NC 27107 </td>
                                        <td>02/5/2024</td>
                                        <td>$230.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-003</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-4.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Bennie S. Littlefi</a> </td>
                                        <td>+ 574-773-8792</td>
                                        <td>657 Sand Fork Road Nappanee, IN 4655 </td>
                                        <td>29/4/2024</td>
                                        <td>$340.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-004</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-5.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Juliana Strickland</a> </td>
                                        <td>+ 619-204-6604</td>
                                        <td>27 Holden Street San Diego, CA 92117</td>
                                        <td>28/4/2024</td>
                                        <td>$483.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-005</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-6.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Imelda M. Metcalf</a> </td>
                                        <td>+ 785-650-9186</td>
                                        <td>691 Sigley Road Hays, KS 67601</td>
                                        <td>25/4/2024</td>
                                        <td>$286.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-006</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-7.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Arturo M. Forrest</a> </td>
                                        <td>+ 719-651-0296</td>
                                        <td> Berry Street Colorado Springs, CO 80</td>
                                        <td>23/4/2024</td>
                                        <td>$594.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-007</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-8.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Derek K. Reyer</a> </td>
                                        <td>+ 803-306-7753</td>
                                        <td>Wexford Way Columbia, SC 29</td>
                                        <td>20/4/2024</td>
                                        <td>$423.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-008</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-9.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Martha Peiffer</a> </td>
                                        <td>+ 914-469-0980</td>
                                        <td>1 Mount Tabor Elmsford, NY 10</td>
                                        <td>14/4/2024</td>
                                        <td>$239.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-009</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-10.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Lucia C. McAnu</a> </td>
                                        <td>+ 218-766-6544</td>
                                        <td>44 Eagle Lane Bemidji, MN 566</td>
                                        <td>11/4/2024</td>
                                        <td>$530.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>#CUS-0010</td>
                                        <td><img alt="..." class="avatar-sm rounded-circle me-2"
                                                  src="/images/users/avatar-1.jpg" /><a class="link-dark fw-medium"
                                                  href="#!">Eufemia M. Lee</a> </td>
                                        <td>+ 410-309-6920</td>
                                        <td>Blue Spruce Lane Columbia, MD </td>
                                        <td>05/4/2024</td>
                                        <td>$476.00</td>
                                        <td>
                                             <div class="d-flex gap-3">
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="eye"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="square-pen"></i></a>
                                                  <a class="text-muted" href="#!"><i class="align-middle fs-20"
                                                            data-lucide="trash-2"></i></a>
                                             </div>
                                        </td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
                    <!-- end table-responsive -->
               </div>
               <div class="card-footer border-0">
                    <nav aria-label="Page navigation example">
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
                    </nav>
               </div>
          </div>
     </div>
</div>
@endsection