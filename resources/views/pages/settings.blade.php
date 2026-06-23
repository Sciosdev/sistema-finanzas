@extends('layouts.vertical', ['title' => 'Banner'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Restaurant Settings</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <p class="fw-medium mb-2">Upload Restaurant Logo</p>
                            <div class="profile-photo-edit w-50 auth-logo border bg-light-subtle p-2 rounded">
                                <input class="profile-img-file-input" id="profile-img-file-input" type="file" />
                                <label class="profile-photo-edit px-4 py-2" for="profile-img-file-input">
                                    <img alt="user-profile-image" class="logo-dark me-1" height="24" src="/images/logo-dark.png" />
                                    <img alt="user-profile-image" class="logo-light me-1" height="24" src="/images/logo-white.png" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="restaurant-name">Restaurant Name</label>
                                <input class="form-control" id="restaurant-name" placeholder="Enter name" type="text" value="Admin" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="owner-name">Restaurant Owner Full Name</label>
                                <input class="form-control" id="owner-name" placeholder="Full name" type="text" value="Randy P. Ralph" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label" for="schedule-number">Owner Phone number</label>
                            <input class="form-control" id="schedule-number" name="schedule-number" placeholder="Number" type="text" value="+ 312-494-3321" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="schedule-email">Owner Email</label>
                                <input class="form-control" id="schedule-email" name="schedule-email" placeholder="Email" type="email" value="randypralph@jourrapide.com" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label class="form-label" for="address">Full Address</label>
                            <textarea class="form-control bg-light-subtle" id="address" placeholder="Type address" rows="3">4822 West Drive Chicago, IL 60610</textarea>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="your-zipcode">Zip-Code</label>
                                <input class="form-control" id="your-zipcode" placeholder="zip-code" type="number" value="60610" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="choices-city">City</label>
                                <select class="form-select" data-choices="" data-choices-groups=""
                                    data-placeholder="Select City" id="choices-city" name="choices-city">
                                    <option value="">Choose a city</option>
                                    <optgroup label="UK">
                                        <option value="London">London</option>
                                        <option value="Manchester">Manchester</option>
                                        <option value="Liverpool">Liverpool</option>
                                    </optgroup>
                                    <optgroup label="FR">
                                        <option value="Paris">Paris</option>
                                        <option value="Lyon">Lyon</option>
                                        <option value="Marseille">Marseille</option>
                                    </optgroup>
                                    <optgroup disabled="" label="DE">
                                        <option value="Hamburg">Hamburg</option>
                                        <option value="Munich">Munich</option>
                                        <option value="Berlin">Berlin</option>
                                    </optgroup>
                                    <optgroup label="US">
                                        <option selected="" value="New York">New York</option>
                                        <option disabled="" value="Washington">
                                            Washington
                                        </option>
                                        <option value="Michigan">Michigan</option>
                                    </optgroup>
                                    <optgroup label="SP">
                                        <option value="Madrid">Madrid</option>
                                        <option value="Barcelona">Barcelona</option>
                                        <option value="Malaga">Malaga</option>
                                    </optgroup>
                                    <optgroup label="CA">
                                        <option value="Montreal">Montreal</option>
                                        <option value="Toronto">Toronto</option>
                                        <option value="Vancouver">Vancouver</option>
                                    </optgroup>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <form>
                            <label class="form-label" for="choices-country">Country</label>
                            <select class="form-control" data-choices="" data-choices-groups=""
                                data-placeholder="Select Country" id="choices-country" name="choices-country">
                                <option value="">Choose a country</option>
                                <optgroup label="">
                                    <option value="">United Kingdom</option>
                                    <option value="Fran">France</option>
                                    <option value="Netherlands">Netherlands</option>
                                    <option selected="" value="U.S.A">U.S.A</option>
                                    <option value="Denmark">Denmark</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Australia">Australia</option>
                                    <option value="India">India</option>
                                    <option value="Germany">Germany</option>
                                    <option value="Spain">Spain</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                </optgroup>
                            </select>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <div class="">
                            <label class="form-label" for="from-time">Restaurant Opening Time</label>
                            <input class="form-control" id="preloading-timepicker" placeholder="Pick a time" type="text" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="">
                            <label class="form-label" for="to-time">Restaurant Close Time</label>
                            <input class="form-control" id="preloading-timepicker2" placeholder="Pick a time" type="text" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">General Settings</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="meta-name">Meta Title</label>
                                <input class="form-control" id="meta-name" placeholder="Title" type="text" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="meta-tag">Meta Tag Keyword</label>
                                <input class="form-control" id="meta-tag" placeholder="Enter word" type="text" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="themes">Restaurant Themes</label>
                                <select class="form-control" data-choices="" data-choices-groups=""
                                    data-placeholder="Select Themes" id="themes">
                                    <option value="">Default</option>
                                    <option value="Dark">Dark</option>
                                    <option selected="" value="Minimalist">Minimalist</option>
                                    <option value="High Contrast">High Contrast</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-12">
                        <div class="">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control bg-light-subtle" id="description" placeholder="Type description" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Social Settings</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="facebook-url">Facebook URL</label>
                                <input class="form-control" id="facebook-url" placeholder="Enter URL" type="url" value="facebook.url" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="instagram-url">Instagram URL</label>
                                <input class="form-control" id="instagram-url" placeholder="Enter URL" type="url" value="instagram.url" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <form>
                            <div class="mb-3">
                                <label class="form-label" for="twitter-url">Twitter URL</label>
                                <input class="form-control" id="twitter-url" placeholder="Enter URL" type="url" value="twitter.url" />
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form>
                            <div class="mb-2">
                                <label class="form-label" for="website-url">Website URL</label>
                                <input class="form-control" id="website-url" placeholder="Enter URL" type="url" value="website.url" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Customer Settings</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group mb-3">
                            <p class="fw-medium mb-2">Customers Online</p>
                            <div class="form-check form-switch">
                                <input checked="" class="form-check-input" id="customersOnline" type="checkbox" />
                                <label class="form-check-label" for="customersOnline">Yes</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group mb-3">
                            <p class="fw-medium mb-2">Customers Activity</p>
                            <div class="form-check form-switch">
                                <input checked="" class="form-check-input" id="customersActivity" type="checkbox" />
                                <label class="form-check-label" for="customersActivity">Yes</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group mb-3">
                            <p class="fw-medium mb-2">Customer Searches</p>
                            <div class="form-check form-switch">
                                <input checked="" class="form-check-input" id="customerSearches" type="checkbox" />
                                <label class="form-check-label" for="customerSearches">Yes</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <p class="fw-medium mb-2">Allow Guest Checkout</p>
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="guestCheckout" type="checkbox" />
                                <label class="form-check-label" for="guestCheckout">Yes</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <p class="fw-medium mb-2">Login Display Price</p>
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="loginDisplayPrice" type="checkbox" />
                                <label class="form-check-label" for="loginDisplayPrice">Yes</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Categories Settings</h4>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <p class="fw-medium mb-2">Category Product Count</p>
                    <div class="form-check form-switch">
                        <input checked="" class="form-check-input" id="categoryProductCount" type="checkbox" />
                        <label class="form-check-label" for="categoryProductCount">Yes</label>
                    </div>
                </div>
                <div class="form-group">
                    <form>
                        <div class="">
                            <label class="form-label" for="items-par-page">Default Items Per Page</label>
                            <input class="form-control" id="items-par-page" placeholder="000" type="number" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Reviews Settings</h4>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <p class="fw-medium mb-2">Allow Reviews</p>
                    <div class="form-check form-switch">
                        <input checked="" class="form-check-input" id="allowReviews" type="checkbox" />
                        <label class="form-check-label" for="allowReviews">Yes</label>
                    </div>
                </div>
                <div class="form-group">
                    <p class="fw-medium mb-2">Allow Guest Reviews</p>
                    <div class="form-check form-switch">
                        <input class="form-check-input" id="gaustReviews" type="checkbox" />
                        <label class="form-check-label" for="gaustReviews">Yes</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/pages/setting.js'])
@endsection