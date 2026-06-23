@extends('layouts.vertical', ['title' => 'Dropdown'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Single Button Dropdowns
                </h5>
                <div class="d-flex flex-wrap gap-3">
                    <!-- Button Dropdown -->
                    <div class="dropdown">
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle"
                            data-bs-toggle="dropdown" id="dropdownMenuButton1" type="button">
                            Dropdown button
                        </button>
                        <div aria-labelledby="dropdownMenuButton1" class="dropdown-menu">
                            <a class="dropdown-item" href="#">Action</a>
                            <a class="dropdown-item" href="#">Another action</a>
                            <a class="dropdown-item" href="#">Something else here</a>
                        </div>
                    </div>
                    <!-- Link Dropdown -->
                    <div class="dropdown">
                        <a aria-expanded="false" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                            href="#">
                            Dropdown link
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">Action</a>
                            <a class="dropdown-item" href="#">Another action</a>
                            <a class="dropdown-item" href="#">Something else here</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Single Button Variant Dropdowns
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-primary dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Primary
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-secondary dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Secondary
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-success dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Success
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-info dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Info
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Split Button Dropdowns
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="btn-group">
                        <button class="btn btn-primary" type="button">Primary</button>
                        <button aria-expanded="false" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" type="button"></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" type="button">Secondary</button>
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" type="button"></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-success" type="button">Success</button>
                        <button aria-expanded="false" class="btn btn-success dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" type="button"></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-info" type="button">Info</button>
                        <button aria-expanded="false" class="btn btn-info dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" type="button"></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Dark Dropdowns
                </h5>
                <div class="dropdown">
                    <button aria-expanded="false" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                        type="button">Dark Dropdown</button>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Dropdown Direction
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="btn-group">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-primary dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Drop Down
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="btn-group dropup">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-secondary dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Drop Up
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="btn-group dropend">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-success dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Drop Right
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="btn-group dropstart">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-info dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Drop Left
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Dropdown Menu Items
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <ul class="dropdown-menu show block position-static">
                        <li><a class="dropdown-item" href="#">Regular link</a></li>
                        <li><a aria-current="true" class="dropdown-item active" href="#">Active link</a></li>
                        <li><a class="dropdown-item" href="#">Another link</a></li>
                    </ul>
                    <ul class="dropdown-menu show block position-static">
                        <li><a class="dropdown-item" href="#">Regular link</a></li>
                        <li><a aria-current="true" class="dropdown-item disabled" href="#">Active link</a></li>
                        <li><a class="dropdown-item" href="#">Another link</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Dropdown options
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle" data-bs-offset="10,20"
                            data-bs-toggle="dropdown" type="button">
                            Offset
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" type="button">Reference</button>
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                            data-bs-reference="parent" data-bs-toggle="dropdown" type="button">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Auto close behavior
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="btn-group">
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle"
                            data-bs-auto-close="true" data-bs-toggle="dropdown" type="button">
                            Default dropdown
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle"
                            data-bs-auto-close="inside" data-bs-toggle="dropdown" type="button">
                            Clickable outside
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle"
                            data-bs-auto-close="outside" data-bs-toggle="dropdown" type="button">
                            Clickable inside
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button aria-expanded="false" class="btn btn-secondary dropdown-toggle"
                            data-bs-auto-close="false" data-bs-toggle="dropdown" type="button">
                            Manual close
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                            <li><a class="dropdown-item" href="#">Menu item</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Menu Content
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-primary dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Dropdown Header
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-header">Dropdown header</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-info dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Dropdown Divider
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-secondary dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Dropdown Text
                        </button>
                        <div class="dropdown-menu dropdown-lg p-3">
                            <p>Some example text that's free-flowing within the dropdown menu.</p>
                            <p class="mb-0">And this is more example text.</p>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button aria-expanded="false" aria-haspopup="true" class="btn btn-success dropdown-toggle"
                            data-bs-toggle="dropdown" type="button">
                            Dropdown Menu Forms
                        </button>
                        <form class="dropdown-menu dropdown-lg p-3">
                            <div class="mb-3">
                                <label class="form-label" for="exampleDropdownFormEmail">Email address</label>
                                <input class="form-control" id="exampleDropdownFormEmail"
                                    placeholder="email@example.com" type="email" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="exampleDropdownFormPassword">Password</label>
                                <input class="form-control" id="exampleDropdownFormPassword" placeholder="Password"
                                    type="password" />
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" id="dropdownCheck" type="checkbox" />
                                    <label class="form-check-label" for="dropdownCheck">Remember me</label>
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">Sign in</button>
                        </form>
                    </div>
                </div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection