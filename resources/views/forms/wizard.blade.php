@extends('layouts.vertical', ['title' => 'Wizard'])

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Horizontal Wizard</h5>
            </div><!-- end card-header -->
            <div class="card-body">
                <form>
                    <div id="horizontalwizard">
                        <ul class="nav nav-pills nav-justified icon-wizard form-wizard-header bg-light p-1"
                            role="tablist">
                            <li class="nav-item" role="presentation">
                                <a aria-selected="true" class="nav-link rounded-0 py-2 active" data-bs-toggle="tab"
                                    data-toggle="tab" href="#basictab1" role="tab">
                                    <iconify-icon class="fs-26" icon="iconamoon:profile-circle-duotone"></iconify-icon>
                                    Account
                                </a><!-- end nav-link -->
                            </li><!-- end nav-item -->
                            <li class="nav-item" role="presentation">
                                <a aria-selected="false" class="nav-link rounded-0 py-2" data-bs-toggle="tab"
                                    data-toggle="tab" href="#basictab2" role="tab" tabindex="-1">
                                    <iconify-icon class="fs-26" icon="iconamoon:profile-duotone"></iconify-icon>
                                    Profile
                                </a><!-- end nav-link -->
                            </li><!-- end nav-item -->
                            <li class="nav-item" role="presentation">
                                <a aria-selected="false" class="nav-link rounded-0 py-2" data-bs-toggle="tab"
                                    data-toggle="tab" href="#basictab3" role="tab" tabindex="-1">
                                    <iconify-icon class="fs-26" icon="iconamoon:link-fill"></iconify-icon>
                                    Social Links
                                </a><!-- end nav-link -->
                            </li><!-- end nav-item -->
                            <li class="nav-item" role="presentation">
                                <a aria-selected="false" class="nav-link rounded-0 py-2" data-bs-toggle="tab"
                                    data-toggle="tab" href="#basictab4" role="tab" tabindex="-1">
                                    <iconify-icon class="fs-26" icon="iconamoon:check-circle-1-duotone"></iconify-icon>
                                    Finish
                                </a><!-- end nav-link -->
                            </li><!-- end nav-item -->
                        </ul>
                        <div class="tab-content mb-0">
                            <div class="tab-pane active show" id="basictab1" role="tabpanel">
                                <h4 class="fs-16 fw-semibold mb-1">Account Information</h4>
                                <p class="text-muted">Setup your account information</p>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicUser">User Name</label>
                                            <input class="form-control" id="basicUser" placeholder="Enter User Name"
                                                type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicEmail">Email</label>
                                            <input class="form-control" id="basicEmail" placeholder="Enter your email"
                                                type="email" />
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicPassworda">Password</label>
                                            <input class="form-control" id="basicPassworda" placeholder="Enter Password"
                                                type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicConfirmPassword">Confirm
                                                Password</label>
                                            <input class="form-control" id="basicConfirmPassword"
                                                placeholder="Confirm a Password" type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                </div> <!-- end row -->
                            </div><!-- end tab-pane -->
                            <div class="tab-pane" id="basictab2" role="tabpanel">
                                <h4 class="fs-16 fw-semibold mb-1">Profile Information</h4>
                                <p class="text-muted">Setup your profile information</p>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="avatar-lg mb-3">
                                            <div
                                                class="avatar-title bg-body rounded-circle border border-3 border-dashed-light position-relative">
                                                <label class="position-absolute end-0 bottom-0" for="imageInput">
                                                    <div class="avatar-xs cursor-pointer">
                                                        <span class="avatar-title bg-light text-dark rounded-circle"><i
                                                                class="" data-lucid="camera"></i></span>
                                                    </div>
                                                </label>
                                                <input accept="image/*" class="hidden" id="imageInput"
                                                    onchange="previewImage(event)" type="file" />
                                                <img alt="Preview Image" class="rounded-circle img-fluid" id="preview"
                                                    src="/images/users/dummy-avatar.jpg" />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="basicFname">First Name</label>
                                                    <input class="form-control" id="basicFname" placeholder="Chris"
                                                        type="text" />
                                                </div>
                                            </div><!-- end col -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="basicLname">Last Name</label>
                                                    <input class="form-control" id="basicLname" placeholder="Keller"
                                                        type="text" />
                                                </div>
                                            </div><!-- end col -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="basicMnumber">Number</label>
                                                    <input class="form-control" id="basicMnumber"
                                                        placeholder="Mobile Number" type="number" />
                                                </div>
                                            </div><!-- end col -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="basicCountry">Country</label>
                                                    <select class="form-select" id="basicCountry">
                                                        <option value="United States">United States</option>
                                                        <option value="Canada">Canada</option>
                                                        <option value="Australia">Australia</option>
                                                        <option value="Germany">Germany</option>
                                                        <option value="Bangladesh">Bangladesh</option>
                                                        <option value="China">China</option>
                                                        <option value="Argentina">Argentina</option>
                                                        <option value="Bharat">Bharat</option>
                                                        <option value="Afghanistan">Afghanistan</option>
                                                        <option value="France">France</option>
                                                        <option value="Brazil">Brazil</option>
                                                        <option value="Belgium">Belgium</option>
                                                        <option value="Colombia">Colombia</option>
                                                        <option value="Albania">Albania</option>
                                                    </select>
                                                </div>
                                            </div><!-- end col -->
                                        </div><!-- end row -->
                                    </div> <!-- end col -->
                                </div> <!-- end row -->
                            </div><!-- end tab-pane -->
                            <div class="tab-pane" id="basictab3" role="tabpanel">
                                <h4 class="fs-16 fw-semibold mb-1">Social Media Links</h4>
                                <p class="text-muted">Fill your social media links</p>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicGitLink">GitHub</label>
                                            <input class="form-control" id="basicGitLink" placeholder="GitHub Link"
                                                type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicGoogleLink">Google</label>
                                            <input class="form-control" id="basicGoogleLink" placeholder="Google Link"
                                                type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicInstagramLink">Instagram</label>
                                            <input class="form-control" id="basicInstagramLink"
                                                placeholder="Instagram Link" type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basicSkypeLink">Skype</label>
                                            <input class="form-control" id="basicSkypeLink" placeholder="Skype Link"
                                                type="text" />
                                        </div>
                                    </div> <!-- end col -->
                                </div><!-- end row -->
                            </div><!-- end tab-pane -->
                            <div class="tab-pane" id="basictab4" role="tabpanel">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="text-center">
                                            <div class="avatar-md mx-auto mb-3">
                                                <div
                                                    class="avatar-title bg-primary bg-opacity-10 text-primary rounded-circle">
                                                    <iconify-icon class="fs-36" icon="iconamoon:like-duotone">
                                                    </iconify-icon>
                                                </div>
                                            </div>
                                            <h3 class="mt-0">Finished !</h3>
                                            <p class="w-75 mb-2 mx-auto">Filled Data Successfully.</p>
                                            <div class="mb-3">
                                                <div class="form-check d-inline-block">
                                                    <input class="form-check-input" id="customCheck1" type="checkbox" />
                                                    <label class="form-check-label" for="customCheck1">I agree with the
                                                        Terms and Conditions</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- end col -->
                                </div> <!-- end row -->
                            </div><!-- end tab-pane -->
                            <div class="d-flex flex-wrap align-items-center wizard justify-content-between gap-3 mt-3">
                                <div class="first">
                                    <a class="btn btn-soft-primary" href="javascript:void(0);">
                                        First
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <div class="previous">
                                        <a class="btn btn-primary disabled" href="javascript:void(0);">
                                            <i class="me-2" data-lucid="arrow-left"></i>Back To Previous
                                        </a>
                                    </div>
                                    <div class="next">
                                        <a class="btn btn-primary" href="javascript:void(0);">
                                            Next Step<i class="ms-2" data-lucid="arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="last">
                                    <a class="btn btn-soft-primary" href="javascript:void(0);">
                                        Finish
                                    </a>
                                </div>
                            </div>
                        </div> <!-- tab-content -->
                    </div> <!-- end #horizontal wizard-->
                </form>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Vertical Wizard</h5>
            </div><!-- end card-header -->
            <div class="card-body">
                <form id="verticalwizard">
                    <div class="row">
                        <div class="col-lg-3">
                            <ul class="nav nav-pills nav-justified flex-column icon-wizard form-wizard-header bg-light p-1"
                                role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a aria-selected="true" class="nav-link rounded-0 py-2 active" data-bs-toggle="tab"
                                        data-toggle="tab" href="#basictabHorizontal1" role="tab">
                                        <iconify-icon class="fs-26" icon="iconamoon:profile-circle-duotone">
                                        </iconify-icon>
                                        Account
                                    </a><!-- end nav-link -->
                                </li><!-- end nav-item -->
                                <li class="nav-item" role="presentation">
                                    <a aria-selected="false" class="nav-link rounded-0 py-2" data-bs-toggle="tab"
                                        data-toggle="tab" href="#basictabHorizontal2" role="tab" tabindex="-1">
                                        <iconify-icon class="fs-26" icon="iconamoon:profile-duotone"></iconify-icon>
                                        Profile
                                    </a><!-- end nav-link -->
                                </li><!-- end nav-item -->
                                <li class="nav-item" role="presentation">
                                    <a aria-selected="false" class="nav-link rounded-0 py-2" data-bs-toggle="tab"
                                        data-toggle="tab" href="#basictabHorizontal3" role="tab" tabindex="-1">
                                        <iconify-icon class="fs-26" icon="iconamoon:link-fill"></iconify-icon>
                                        Social Links
                                    </a><!-- end nav-link -->
                                </li><!-- end nav-item -->
                                <li class="nav-item" role="presentation">
                                    <a aria-selected="false" class="nav-link rounded-0 py-2" data-bs-toggle="tab"
                                        data-toggle="tab" href="#basictabHorizontal4" role="tab" tabindex="-1">
                                        <iconify-icon class="fs-26" icon="iconamoon:check-circle-1-duotone">
                                        </iconify-icon>
                                        Finish
                                    </a><!-- end nav-link -->
                                </li><!-- end nav-item -->
                            </ul>
                        </div>
                        <div class="col-lg-9">
                            <div class="tab-content mb-0">
                                <div class="tab-pane active show" id="basictabHorizontal1" role="tabpanel">
                                    <h4 class="fs-16 fw-semibold mb-1">Account Information</h4>
                                    <p class="text-muted">Setup your account information</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicUser2">User Name</label>
                                                <input class="form-control" id="basicUser2"
                                                    placeholder="Enter User Name" type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicEmail2">Email</label>
                                                <input class="form-control" id="basicEmail2"
                                                    placeholder="Enter your email" type="email" />
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicPassworda2">Password</label>
                                                <input class="form-control" id="basicPassworda2"
                                                    placeholder="Enter Password" type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicConfirmPassword2">Confirm
                                                    Password</label>
                                                <input class="form-control" id="basicConfirmPassword2"
                                                    placeholder="Confirm a Password" type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                    </div> <!-- end row -->
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="basictabHorizontal2" role="tabpanel">
                                    <h4 class="fs-16 fw-semibold mb-1">Profile Information</h4>
                                    <p class="text-muted">Setup your profile information</p>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="avatar-lg mb-3">
                                                <div
                                                    class="avatar-title bg-body rounded-circle border border-3 border-dashed-light position-relative">
                                                    <label class="position-absolute end-0 bottom-0" for="imageInput">
                                                        <div class="avatar-xs cursor-pointer">
                                                            <span
                                                                class="avatar-title bg-light text-dark rounded-circle"><i
                                                                    class="" data-lucid="camera"></i></span>
                                                        </div>
                                                    </label>
                                                    <input accept="image/*" class="hidden" id="imageInput"
                                                        onchange="previewImage(event)" type="file" />
                                                    <img alt="Preview Image" class="rounded-circle img-fluid"
                                                        id="preview" src="/images/users/dummy-avatar.jpg" />
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="basicFname2">First Name</label>
                                                        <input class="form-control" id="basicFname2" placeholder="Chris"
                                                            type="text" />
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="basicLname2">Last Name</label>
                                                        <input class="form-control" id="basicLname2"
                                                            placeholder="Keller" type="text" />
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="basicMnumber2">Number</label>
                                                        <input class="form-control" id="basicMnumber2"
                                                            placeholder="Mobile Number" type="number" />
                                                    </div>
                                                </div><!-- end col -->
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="basicCountry2">Country</label>
                                                        <select class="form-select" id="basicCountry2">
                                                            <option value="United States">United States</option>
                                                            <option value="Canada">Canada</option>
                                                            <option value="Australia">Australia</option>
                                                            <option value="Germany">Germany</option>
                                                            <option value="Bangladesh">Bangladesh</option>
                                                            <option value="China">China</option>
                                                            <option value="Argentina">Argentina</option>
                                                            <option value="Bharat">Bharat</option>
                                                            <option value="Afghanistan">Afghanistan</option>
                                                            <option value="France">France</option>
                                                            <option value="Brazil">Brazil</option>
                                                            <option value="Belgium">Belgium</option>
                                                            <option value="Colombia">Colombia</option>
                                                            <option value="Albania">Albania</option>
                                                        </select>
                                                    </div>
                                                </div><!-- end col -->
                                            </div><!-- end row -->
                                        </div> <!-- end col -->
                                    </div> <!-- end row -->
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="basictabHorizontal3" role="tabpanel">
                                    <h4 class="fs-16 fw-semibold mb-1">Social Media Links</h4>
                                    <p class="text-muted">Fill your social media links</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicGitLink2">GitHub</label>
                                                <input class="form-control" id="basicGitLink2" placeholder="GitHub Link"
                                                    type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicGoogleLink2">Google</label>
                                                <input class="form-control" id="basicGoogleLink2"
                                                    placeholder="Google Link" type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicInstagramLink3">Instagram</label>
                                                <input class="form-control" id="basicInstagramLink3"
                                                    placeholder="Instagram Link" type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basicSkypeLink4">Skype</label>
                                                <input class="form-control" id="basicSkypeLink4"
                                                    placeholder="Skype Link" type="text" />
                                            </div>
                                        </div> <!-- end col -->
                                    </div><!-- end row -->
                                </div><!-- end tab-pane -->
                                <div class="tab-pane" id="basictabHorizontal4" role="tabpanel">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="text-center">
                                                <div class="avatar-md mx-auto mb-3">
                                                    <div
                                                        class="avatar-title bg-primary bg-opacity-10 text-primary rounded-circle">
                                                        <iconify-icon class="fs-36" icon="iconamoon:like-duotone">
                                                        </iconify-icon>
                                                    </div>
                                                </div>
                                                <h3 class="mt-0">Finished !</h3>
                                                <p class="w-75 mb-2 mx-auto">Filled Data Successfully.</p>
                                                <div class="mb-3">
                                                    <div class="form-check d-inline-block">
                                                        <input class="form-check-input" id="customCheck2"
                                                            type="checkbox" />
                                                        <label class="form-check-label" for="customCheck2">I agree with
                                                            the Terms and Conditions</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> <!-- end col -->
                                    </div> <!-- end row -->
                                </div><!-- end tab-pane -->
                                <div
                                    class="d-flex flex-wrap align-items-center wizard justify-content-between gap-3 mt-3">
                                    <div class="first">
                                        <a class="btn btn-soft-primary" href="javascript:void(0);">
                                            First
                                        </a>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div class="previous">
                                            <a class="btn btn-primary disabled" href="javascript:void(0);">
                                                <i class="me-2" data-lucid="arrow-left"></i>Back To Previous
                                            </a>
                                        </div>
                                        <div class="next">
                                            <a class="btn btn-primary" href="javascript:void(0);">
                                                Next Step<i class="ms-2" data-lucid="arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="last">
                                        <a class="btn btn-soft-primary" href="javascript:void(0);">
                                            Finish
                                        </a>
                                    </div>
                                </div>
                            </div> <!-- tab-content -->
                        </div>
                    </div> <!-- end #horizontalwizard-->
                </form>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div><!-- end col -->
</div> <!-- end row -->
@endsection

@section('scripts')
@vite(['resources/js/pages/form-wizard.js'])
@endsection