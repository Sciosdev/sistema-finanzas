@extends('layouts.vertical', ['title' => 'All Apps'])

@section('content')
<div class="row">
    <div class="col-lg-6">
        <h4 class="fw-semibold mb-2">Authorized Apps</h4>
        <p class="mb-0 text-muted">You have used 3/3 free integrations. To add more integrations <a
                class="link-primary text-decoration-underline fw-semibold" href="#!">Upgrade to PRO</a></p>
    </div>
</div>
<div class="row mt-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/apps/app-1.svg" />
                    </div>
                    <div>
                        <div class="form-check form-switch checkbox-xl">
                            <input checked="" class="form-check-input" id="flexSwitchCheckChecked" role="switch"
                                type="checkbox" />
                        </div>
                    </div>
                </div>
                <h4 class="fw-semibold">Google Analytics <a class="link-warning fs-13 fw-normal ms-1 fw-normal"
                        href="#!">analytics.google.com</a></h4>
                <p class="mb-0">Google Analytics is a free web analytics service offered by Google that tracks and
                    reports website traffic ...</p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Free</p>
                        <p class="text-muted mb-0">Last Sync: 12:56pm, 12 May</p>
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success fs-12 py-1 px-2">Connected</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/apps/app-2.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input checked="" class="form-check-input" id="flexSwitchCheckChecked" role="switch"
                            type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">Dropbox <a class="link-warning fw-normal fs-13 ms-1" href="#!">dropbox.com</a>
                </h4>
                <p class="mb-0">Dropbox is a cloud-based file storage and collaboration platform designed to facilitate
                    easy file sharing ...</p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Premium</p>
                        <p class="text-muted mb-0">Last Sync: 11:23pm, 08 Dec</p>
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success fs-12 py-1 px-2">Connected</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/apps/app-3.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input checked="" class="form-check-input" id="flexSwitchCheckChecked" role="switch"
                            type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">Google Ads <a class="link-warning fs-13 fw-normal ms-1"
                        href="#!">ads.google.com</a></h4>
                <p class="mb-0">Google Ads is an online advertising platform developed by Google, where advertisers can
                    create ads to... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Premium</p>
                        <p class="text-muted mb-0">Last Sync: 02:12pm, 03 Jan</p>
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success fs-12 py-1 px-2">Connected</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-lg-6">
        <h4 class="fw-semibold mb-2">Brows Library</h4>
        <p class="mb-0 text-muted">200+ available integrations</p>
    </div>
</div>
<div class="row mt-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/apps/app-4.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input class="form-check-input" id="flexSwitchCheckChecked" role="switch" type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">Mailchimp <a class="link-warning fs-13 fw-normal ms-1"
                        href="#!">mailchimp.com</a></h4>
                <p class="mb-0">Mailchimp is a comprehensive marketing automation platform designed primarily for email
                    marketing ... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Premium</p>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning fs-12 py-1 px-2">Connect</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/apps/app-5.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input class="form-check-input" id="flexSwitchCheckChecked" role="switch" type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">MS Excel <a class="link-warning fs-13 fw-normal ms-1"
                        href="#!">microsoft.com</a></h4>
                <p class="mb-0">Microsoft Excel is a powerful spreadsheet application that is part of the Microsoft
                    Office suite. It is widely ... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Premium</p>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning fs-12 py-1 px-2">Connect</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/apps/app-6.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input class="form-check-input" id="flexSwitchCheckChecked" role="switch" type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">MS Team <a class="link-warning fs-13 fw-normal ms-1" href="#!">microsoft.com</a>
                </h4>
                <p class="mb-0">Microsoft Teams is a collaboration and communication platform designed to facilitate
                    teamwork and improve ... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Free</p>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning fs-12 py-1 px-2">Connect</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/brands/bitbucket.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input class="form-check-input" id="flexSwitchCheckChecked" role="switch" type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">Bitbucket <a class="link-warning fs-13 fw-normal ms-1"
                        href="#!">bitbucket.org</a></h4>
                <p class="mb-0">Bitbucket is a web-based version control repository hosting service owned by Atlassian.
                    It is designed ... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Free</p>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning fs-12 py-1 px-2">Connect</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/brands/dribbble.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input class="form-check-input" id="flexSwitchCheckChecked" role="switch" type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">Dribbble <a class="link-warning fs-13 fw-normal ms-1" href="#!">dribble.com</a>
                </h4>
                <p class="mb-0">Dribbble is an online community and platform for showcasing and discovering creative
                    work in design ... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Premium</p>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning fs-12 py-1 px-2">Connect</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="avatar-md d-flex me-2 bg-light align-items-center justify-content-center rounded">
                        <img alt="" class="avatar-sm" src="/images/brands/slack.svg" />
                    </div>
                    <div class="form-check form-switch checkbox-xl">
                        <input class="form-check-input" id="flexSwitchCheckChecked" role="switch" type="checkbox" />
                    </div>
                </div>
                <h4 class="fw-semibold">Slack <a class="link-warning fs-13 fw-normal ms-1" href="#!">slack.com</a></h4>
                <p class="mb-0">Slack is a collaboration and messaging platform designed to facilitate team
                    communication and productivity ... </p>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <div>
                        <p class="mb-0 fw-semibold text-dark">Premium</p>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning fs-12 py-1 px-2">Connect</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-1 align-items-center">
                    <div>
                        <a class="btn btn-outline-danger btn-sm fw-semibold" href="#!">Remove</a>
                    </div>
                    <div>
                        <a class="btn btn-outline-primary btn-sm fw-semibold" href="#!">Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="d-flex justify-content-end">
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
@endsection