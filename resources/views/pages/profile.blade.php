@extends('layouts.vertical', ['title' => 'Profile'])

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card overflow-hidden">
            <div class="card-body p-0">
                <div class="bg-primary profile-bg rounded-top position-relative">
                    <img alt=""
                        class="avatar-xl mx-auto border border-light border-3 rounded-circle position-absolute top-100 start-50 translate-middle"
                        src="/images/users/avatar-1.jpg" />
                </div>
                <div class="mt-3 px-4 d-flex flex-wrap align-items-end justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-semibold">Gaston Lapierre <i class="text-success align-middle"
                                data-lucide="badge-check"></i></h4>
                        <p class="mb-4">gastonlapierre333@rhyta.com</p>
                        <p class="text-muted fw-medium mb-2 d-flex align-items-start gap-2"><span
                                class="text-dark fs-12 fw-bold text-uppercase d-flex align-items-center gap-1">Occupation:
                            </span> Senior Web Developer In Joins LLP</p>
                        <p class="text-muted fw-medium mb-3 d-flex align-items-start gap-2"><span
                                class="text-dark fs-12 fw-bold text-uppercase d-flex align-items-center gap-1">Location:
                            </span> 2182 Arron Smith Drive Honolulu, HI 96813 </p>
                    </div>
                    <div>
                        <div class="row text-center g-2 mb-4">
                            <div class="col-lg-3 col-4 border-end">
                                <h5 class="mb-1 fw-bold">80</h5>
                                <p class="text-muted mb-0">Posts</p>
                            </div>
                            <div class="col-lg-3 col-4 border-end">
                                <h5 class="mb-1 fw-bold">3.6k</h5>
                                <p class="text-muted mb-0">Followers</p>
                            </div>
                            <div class="col-lg-3 col-4 border-end">
                                <h5 class="mb-1 fw-bold">1.1k</h5>
                                <p class="text-muted mb-0">Following</p>
                            </div>
                            <div class="col-lg-3 col-4">
                                <h5 class="mb-1 fw-bold">6.7k</h5>
                                <p class="text-muted mb-0">Views</p>
                            </div>
                        </div>
                        <ul class="nav nav-tabs gap-4">
                            <li class="nav-item">
                                <a aria-expanded="false" class="nav-link active" data-bs-toggle="tab"
                                    href="#profilePill">
                                    <span class="d-block d-sm-none"><i class="" data-lucide="user"></i></span>
                                    <span class="d-none d-sm-block">Profile</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a aria-expanded="true" class="nav-link" data-bs-toggle="tab" href="#networkPill">
                                    <span class="d-block d-sm-none"><i class="" data-lucide="link"></i></span>
                                    <span class="d-none d-sm-block">Network</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#subscriptionPill">
                                    <span class="d-block d-sm-none"><i class="" data-lucide="wallet-cards"></i></span>
                                    <span class="d-none d-sm-block">Subscription</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-content pt-0 text-muted">
            <div class="tab-pane show active" id="profilePill">
                <div class="row">
                    <div class="col-12 col-md-6 col-xl-3 order-0 order-md-0 order-xxl-0">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Personal Info</h4>
                            </div>
                            <div class="card-body">
                                <div class="pb-3 border-bottom">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">About Me :</h5>
                                    <p class="fw-medium mb-0">Hi, Gaston Lapierre I'm 36 and I work as a Digital Designer for the “debater” Agency in Ontario, Canada</p>
                                </div>
                                <div class="py-3 border-bottom">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">Birth Date : </h5>
                                    <p class="fw-medium mb-0">December 17, 1985</p>
                                </div>
                                <div class="py-3 border-bottom">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">Phone Number :</h5>
                                    <p class="fw-medium mb-0">+1-989-232435234</p>
                                </div>
                                <div class="py-3 border-bottom">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">Gender :</h5>
                                    <p class="fw-medium mb-0">Male</p>
                                </div>
                                <div class="py-3 border-bottom">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">Country :</h5>
                                    <p class="fw-medium mb-0">2182 Arron Smith Drive Honolulu, USA</p>
                                </div>
                                <div class="py-3 border-bottom">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">Occupation :</h5>
                                    <p class="fw-medium mb-0">Web Designer</p>
                                </div>
                                <div class="pt-3">
                                    <h5 class="text-dark fs-12 text-uppercase fw-bold">Joined :</h5>
                                    <p class="fw-medium mb-0">December 20, 2001</p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title mb-0">Activity</h4>
                                    <div class="ms-auto">
                                        <a class="text-muted fw-semibold" href="#!">See all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="">
                                    <h6 class="text-dark fw-semibold">Stories about you</h6>
                                    <a href="#!">
                                        <div class="d-flex user-chat align-items-center my-2 pt-2 ps-1 rounded">
                                            <div class="position-relative">
                                                <img alt="" class="avatar-sm rounded-circle flex-shrink-0" src="/images/small/img-4.jpg" />
                                            </div>
                                            <div class="d-block ms-3 flex-grow-1">
                                                <h5 class="text-dark fw-semibold mb-0">Mentions</h5>
                                                <p class="mb-0 text-muted fw-medium">2 stories mention you</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="mt-4">
                                    <h6 class="text-dark fw-semibold">New</h6>
                                    <a href="#!">
                                        <div class="d-flex user-chat align-items-center my-2 pt-2 ps-1 rounded gap-1">
                                            <div class="position-relative">
                                                <img alt="" class="avatar-sm rounded-circle flex-shrink-0" src="/images/users/avatar-2.jpg" />
                                            </div>
                                            <div class="d-block ms-2 flex-grow-1">
                                                <h5 class="text-dark fs-13 fw-semibold mb-1 lh-sm">uiamjad <span class="text-dark fw-normal">started following you. <span class="text-muted">1m</span></span></h5>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-primary-subtle text-primary fw-semibold fs-3 rounded-circle">
                                                    <i class="" data-lucide="user-plus"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#!">
                                        <div class="d-flex user-chat align-items-center my-2 pt-2 ps-1 rounded gap-1">
                                            <div class="position-relative">
                                                <img alt="" class="avatar-sm rounded-circle flex-shrink-0" src="/images/users/avatar-6.jpg" />
                                            </div>
                                            <div class="d-block ms-2 flex-grow-1">
                                                <h5 class="text-dark fs-13 fw-semibold mb-0 lh-sm">mr.alidoost <span class="text-dark fw-normal">liked your photo <span class="text-muted">2m</span></span></h5>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <img alt="" class="avatar-sm rounded-3 flex-shrink-0" src="/images/small/img-12.jpg" />
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#!">
                                        <div class="d-flex user-chat align-items-center my-2 pt-2 ps-1 rounded gap-1">
                                            <div class="position-relative">
                                                <img alt="" class="avatar-sm rounded-circle flex-shrink-0" src="/images/users/avatar-8.jpg" />
                                            </div>
                                            <div class="d-block ms-2 flex-grow-1">
                                                <h5 class="text-dark fs-13 fw-semibold mb-0 lh-sm">afshint2y <span class="text-dark fw-normal">liked your photo <span class="text-muted">3m</span></span></h5>
                                            </div>
                                            <div class="avatar flex-shrink-0 ms-auto">
                                                <img alt="" class="avatar-sm rounded-3 flex-shrink-0" src="/images/small/img-14.jpg" />
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#!">
                                        <div class="d-flex user-chat align-items-center my-2 pt-2 ps-1 rounded">
                                            <div class="position-relative">
                                                <img alt="" class="avatar-sm rounded-circle flex-shrink-0"
                                                    src="/images/users/avatar-9.jpg" />
                                            </div>
                                            <div class="d-block ms-2 flex-grow-1">
                                                <h5 class="text-dark fs-13 fw-semibold mb-1 lh-sm">Anna Rice <span
                                                        class="text-dark fw-normal">started following you. <span
                                                            class="text-muted">4m</span></span></h5>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span
                                                    class="avatar-title bg-primary-subtle text-primary fw-semibold fs-3 rounded-circle">
                                                    <i class="" data-lucide="user-plus"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#!">
                                        <div class="d-flex user-chat align-items-center my-2 pt-2 ps-1 rounded gap-1">
                                            <div class="position-relative">
                                                <img alt="" class="avatar-sm rounded-circle flex-shrink-0"
                                                    src="/images/users/avatar-1.jpg" />
                                            </div>
                                            <div class="d-block ms-2 flex-grow-1">
                                                <h5 class="text-dark fs-13 fw-semibold mb-0 lh-sm">sepide_moqadasi <span
                                                        class="text-dark fw-normal">liked your
                                                        photo <span class="text-muted">4m</span></span></h5>
                                            </div>
                                            <div class="avatar flex-shrink-0">
                                                <img alt="" class="avatar-sm rounded-3 flex-shrink-0"
                                                    src="/images/small/img-15.jpg" />
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 col-xl-6 order-1 order-md-2 order-xxl-1">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <a href="#!">
                                        <img alt="" class="avatar rounded-circle flex-shrink-0"
                                            src="/images/users/avatar-1.jpg" />
                                    </a>
                                    <div>
                                        <a class="text-dark fw-semibold fs-5" href="#!">Gaston Lapierre </a>
                                        <p class="mt-1 mb-0 text-muted fs-12"> 20 May at 01:12 PM </p>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <a aria-expanded="false" class="dropdown-toggle arrow-none card-drop"
                                                data-bs-toggle="dropdown" href="#">
                                                <i class="fs-24" data-lucide="ellipsis-vertical"></i>
                                            </a>
                                            <div
                                                class="dropdown-menu dropdown-menu-animated dropdown-menu-end shadow-sm">
                                                <!-- item-->
                                                <a class="dropdown-item" href="javascript:void(0);">Edit
                                                    Post</a>
                                                <!-- item-->
                                                <a class="dropdown-item" href="javascript:void(0);">Delete
                                                    Post</a>
                                                <!-- item-->
                                                <a class="dropdown-item" href="javascript:void(0);">Share
                                                    Post</a>
                                                <!-- item-->
                                                <a class="dropdown-item" href="javascript:void(0);">Action</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-0">Hi, I am flying to Los Angeles to attend castings. I hope it will happen
                                    and my dream comes true. Wish me luck 👋👍</p>
                                <div class="row mt-1 g-3">
                                    <div class="col-lg-6">
                                        <img alt="" class="img-fluid rounded" src="/images/small/img-15.jpg" />
                                    </div>
                                    <div class="col-lg-6">
                                        <img alt="" class="img-fluid rounded" src="/images/small/img-16.jpg" />
                                    </div>
                                    <div class="col-lg-6">
                                        <img alt="" class="img-fluid rounded" src="/images/small/img-12.jpg" />
                                    </div>
                                    <div class="col-lg-6">
                                        <img alt="" class="img-fluid rounded" src="/images/small/img-13.jpg" />
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer pt-0">
                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-4">
                                        <a class="link-dark d-flex align-items-center gap-1 fw-medium" href="#!"><i
                                                class="ri-heart-fill text-danger fs-16"></i> Love it</a>
                                        <a class="link-dark d-flex align-items-center gap-1 fw-medium" href="#!"><i
                                                class="ri-chat-1-fill fs-16"></i> Comment</a>
                                        <a class="link-dark d-flex align-items-center gap-1 fw-medium" href="#!"><i
                                                class="ri-share-line fs-16"></i> Share</a>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center">
                                        <p class="mb-0 fs-13">4.5k People Love it, including</p>
                                        <div class="avatar-group ps-3">
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-1.jpg" />
                                            </div>
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-2.jpg" />
                                            </div>
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-3.jpg" />
                                            </div>
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-4.jpg" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-sm-flex align-items-top my-3">
                                    <img alt="" class="avatar-sm rounded-circle flex-shrink-0"
                                        src="/images/users/avatar-7.jpg" />
                                    <div class="flex-grow-1 ms-sm-3">
                                        <span class="">
                                            <a class="text-dark fw-semibold fs-13" href="#!">Timothy Herby</a>
                                        </span>
                                        <p class="text-muted mb-2 fs-12">22 May at 05:40 AM</p>
                                        <p class="text-muted">Even though we're aware the voices in our minds aren't
                                            tangible, there are moments when their suggestions are simply too compelling
                                            to overlook.</p>
                                        <div class="d-flex gap-3 fs-14">
                                            <a class="d-flex align-items-center text-dark" href="#!"><i
                                                    class="ri-thumb-up-line fs-16 me-2"></i> Like</a>
                                            <a class="d-flex align-items-center text-dark" href="#!"><i
                                                    class="ri-reply-line fs-16 me-2"></i> Reply</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row align-items-center g-2 mt-3">
                                    <div class="col-lg-10">
                                        <div class="position-relative">
                                            <input class="form-control bg-light ps-5 rounded"
                                                placeholder="Write your comment here" type="text" />
                                            <a class="link-primary search-icon text-primary fs-22 align-middle translate-middle-y position-absolute top-50 start-0 ms-2"
                                                href="#!"><i class="" data-lucide="smile"></i></a>
                                        </div>
                                    </div>
                                    <div class="col-lg-2">
                                        <button
                                            class="btn btn-primary chat-send d-inline-flex align-items-center justify-content-center gap-1"
                                            type="submit"> <i class="" data-lucide="send"></i>Comment</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <a href="#!">
                                        <img alt="" class="avatar rounded-circle flex-shrink-0"
                                            src="/images/users/avatar-5.jpg" />
                                    </a>
                                    <div>
                                        <a class="text-dark fw-semibold fs-5" href="#!">Amelia Johnson</a>
                                        <p class="mt-1 mb-0 text-muted fs-12">15 March at 03:45 PM</p>
                                    </div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <a aria-expanded="false" class="dropdown-toggle arrow-none card-drop"
                                                data-bs-toggle="dropdown" href="#">
                                                <i class="fs-24" data-lucide="ellipsis-vertical"></i>
                                            </a>
                                            <div
                                                class="dropdown-menu dropdown-menu-animated dropdown-menu-end shadow-sm">
                                                <a class="dropdown-item" href="javascript:void(0);">Edit Post</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Delete Post</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Share Post</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Action</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-3">Excited to explore Paris! Can't wait to capture some amazing moments.
                                    Any recommendations? 😊📸</p>
                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-5.jpg" />
                            </div>
                            <div class="card-footer pt-0">
                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-4">
                                        <a class="link-dark d-flex align-items-center gap-1 fw-medium" href="#!">
                                            <i class="ri-heart-fill text-danger fs-16"></i> Love it</a>
                                        <a class="link-dark d-flex align-items-center gap-1 fw-medium" href="#!">
                                            <i class="ri-chat-1-fill fs-16"></i> Comment</a>
                                        <a class="link-dark d-flex align-items-center gap-1 fw-medium" href="#!">
                                            <i class="ri-share-line fs-16"></i> Share</a>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center">
                                        <p class="mb-0 fs-13">7.2k People Love it, including</p>
                                        <div class="avatar-group ps-3">
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-6.jpg" />
                                            </div>
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-7.jpg" />
                                            </div>
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-8.jpg" />
                                            </div>
                                            <div class="avatar h-auto w-auto">
                                                <img alt=""
                                                    class="rounded-circle avatar-sm border border-light border-2"
                                                    src="/images/users/avatar-9.jpg" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-sm-flex align-items-top my-3">
                                    <img alt="" class="avatar-sm rounded-circle flex-shrink-0"
                                        src="/images/users/avatar-10.jpg" />
                                    <div class="flex-grow-1 ms-sm-3">
                                        <span class="">
                                            <a class="text-dark fw-semibold fs-13" href="#!">David Carter</a>
                                        </span>
                                        <p class="text-muted mb-2 fs-12">16 March at 09:15 AM</p>
                                        <p class="text-muted">Paris is beautiful! Be sure to check out Montmartre and
                                            the Seine River at sunset. Safe travels!</p>
                                        <div class="d-flex gap-3 fs-14">
                                            <a class="d-flex align-items-center text-dark" href="#!"><i
                                                    class="ri-thumb-up-line fs-16 me-2"></i> Like</a>
                                            <a class="d-flex align-items-center text-dark" href="#!"><i
                                                    class="ri-reply-line fs-16 me-2"></i> Reply</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row align-items-center g-2 mt-3">
                                    <div class="col-lg-10">
                                        <div class="position-relative">
                                            <input class="form-control bg-light ps-5 rounded"
                                                placeholder="Write your comment here" type="text" />
                                            <a class="link-primary search-icon text-primary fs-22 align-middle translate-middle-y position-absolute top-50 start-0 ms-2"
                                                href="#!">
                                                <i class="" data-lucide="smile"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-2">
                                        <button
                                            class="btn btn-primary chat-send d-inline-flex align-items-center justify-content-center gap-1"
                                            type="submit">
                                            <i class="" data-lucide="send"></i> Comment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 order-2 order-md-1 order-xxl-2">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">People Associated</h4>
                            </div>
                            <div class="card-body">
                                <div class="border-bottom pb-3">
                                    <div class="avatar-group">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar border border-light border-2" src="/images/users/avatar-1.jpg" />
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-title bg-danger rounded-circle fw-semibold border border-light border-2">
                                                <iconify-icon class="fs-20" icon="logos:facebook"></iconify-icon>
                                            </span>
                                        </div>
                                    </div>
                                    <h5 class="mt-3 mb-2 fw-semibold fs-14">Gaston Lapierre</h5>
                                    <p class="mb-0">Future Program Designer at <a class="link-primary fw-semibold text-decoration-underline" href="#!">Google</a></p>
                                </div>
                                <div class="border-bottom py-3">
                                    <div class="avatar-group">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar border border-light border-2" src="/images/users/avatar-4.jpg" />
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-title bg-primary rounded-circle fw-semibold border border-light border-2">
                                                <i class="fs-20" data-lucide="facebook"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <h5 class="mt-3 mb-2 fw-semibold fs-14">Jason P. Mona</h5>
                                    <p class="mb-0">Dynamic Directives Architect at <a
                                            class="link-primary fw-semibold text-decoration-underline"
                                            href="#!">Facebook</a></p>
                                </div>
                                <div class="border-bottom py-3">
                                    <div class="avatar-group">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar border border-light border-2" src="/images/users/avatar-2.jpg" />
                                        </div>
                                        <div class="avatar">
                                            <span
                                                class="avatar-title bg-dark rounded-circle fw-semibold border border-light border-2">
                                                <i class="fs-20" data-lucide="github"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <h5 class="mt-3 mb-2 fw-semibold fs-14">Jerry R. Lee</h5>
                                    <p class="mb-0">Lead Configuration Architect at <a class="link-primary fw-semibold text-decoration-underline" href="#!">GitHub</a></p>
                                </div>
                                <div class="pt-3">
                                    <div class="avatar-group">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar border border-light border-2" src="/images/users/avatar-3.jpg" />
                                        </div>
                                        <div class="avatar">
                                            <span class="avatar-title bg-warning rounded-circle fw-semibold border border-light border-2">
                                                <i class="fs-20" data-lucide="gitlab"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <h5 class="mt-3 mb-2 fw-semibold fs-14">Louise M. Jenkins</h5>
                                    <p class="mb-0">Future Applications Consultant at <a class="link-primary fw-semibold text-decoration-underline" href="#!">Gitlab</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex">
                                    <h4 class="card-title mb-0">Friends <span class="badge bg-primary-subtle fs-13 text-primary">897</span></h4>
                                    <div class="ms-auto">
                                        <a class="text-muted fw-semibold" href="#!">See all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="">
                                    <div class="row g-2">
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-2.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-3.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-4.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-5.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-6.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-7.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-8.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-9.jpg" />
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <a href="#!">
                                                <img alt="" class="img-fluid rounded" src="/images/users/avatar-10.jpg" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="profilePill">
                    <p class="mb-0">
                    </p>
                </div>
                <div class="tab-pane" id="subscriptionPill">
                    <p class="mb-0">
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Container xxl -->
@endsection