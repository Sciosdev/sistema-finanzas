@extends('layouts.vertical', ['title' => 'Gallery'])

@section('css')
     @vite(['node_modules/glightbox/dist/css/glightbox.min.css'])
@endsection

@section('content')

<div class="row">
     <div class="col-lg-12">
          <div class="card">
               <div class="card-body">
                    <div class="row justify-content-center g-3">
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="/images/small/img-1.jpg">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-1.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-2.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Daavid Nummi</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Simple Image Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="/images/small/img-11.jpg">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-11.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-2.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white"> Charles Cook</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Simple Image Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="/images/small/img-15.jpg">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-15.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm"
                                                                           src="/images/users/avatar-1.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Pedro Crader</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Simple Image Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative"
                                             data-glightbox="title: Description Right; description: .custom-desc2; descPosition: right;"
                                             href="/images/small/img-7.jpg">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-7.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-3.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Shane Sines</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Images with Description</span>
                                                  </div>
                                             </div>
                                        </a>
                                        <div class="glightbox-desc custom-desc2">
                                             <p>
                                                  You can set the position of the description in different ways for
                                                  example
                                                  <strong style="text-decoration: underline">top, bottom, left or right</strong>
                                             </p>
                                             <p>
                                                  <a href="http://google.com" style="text-decoration: underline; font-weight: bold" target="_blank">Example Google link</a>
                                                  ipsum vehicula eros ultrices lacinia Vestibulum ante ipsum primis in
                                                  faucibus orci luctus et ultrices posuere cubilia Curae Duis quis ipsum
                                                  vehicula eros ultrices lacinia.
                                                  Vestibulum ante ipsum primis in faucibus orci luctus et ultrices
                                                  posuere
                                             </p>
                                             <p>
                                                  Primis pharetra facilisis lorem quis penatibus ad nulla inceptos, dui
                                                  per tempor taciti aliquet consequat sodales, curae tristique gravida
                                                  auctor interdum malesuada sagittis.
                                                  Felis pretium eros ligula natoque ad ante rutrum himenaeos, adipiscing
                                                  urna mauris porta quam efficitur odio, sagittis morbi tellus nisi
                                                  molestie mus faucibus.
                                             </p>
                                             <p>
                                                  Primis pharetra facilisis lorem quis penatibus ad nulla inceptos, dui
                                                  per tempor taciti aliquet consequat sodales, curae tristique gravida
                                                  auctor interdum malesuada sagittis.
                                                  Felis pretium eros ligula natoque ad ante rutrum himenaeos, adipiscing
                                                  urna mauris porta quam efficitur odio, sagittis morbi tellus nisi
                                                  molestie mus faucibus.
                                             </p>
                                        </div>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative"
                                             data-glightbox="title: ; description: .custom-desc3; descPosition: bottom;"
                                             href="/images/small/img-3.jpg">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-3.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-4.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white"> Jeffrey McNer</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Images with Description</span>
                                                  </div>
                                             </div>
                                        </a>
                                        <div class="glightbox-desc custom-desc3">
                                             <div class="d-flex justify-content-between mb-3">
                                                  <div class="d-flex align-items-center gap-3">
                                                       <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-4.jpg" />
                                                       <div>
                                                            <p class="fw-semibold mb-1 text-dark fs-16">Jeffrey McNer</p>
                                                            <p class="mb-0 fw-medium">Sheriff</p>
                                                       </div>
                                                  </div>
                                                  <ul class="d-flex text-warning m-0 fs-18 list-unstyled">
                                                       <li><i class="" data-lucide="star"></i></li>
                                                       <li><i class="" data-lucide="star"></i></li>
                                                       <li><i class="" data-lucide="star"></i></li>
                                                       <li><i class="" data-lucide="star"></i></li>
                                                       <li><i class="" data-lucide="star-half"></i></li>
                                                  </ul>
                                             </div>
                                             <p class="mb-0">"We partnered with FoxPixel and couldn't be more impressed.
                                                  Their professionalism, attention to detail, and commitment to
                                                  excellence set them apart. The team delivered outstanding results,
                                                  exceeding our expectations at every turn. Highly recommend FoxPixel
                                                  for their superior service and quality!"</p>
                                        </div>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative"
                                             data-glightbox="title: Description Left; description: .custom-desc4; descPosition: left;"
                                             href="/images/small/img-4.jpg">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-4.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-5.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Marcie Purcell</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Images with Description</span>
                                                  </div>
                                             </div>
                                        </a>
                                        <div class="glightbox-desc custom-desc4">
                                             <p>
                                                  You can set the position of the description in different ways for
                                                  example
                                                  <strong style="text-decoration: underline">top, bottom, left or right</strong>
                                             </p>
                                             <p>
                                                  <a href="http://google.com" style="text-decoration: underline; font-weight: bold" target="_blank">Example Google link</a>
                                                  ipsum vehicula eros ultrices lacinia Vestibulum ante ipsum primis in
                                                  faucibus orci luctus et ultrices posuere cubilia Curae Duis quis ipsum
                                                  vehicula eros ultrices lacinia.
                                                  Vestibulum ante ipsum primis in faucibus orci luctus et ultrices
                                                  posuere
                                             </p>
                                             <p>
                                                  Primis pharetra facilisis lorem quis penatibus ad nulla inceptos, dui
                                                  per tempor taciti aliquet consequat sodales, curae tristique gravida
                                                  auctor interdum malesuada sagittis.
                                                  Felis pretium eros ligula natoque ad ante rutrum himenaeos, adipiscing
                                                  urna mauris porta quam efficitur odio, sagittis morbi tellus nisi
                                                  molestie mus faucibus.
                                             </p>
                                             <p>
                                                  Primis pharetra facilisis lorem quis penatibus ad nulla inceptos, dui
                                                  per tempor taciti aliquet consequat sodales, curae tristique gravida
                                                  auctor interdum malesuada sagittis.
                                                  Felis pretium eros ligula natoque ad ante rutrum himenaeos, adipiscing
                                                  urna mauris porta quam efficitur odio, sagittis morbi tellus nisi
                                                  molestie mus faucibus.
                                             </p>
                                        </div>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="https://vimeo.com/524933864">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-5.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm"
                                                                           src="/images/users/avatar-6.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Eloise Murray</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Videos Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="https://vimeo.com/524933864">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-6.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-7.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">John N. Kimmel</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Videos Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="https://vimeo.com/524933864">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-13.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-8.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Max J. Copley</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Videos Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative" href="https://vimeo.com/524933864">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-8.jpg" />
                                                  <div
                                                       class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div
                                                            class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-9.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white"> Mildred J. Matth</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Videos Gallery</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative"
                                             data-glightbox="width: 700; height: auto;" href="#inline-example">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-9.jpg" />
                                                  <div class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-10.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Edith M. Marks</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Iframes and Inline Elements</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                              <div id="inline-example" style="display: none">
                                   <div class="inline-inner text-center">
                                        <h4 class="text-center">Example of inline content</h4>
                                        <div class="text-center">
                                             <p>
                                                  Duis quis ipsum vehicula eros ultrices lacinia. Vestibulum ante ipsum
                                                  primis in faucibus orci luctus et ultrices posuere cubilia Curae;
                                                  Donec nec sollicitudin felis. Donec vel nulla vel leo varius
                                                  tempor. Duis suscipit pharetra quam id imperdiet. Praesent vitae eros
                                                  metus. Donec placerat sagittis rhoncus. In condimentum eleifend ante
                                                  et ornare. Curabitur pharetra nibh non purus gravida.
                                             </p>
                                        </div>
                                        <a class="gtrigger-close inline-close-btn btn btn-primary" href="#">Close Box</a>
                                   </div>
                              </div>
                         </div>
                         <div class="col-xl-3 col-lg-6">
                              <div class="picture-item">
                                   <div class="rounded work-container">
                                        <a class="image-popup position-relative"
                                             href="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&amp;callback=myMap">
                                             <div class="overflow-hidden position-relative">
                                                  <img class="img-fluid rounded" src="/images/small/img-10.jpg" />
                                                  <div
                                                       class="position-absolute bottom-0 start-50 translate-middle-x w-100">
                                                       <div class="bg-black bg-opacity-25 w-100 p-2 rounded-bottom rounded-top-0">
                                                            <div class="d-flex justify-content-between">
                                                                 <div class="d-flex align-items-center gap-3">
                                                                      <img alt="" class="rounded-circle avatar-sm" src="/images/users/avatar-1.jpg" />
                                                                      <div>
                                                                           <p class="fw-semibold mb-0 text-white">Evelyn O. Ramos</p>
                                                                      </div>
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="position-absolute top-0 end-0 m-1">
                                                       <span class="badge bg-black bg-opacity-25 text-white py-1 px-2 fs-12 rounded">Iframes and Inline Elements</span>
                                                  </div>
                                             </div>
                                        </a>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</div>
@endsection

@section('scripts')
     @vite(['resources/js/pages/gallery.js'])
@endsection