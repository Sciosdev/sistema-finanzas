@extends('layouts.vertical', ['title' => 'Offcanvas'])

@section('content')

<div class="card">
    <div class="card-body">
        <!-- start offcanvas -->
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Default Buttons</h5>
                <div class="button-list">
                    <a aria-controls="offcanvasExample" class="btn btn-primary" data-bs-toggle="offcanvas"
                        href="#offcanvasExample" role="button">
                        Link with href
                    </a>
                    <button aria-controls="offcanvasExample" class="btn btn-secondary"
                        data-bs-target="#offcanvasExample" data-bs-toggle="offcanvas" type="button">
                        Button with data-bs-target
                    </button>
                </div>
                <!-- default offcanvas -->
                <div aria-labelledby="offcanvasExampleLabel" class="offcanvas offcanvas-start" id="offcanvasExample"
                    tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="offcanvasExampleLabel">Offcanvas</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="defaultDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="defaultDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Static Backdrop</h5>
                <div class="button-list">
                    <button aria-controls="offcanvasScrolling" class="btn btn-primary"
                        data-bs-target="#offcanvasScrolling" data-bs-toggle="offcanvas" type="button">Enable Body
                        Scrolling</button>
                    <button aria-controls="offcanvasWithBackdrop" class="btn btn-secondary"
                        data-bs-target="#offcanvasWithBackdrop" data-bs-toggle="offcanvas" type="button">Enable Backdrop
                        (Default)</button>
                    <button aria-controls="offcanvasWithBothOptions" class="btn btn-success"
                        data-bs-target="#offcanvasWithBothOptions" data-bs-toggle="offcanvas" type="button">Enable Both
                        Scrolling &amp; Backdrop</button>
                </div>
                <!-- scrolling offcanvas -->
                <div aria-labelledby="offcanvasScrollingLabel" class="offcanvas offcanvas-start"
                    data-bs-backdrop="false" data-bs-scroll="true" id="offcanvasScrolling" tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="offcanvasScrollingLabel">Colored with scrolling</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="scrollingDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="scrollingDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- backdrop offcanvas -->
                <div aria-labelledby="offcanvasWithBackdropLabel" class="offcanvas offcanvas-start"
                    id="offcanvasWithBackdrop" tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="offcanvasWithBackdropLabel">Offcanvas with backdrop</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="backdropDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="backdropDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- both scrolling & backdrop offcanvas -->
                <div aria-labelledby="offcanvasWithBothOptionsLabel" class="offcanvas offcanvas-start"
                    data-bs-scroll="true" id="offcanvasWithBothOptions" tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="offcanvasWithBothOptionsLabel">Backdroped with scrolling
                        </h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="scrollingBackdropDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="scrollingBackdropDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Offcanvas Position</h5>
                <div class="button-list">
                    <button aria-controls="leftOffcanvas" class="btn btn-primary" data-bs-target="#leftOffcanvas"
                        data-bs-toggle="offcanvas" type="button">Left Offcanvas</button>
                    <button aria-controls="rightOffcanvas" class="btn btn-secondary" data-bs-target="#rightOffcanvas"
                        data-bs-toggle="offcanvas" type="button">Right Offcanvas</button>
                    <button aria-controls="topOffcanvas" class="btn btn-success" data-bs-target="#topOffcanvas"
                        data-bs-toggle="offcanvas" type="button">Top Offcanvas</button>
                    <button aria-controls="bottomOffcanvas" class="btn btn-info" data-bs-target="#bottomOffcanvas"
                        data-bs-toggle="offcanvas" type="button">Bottom Offcanvas</button>
                </div>
                <!-- left offcanvas -->
                <div aria-labelledby="leftOffcanvasLabel" class="offcanvas offcanvas-start" id="leftOffcanvas"
                    tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="leftOffcanvasLabel">Offcanvas</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="leftDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="leftDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- right offcanvas -->
                <div aria-labelledby="rightOffcanvasLabel" class="offcanvas offcanvas-end" id="rightOffcanvas"
                    tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="rightOffcanvasLabel">Offcanvas</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="rightDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="rightDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- top offcanvas -->
                <div aria-labelledby="topOffcanvasLabel" class="offcanvas offcanvas-top" id="topOffcanvas"
                    tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="topOffcanvasLabel">Offcanvas</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="topDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="topDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- bottom offcanvas -->
                <div aria-labelledby="bottomOffcanvasLabel" class="offcanvas offcanvas-bottom" id="bottomOffcanvas"
                    tabindex="-1">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title mt-0" id="bottomOffcanvasLabel">Offcanvas</h5>
                        <button aria-label="Close" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                            type="button"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>
                            Some text as placeholder. In real life you can have the elements you have chosen. Like,
                            text, images, lists, etc.
                        </p>
                        <div class="dropdown mt-3">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                                id="bottomDropdownMenuButton" type="button">
                                Dropdown button
                            </button>
                            <ul aria-labelledby="bottomDropdownMenuButton" class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection