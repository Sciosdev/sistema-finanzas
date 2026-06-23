@extends('layouts.vertical', ['title' => 'Contacts'])

@section('content')
<!-- Start here.... -->
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recently Assigned</h5>
            </div>
            <div class="card-body">
                <div class="mb-1 border rounded">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-7 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task1" type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span class="text-primary fw-semibold">Kickoff Meeting : </span> Draft the new contract document for sales team</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-5">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-warning">New</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">Today</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1">High</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-5.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-6.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown" href="javascript: void(0);"><i class="align-middle fs-16" data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-7 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task12"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Client Proposal : </span> Draft and
                                        finalize the proposal for the new client.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-5">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-warning">New</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">Yesterday</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1">High</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-2.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-3.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-4.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-7 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task2"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Marketing Campaign : </span> Plan launch
                                        the upcoming marketing campaign.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-5">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-warning">New</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">/</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-success p-1">Low</span>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-7 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task3"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Client Presentation : </span> Prepare and
                                        deliver a presentation for the client.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-5">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-danger">Old</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">22 December</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1"><i class="bx bxs-circle fs-10"></i>
                                            Urgent</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-5.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-6.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-7.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-8.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">In Progress</h5>
            </div>
            <div class="card-body">
                <div class="mb-1 border rounded">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task4"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Report Analysis : </span> Analyze the
                                        latest sales reports and generate.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-success">Completed <i
                                                class="bx bx-check-double"></i></span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">18 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1"><i class="bx bxs-circle fs-10"></i>
                                            Urgent</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-3.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-8.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-9.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task5"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Design Review : </span> Review and approve
                                        the latest design drafts.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-success">Completed <i
                                                class="bx bx-check-double"></i></span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">20 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1">High</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-10.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-5.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-9.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-2.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task6"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Project Planning : </span> Outline the next
                                        steps for the ongoing project.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-warning">Progress</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">18 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1">High</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-5.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-8.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task7"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Email Responses : </span> Respond to all
                                        pending emails and inquiries.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-warning">Progress</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">20 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-success p-1">Low</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-7.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming</h5>
            </div>
            <div class="card-body">
                <div class="mb-1 border rounded">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-7 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task8"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Morning Stand-up : </span> Host the daily
                                        stand-up meeting with the team.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-5 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-primary">Upcoming</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">25 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-success p-1">Low</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-9.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task9"
                                        type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span
                                            class="text-primary fw-semibold">Client Follow-up : </span> Send follow-up
                                        emails to potential clients.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-primary">Upcoming</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">26 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1"><i class="bx bxs-circle fs-10"></i>
                                            Urgent</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-10.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-9.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-8.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-1.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown"
                                            href="javascript: void(0);"><i class="align-middle fs-16"
                                                data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task10" type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span class="text-primary fw-semibold">Content Creation : </span> Write and edit content for the company blog.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-primary">Upcoming</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">27 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-success p-1">Low</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-2.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-8.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown" href="javascript: void(0);"><i class="align-middle fs-16" data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
                <div class="mb-1 border rounded mt-3">
                    <div class="p-2">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center justify-content-start gap-2">
                                    <input class="form-check-input rounded-circle mt-0 fs-16" id="task11" type="checkbox" />
                                    <a class="link-dark fw-medium" href="#!"><span class="text-primary fw-semibold">Software Update : </span> Install and test the latest software updates.</a>
                                </div>
                            </div> <!-- end col-->
                            <div class="col-12 col-md-6 mb-md-0">
                                <div class="d-flex align-items-center gap-2 justify-content-md-end flex-wrap">
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-outline-primary">Upcoming</span>
                                    </div>
                                    <ul class="list-inline fs-13 text-end flex-shrink-0 mb-0">
                                        <li class="list-inline-item">
                                            <span class="fw-semibold">27 May</span>
                                        </li>
                                    </ul>
                                    <div class="flex-shrink-0">
                                        <span class="badge badge-soft-danger p-1">High</span>
                                    </div>
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-5.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-6.jpg" />
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <a aria-expanded="false" class="ps-1" data-bs-toggle="dropdown" href="javascript: void(0);"><i class="align-middle fs-16" data-lucide="ellipsis-vertical"></i></a>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="square-pen"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="eye"></i> View Tasks
                                            </a>
                                            <a class="dropdown-item" href="javascript: void(0);">
                                                <i class="align-middle me-2" data-lucide="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div> <!-- end d-flex-->
                            </div> <!-- end col-->
                        </div> <!-- end row-->
                    </div> <!-- end card-body-->
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <div aria-labelledby="EmailSidebaroffcanvasLabel" class="offcanvas-xxl offcanvas-start h-100" id="EmailSidebaroffcanvas" tabindex="-1">
                    <div class="card mb-0" data-simplebar="">
                        <div class="card-body">
                            <div class="d-grid mb-3">
                                <button class="btn btn-primary" type="button">Add Tasks</button>
                            </div>
                            <div class="search-bar">
                                <span class="align-middle"><i class="me-2" data-lucide="search"></i></span>
                                <input class="form-control" id="search" placeholder="Search task..." type="search" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h4 class="fw-semibold">Application Design <span class="badge badge-soft-danger p-1 fs-11 ms-1"><i class="bx bxs-circle fs-10"></i> Urgent</span> </h4>
                <p class="mb-0">The app has advanced, signaling the need to finalize our application for the upcoming
                    phase of development. This crucial step ensures alignment with our objectives and user expectations,
                    paving the way for a successful launch.</p>
                <div class="table-responsive mt-3">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <td>
                                    <p class="mb-0 fw-semibold mt-2"> Assigned : </p>
                                </td>
                                <td class="px-2 text-dark fw-medium fs-15">
                                    <div class="avatar-group ps-3">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-5.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-6.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-7.jpg" />
                                        </div>
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2" src="/images/users/avatar-8.jpg" />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="mb-0 fw-semibold"> Date : </p>
                                </td>
                                <td class="px-2 text-dark fw-medium">20 May 2024 - 23 May 2024</td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="mb-0 fw-semibold mt-2"> Create : </p>
                                </td>
                                <td class="px-2 text-dark fw-medium">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar h-auto w-auto">
                                            <img alt="" class="rounded-circle avatar-sm border border-light border-2"
                                                src="/images/users/avatar-8.jpg" />
                                        </div>
                                        <h5 class="mb-0 fs-13">Erma D. Rumph</h5>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="mb-0 fw-semibold">Labels : </p>
                                </td>
                                <td class="px-2 text-dark fw-medium fs-15">
                                    <span class="badge badge-soft-primary p-1">Branding</span>
                                    <span class="badge badge-soft-info p-1">UI / UX</span>
                                    <span class="badge badge-soft-success p-1">Design</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="d-flex mb-0 align-items-center gap-1 fw-semibold">Url Link : </p>
                                </td>
                                <td class="px-2 text-dark fw-medium"><a href="#!">https://website.com/ </a></td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="mb-0 fw-semibold">Progress : </p>
                                </td>
                                <td class="px-2">
                                    <div class="progress progress-soft progress-md mt-2">
                                        <div aria-valuemax="100" aria-valuemin="0" aria-valuenow=""
                                            class="progress-bar bg-success" role="progressbar" style="width: 70%"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection