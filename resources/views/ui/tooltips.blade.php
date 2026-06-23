@extends('layouts.vertical', ['title' => 'Tooltips'])

@section('content')
<div class="card">
    <div class="card-body">
        <!-- start tooltips-->
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Tooltip Direction</h5>
                <div class="d-flex flex-wrap gap-3">
                    <button class="btn btn-primary" data-bs-placement="top" data-bs-title="Tooltip on top"
                        data-bs-toggle="tooltip" type="button">
                        Tooltip on top
                    </button>
                    <button class="btn btn-primary" data-bs-placement="right" data-bs-title="Tooltip on right"
                        data-bs-toggle="tooltip" type="button">
                        Tooltip on right
                    </button>
                    <button class="btn btn-primary" data-bs-placement="bottom" data-bs-title="Tooltip on bottom"
                        data-bs-toggle="tooltip" type="button">
                        Tooltip on bottom
                    </button>
                    <button class="btn btn-primary" data-bs-placement="left" data-bs-title="Tooltip on left"
                        data-bs-toggle="tooltip" type="button">
                        Tooltip on left
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Color Tooltip</h5>
                <div class="d-flex flex-wrap gap-3">
                    <button class="btn btn-primary" data-bs-custom-class="primary-tooltip" data-bs-placement="top"
                        data-bs-title="This top tooltip is themed via CSS variables." data-bs-toggle="tooltip"
                        type="button">
                        Primary tooltip
                    </button>
                    <button class="btn btn-danger" data-bs-custom-class="danger-tooltip" data-bs-placement="top"
                        data-bs-title="This top tooltip is themed via CSS variables." data-bs-toggle="tooltip"
                        type="button">
                        Danger tooltip
                    </button>
                    <button class="btn btn-info" data-bs-custom-class="info-tooltip" data-bs-placement="top"
                        data-bs-title="This top tooltip is themed via CSS variables." data-bs-toggle="tooltip"
                        type="button">
                        Info tooltip
                    </button>
                    <button class="btn btn-success" data-bs-custom-class="success-tooltip" data-bs-placement="top"
                        data-bs-title="This top tooltip is themed via CSS variables." data-bs-toggle="tooltip"
                        type="button">
                        Success tooltip
                    </button>
                </div>
            </div> <!-- end col -->
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Tooltips on links</h5>
                <p class="muted">Placeholder text to demonstrate some <a class="link-danger"
                        data-bs-title="Default tooltip" data-bs-toggle="tooltip" href="#">inline links</a> with
                    tooltips. This is now just filler, no killer. Content placed here just to mimic the presence of <a
                        class="link-danger" data-bs-title="Another tooltip" data-bs-toggle="tooltip" href="#">real
                        text</a>. And all that just to give you an idea of how tooltips would look when used in
                    real-world situations. So hopefully you've now seen how <a class="link-danger"
                        data-bs-title="Another one here too" data-bs-toggle="tooltip" href="#">these tooltips on
                        links</a> can work in practice, once you use them on <a class="link-danger"
                        data-bs-title="The last tip!" data-bs-toggle="tooltip" href="#">your own</a> site or project.
                </p>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection