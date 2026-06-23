@extends('layouts.vertical', ['title' => 'Collapse'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Default Example
                </h5>
                <ul>
                    <li><code>.collapse</code> hides content</li>
                    <li><code>.collapsing</code> is applied during transitions</li>
                    <li><code>.collapse.show</code> shows content</li>
                </ul>
                <p class="text-muted">Generally, we recommend using a button with the <code>data-bs-target</code>
                    attribute. While not recommended from a semantic point of view, you can also use a link with the
                    <code>href</code> attribute (and a <code>role="button"</code>). In both cases, the
                    <code>data-bs-toggle="collapse"</code> is required.</p>
                <div class="hstack gap-2">
                    <a aria-controls="collapseExample" aria-expanded="false" class="btn btn-primary mb-2"
                        data-bs-toggle="collapse" href="#collapseExample" role="button">
                        Link with href
                    </a>
                    <button aria-controls="collapseExample" aria-expanded="false" class="btn btn-primary mb-2"
                        data-bs-target="#collapseExample" data-bs-toggle="collapse" type="button">
                        Button with data-bs-target
                    </button>
                </div>
                <div class="collapse" id="collapseExample">
                    <div class="card mb-0">
                        <div class="card-body">
                            Some placeholder content for the collapse component. This panel is hidden by default but
                            revealed when the
                            user activates the relevant trigger.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Multiple Targets
                </h5>
                <div class="hstack gap-2 mb-3">
                    <a aria-controls="multiCollapseExample1" aria-expanded="false" class="btn btn-primary"
                        data-bs-toggle="collapse" href="#multiCollapseExample1" role="button">Toggle first element</a>
                    <button aria-controls="multiCollapseExample2" aria-expanded="false" class="btn btn-primary"
                        data-bs-target="#multiCollapseExample2" data-bs-toggle="collapse" type="button">Toggle second
                        element</button>
                    <button aria-controls="multiCollapseExample1 multiCollapseExample2" aria-expanded="false"
                        class="btn btn-primary" data-bs-target=".multi-collapse" data-bs-toggle="collapse"
                        type="button">Toggle both elements</button>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="collapse multi-collapse" id="multiCollapseExample1">
                            <div class="card card-body">
                                Some placeholder content for the first collapse component of this multi-collapse
                                example. This panel
                                is hidden by default but revealed when the user activates the relevant trigger.
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="collapse multi-collapse" id="multiCollapseExample2">
                            <div class="card card-body">
                                Some placeholder content for the second collapse component of this multi-collapse
                                example. This
                                panel is hidden by default but revealed when the user activates the relevant trigger.
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Horizontal
                </h5>
                <button aria-controls="collapseWidthExample" aria-expanded="false" class="btn btn-primary mb-2"
                    data-bs-target="#collapseWidthExample" data-bs-toggle="collapse" type="button">
                    Toggle width collapse
                </button>
                <div style="min-height: 135px;">
                    <div class="collapse collapse-horizontal" id="collapseWidthExample">
                        <div class="card card-body mb-0" style="width: 300px;">
                            This is some placeholder content for a horizontal collapse. It's hidden by default and shown
                            when triggered.
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection