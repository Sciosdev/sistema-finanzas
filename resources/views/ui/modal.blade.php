@extends('layouts.vertical', ['title' => 'Modal'])

@section('content')

<div class="card">
    <div class="card-body">
        <!-- start modal -->
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Default Modals
                </h5>
                <!-- Button trigger modal -->
                <button class="btn btn-primary" data-bs-target="#exampleModal" data-bs-toggle="modal" type="button">
                    Launch demo modal
                </button>
                <!-- Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalLabel" class="modal fade" id="exampleModal"
                    tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                <p>Woo-hoo, you're reading this text in a modal!</p>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                                <button class="btn btn-primary" type="button">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Static Backdrop
                </h5>
                <!-- Button trigger modal -->
                <button class="btn btn-primary" data-bs-target="#staticBackdrop" data-bs-toggle="modal" type="button">
                    Launch static backdrop modal
                </button>
                <!-- Modal -->
                <div aria-hidden="true" aria-labelledby="staticBackdropLabel" class="modal fade"
                    data-bs-backdrop="static" data-bs-keyboard="false" id="staticBackdrop" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel">Modal title</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                <p>I will not close if you click outside of me. Don't even try to press escape key.</p>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                                <button class="btn btn-primary" type="button">Understood</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Scrolling Long Content
                </h5>
                <!-- Button trigger modal -->
                <button class="btn btn-primary" data-bs-target="#exampleModalLong" data-bs-toggle="modal" type="button">
                    Launch demo modal
                </button>
                <!-- Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="exampleModalLong"
                    tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body" style="min-height: 1500px">
                                <p>This is some placeholder content to show the scrolling behavior for modals. Instead
                                    of repeating
                                    the text the modal, we use an inline style set a minimum height, thereby extending
                                    the length of
                                    the overall modal and demonstrating the overflow scrolling. When content becomes
                                    longer than the
                                    height of the viewport, scrolling will move the modal as needed.</p>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                                <button class="btn btn-primary" type="button">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Button trigger modal -->
                <button class="btn btn-primary" data-bs-target="#exampleModalScrollable" data-bs-toggle="modal"
                    type="button">
                    Launch demo modal
                </button>
                <!-- Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalScrollableTitle" class="modal fade"
                    id="exampleModalScrollable" tabindex="-1">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalScrollableTitle">Modal title</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                <p>This is some placeholder content to show the scrolling behavior for modals. We use
                                    repeated line
                                    breaks to demonstrate how content can exceed minimum inner height, thereby showing
                                    inner
                                    scrolling. When content becomes longer than the predefined max-height of modal,
                                    content will be
                                    cropped and scrollable within the modal.</p>
                                <br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
                                <p>This content should appear at the bottom after you scroll.</p>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                                <button class="btn btn-primary" type="button">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Modal Position
                </h5>
                <div class="d-flex flex-wrap gap-2">
                    <!-- Button trigger modal -->
                    <button class="btn btn-primary" data-bs-target="#exampleModalCenter" data-bs-toggle="modal"
                        type="button">
                        Vertically centered modal
                    </button>
                    <!-- Modal -->
                    <div aria-hidden="true" aria-labelledby="exampleModalCenterTitle" class="modal fade"
                        id="exampleModalCenter" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalCenterTitle">Modal title</h5>
                                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                        type="button"></button>
                                </div>
                                <div class="modal-body">
                                    <p>This is a vertically centered modal.</p>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal"
                                        type="button">Close</button>
                                    <button class="btn btn-primary" type="button">Save changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Button trigger modal -->
                    <button class="btn btn-primary" data-bs-target="#exampleModalCenteredScrollable"
                        data-bs-toggle="modal" type="button">
                        Vertically centered scrollable modal
                    </button>
                    <!-- Modal -->
                    <div aria-hidden="true" aria-labelledby="exampleModalCenteredScrollableTitle" class="modal fade"
                        id="exampleModalCenteredScrollable" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalCenteredScrollableTitle">Modal title</h5>
                                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                        type="button"></button>
                                </div>
                                <div class="modal-body">
                                    <p>This is some placeholder content to show a vertically centered modal. We've added
                                        some extra
                                        copy
                                        here to show how vertically centering the modal works when combined with
                                        scrollable modals.
                                        We
                                        also use some repeated line breaks to quickly extend the height of the content,
                                        thereby
                                        triggering the scrolling. When content becomes longer than the predefined
                                        max-height of
                                        modal,
                                        content will be cropped and scrollable within the modal.</p>
                                    <br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
                                    <p>Just like that.</p>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal"
                                        type="button">Close</button>
                                    <button class="btn btn-primary" type="button">Save changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Toggle Between Modals
                </h5>
                <!-- Button trigger modal -->
                <a class="btn btn-primary" data-bs-toggle="modal" href="#exampleModalToggle" role="button">Open first
                    modal</a>
                <!-- First Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalToggleLabel" class="modal fade"
                    id="exampleModalToggle" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalToggleLabel">Modal 1</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                Show a second modal and hide this one with the button below.
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-primary" data-bs-target="#exampleModalToggle2"
                                    data-bs-toggle="modal">Open
                                    second modal</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Second Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalToggleLabel2" class="modal fade"
                    id="exampleModalToggle2" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalToggleLabel2">Modal 2</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                Hide this modal and show the first with the button below.
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                                <button class="btn btn-primary" data-bs-target="#exampleModalToggle"
                                    data-bs-toggle="modal">Back to
                                    first</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Optional Sizes
                </h5>
                <div class="hstack gap-2">
                    <!-- Button trigger modal -->
                    <button class="btn btn-primary" data-bs-target="#exampleModalXl" data-bs-toggle="modal"
                        type="button">Extra large modal</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalLg" data-bs-toggle="modal"
                        type="button">Large modal</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalSm" data-bs-toggle="modal"
                        type="button">Small modal</button>
                </div>
                <!-- Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalXlLabel" class="modal fade" id="exampleModalXl"
                    tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalXlLabel">Extra large modal</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalLgLabel" class="modal fade" id="exampleModalLg"
                    tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalLgLabel">Large modal</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalSmLabel" class="modal fade" id="exampleModalSm"
                    tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalSmLabel">Small modal</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Fullscreen Modal
                </h5>
                <div class="hstack flex-wrap gap-2">
                    <!-- Button trigger modal -->
                    <button class="btn btn-primary" data-bs-target="#exampleModalFullscreen" data-bs-toggle="modal"
                        type="button">Full screen</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalFullscreenSm" data-bs-toggle="modal"
                        type="button">Full screen below sm</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalFullscreenMd" data-bs-toggle="modal"
                        type="button">Full screen below md</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalFullscreenLg" data-bs-toggle="modal"
                        type="button">Full screen below lg</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalFullscreenXl" data-bs-toggle="modal"
                        type="button">Full screen below xl</button>
                    <button class="btn btn-primary" data-bs-target="#exampleModalFullscreenXxl" data-bs-toggle="modal"
                        type="button">Full screen below xxl</button>
                </div>
                <!-- Modal -->
                <div aria-hidden="true" aria-labelledby="exampleModalFullscreenLabel" class="modal fade"
                    id="exampleModalFullscreen" tabindex="-1">
                    <div class="modal-dialog modal-fullscreen">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalFullscreenLabel">Full screen modal</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalFullscreenSmLabel" class="modal fade"
                    id="exampleModalFullscreenSm" tabindex="-1">
                    <div class="modal-dialog modal-fullscreen-sm-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalFullscreenSmLabel">Full screen below sm</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalFullscreenMdLabel" class="modal fade"
                    id="exampleModalFullscreenMd" tabindex="-1">
                    <div class="modal-dialog modal-fullscreen-md-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalFullscreenMdLabel">Full screen below md</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalFullscreenLgLabel" class="modal fade"
                    id="exampleModalFullscreenLg" tabindex="-1">
                    <div class="modal-dialog modal-fullscreen-lg-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalFullscreenLgLabel">Full screen below lg</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalFullscreenXlLabel" class="modal fade"
                    id="exampleModalFullscreenXl" tabindex="-1">
                    <div class="modal-dialog modal-fullscreen-xl-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalFullscreenXlLabel">Full screen below xl</h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div aria-hidden="true" aria-labelledby="exampleModalFullscreenXxlLabel" class="modal fade"
                    id="exampleModalFullscreenXxl" tabindex="-1">
                    <div class="modal-dialog modal-fullscreen-xxl-down">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title h4" id="exampleModalFullscreenXxlLabel">Full screen below xxl
                                </h5>
                                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal"
                                    type="button"></button>
                            </div>
                            <div class="modal-body">
                                ...
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Modal Based Alerts
                </h5>
                <div class="hstack flex-wrap gap-2">
                    <button class="btn btn-primary" data-bs-target="#primaryAlertModal" data-bs-toggle="modal"
                        type="button">Primary Alert</button>
                    <button class="btn btn-secondary" data-bs-target="#secondaryAlertModal" data-bs-toggle="modal"
                        type="button">Secondary Alert</button>
                    <button class="btn btn-success" data-bs-target="#successAlertModal" data-bs-toggle="modal"
                        type="button">Success Alert</button>
                    <button class="btn btn-info" data-bs-target="#infoALertModal" data-bs-toggle="modal"
                        type="button">Info Alert</button>
                </div>
                <!-- Primary Alert Modal -->
                <div aria-hidden="true" class="modal fade" id="primaryAlertModal" role="dialog" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content modal-filled bg-primary">
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="display-6 mt-0 text-white" data-lucide="check-check"></i>
                                    <h4 class="mt-3 text-white">Well Done!</h4>
                                    <p class="mt-3">Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
                                        dapibus ac facilisis in, egestas eget quam.</p>
                                    <button class="btn btn-light mt-3" data-bs-dismiss="modal"
                                        type="button">Continue</button>
                                </div>
                            </div> <!-- end modal body -->
                        </div><!-- end modal content -->
                    </div><!-- end modal dialog -->
                </div> <!-- end modal -->
                <!-- Secondary Alert Modal -->
                <div aria-hidden="true" class="modal fade" id="secondaryAlertModal" role="dialog" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content modal-filled bg-secondary">
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="display-6 mt-0 text-white" data-lucide="check-check"></i>
                                    <h4 class="mt-3 text-white">Well Done!</h4>
                                    <p class="mt-3">Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
                                        dapibus ac facilisis in, egestas eget quam.</p>
                                    <button class="btn btn-light mt-3" data-bs-dismiss="modal"
                                        type="button">Continue</button>
                                </div>
                            </div> <!-- end modal body -->
                        </div><!-- end modal content -->
                    </div><!-- end modal dialog -->
                </div> <!-- end modal -->
                <!-- Success Alert Modal -->
                <div aria-hidden="true" class="modal fade" id="successAlertModal" role="dialog" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content modal-filled bg-success">
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="display-6 mt-0 text-white" data-lucide="check-check"></i>
                                    <h4 class="mt-3 text-white">Well Done!</h4>
                                    <p class="mt-3">Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
                                        dapibus ac facilisis in, egestas eget quam.</p>
                                    <button class="btn btn-light mt-3" data-bs-dismiss="modal"
                                        type="button">Continue</button>
                                </div>
                            </div> <!-- end modal body -->
                        </div><!-- end modal content -->
                    </div><!-- end modal dialog -->
                </div> <!-- end modal -->
                <!-- Info Alert Modal -->
                <div aria-hidden="true" class="modal fade" id="infoALertModal" role="dialog" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content modal-filled bg-info">
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="display-6 mt-0 text-white" data-lucide="check-check"></i>
                                    <h4 class="mt-3 text-white">Well Done!</h4>
                                    <p class="mt-3">Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
                                        dapibus ac facilisis in, egestas eget quam.</p>
                                    <button class="btn btn-light mt-3" data-bs-dismiss="modal"
                                        type="button">Continue</button>
                                </div>
                            </div> <!-- end modal body -->
                        </div><!-- end modal content -->
                    </div><!-- end modal dialog -->
                </div> <!-- end modal -->
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection