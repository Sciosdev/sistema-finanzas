@extends('layouts.vertical', ['title' => 'List Group'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Basic
                </h5>
                <div>
                    <ul class="list-group">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                        <li class="list-group-item">A fourth item</li>
                        <li class="list-group-item">And a fifth one</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Active items
                </h5>
                <div>
                    <ul class="list-group">
                        <li aria-current="true" class="list-group-item active">An active item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                        <li class="list-group-item">A fourth item</li>
                        <li class="list-group-item">And a fifth one</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4 mb-1 anchor" id="disabled">
                    Disabled items<a class="anchor-link" href="#disabled">#</a>
                </h5>
                <div>
                    <ul class="list-group">
                        <li aria-disabled="true" class="list-group-item disabled">A disabled item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                        <li class="list-group-item">A fourth item</li>
                        <li class="list-group-item">And a fifth one</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Links and buttons
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="list-group">
                            <a aria-current="true" class="list-group-item list-group-item-action active" href="#">
                                The current link item
                            </a>
                            <a class="list-group-item list-group-item-action" href="#">A second link item</a>
                            <a class="list-group-item list-group-item-action" href="#">A third link item</a>
                            <a class="list-group-item list-group-item-action" href="#">A fourth link item</a>
                            <a class="list-group-item list-group-item-action disabled">A disabled link item</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="list-group">
                            <button aria-current="true" class="list-group-item list-group-item-action active"
                                type="button">
                                The current button
                            </button>
                            <button class="list-group-item list-group-item-action" type="button">A second button
                                item</button>
                            <button class="list-group-item list-group-item-action" type="button">A third button
                                item</button>
                            <button class="list-group-item list-group-item-action" type="button">A fourth button
                                item</button>
                            <button class="list-group-item list-group-item-action" disabled="" type="button">A disabled
                                button item</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Flush
                </h5>
                <div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                        <li class="list-group-item">A fourth item</li>
                        <li class="list-group-item">And a fifth one</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Numbered
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">A list item</li>
                            <li class="list-group-item">A list item</li>
                            <li class="list-group-item">A list item</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Subheading</div>
                                    Content for list item
                                </div>
                                <span class="badge bg-primary rounded-pill">14</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Subheading</div>
                                    Content for list item
                                </div>
                                <span class="badge bg-primary rounded-pill">14</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Subheading</div>
                                    Content for list item
                                </div>
                                <span class="badge bg-primary rounded-pill">14</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Horizontal
                </h5>
                <div class="mb-3 d-flex flex-column gap-2">
                    <ul class="list-group list-group-horizontal">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                    <ul class="list-group list-group-horizontal-sm">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                    <ul class="list-group list-group-horizontal-md">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                    <ul class="list-group list-group-horizontal-lg">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                    <ul class="list-group list-group-horizontal-xl">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                    <ul class="list-group list-group-horizontal-xxl">
                        <li class="list-group-item">An item</li>
                        <li class="list-group-item">A second item</li>
                        <li class="list-group-item">A third item</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Contextual classes
                </h5>
                <div>
                    <ul class="list-group">
                        <li class="list-group-item">A simple default list group item</li>
                        <li class="list-group-item list-group-item-primary">A simple primary list group item</li>
                        <li class="list-group-item list-group-item-secondary">A simple secondary list group item</li>
                        <li class="list-group-item list-group-item-success">A simple success list group item</li>
                        <li class="list-group-item list-group-item-danger">A simple danger list group item</li>
                        <li class="list-group-item list-group-item-warning">A simple warning list group item</li>
                        <li class="list-group-item list-group-item-info">A simple info list group item</li>
                        <li class="list-group-item list-group-item-light">A simple light list group item</li>
                        <li class="list-group-item list-group-item-dark">A simple dark list group item</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Custom content
                </h5>
                <div>
                    <div class="list-group">
                        <a aria-current="true" class="list-group-item list-group-item-action active" href="#">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 text-reset">List group item heading</h5>
                                <small>3 days ago</small>
                            </div>
                            <p class="mb-1">Some placeholder content in a paragraph.</p>
                            <small>And some small print.</small>
                        </a>
                        <a class="list-group-item list-group-item-action" href="#">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">List group item heading</h5>
                                <small class="text-muted">3 days ago</small>
                            </div>
                            <p class="mb-1">Some placeholder content in a paragraph.</p>
                            <small class="text-muted">And some muted small print.</small>
                        </a>
                        <a class="list-group-item list-group-item-action" href="#">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">List group item heading</h5>
                                <small class="text-muted">3 days ago</small>
                            </div>
                            <p class="mb-1">Some placeholder content in a paragraph.</p>
                            <small class="text-muted">And some muted small print.</small>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Checkboxes and radios
                </h5>
                <div class="w-50">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <input class="form-check-input me-1" id="firstCheckbox" type="checkbox" value="" />
                            <label class="form-check-label" for="firstCheckbox">First checkbox</label>
                        </li>
                        <li class="list-group-item">
                            <input class="form-check-input me-1" id="secondCheckbox" type="checkbox" value="" />
                            <label class="form-check-label" for="secondCheckbox">Second checkbox</label>
                        </li>
                        <li class="list-group-item">
                            <input class="form-check-input me-1" id="thirdCheckbox" type="checkbox" value="" />
                            <label class="form-check-label" for="thirdCheckbox">Third checkbox</label>
                        </li>
                    </ul>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection