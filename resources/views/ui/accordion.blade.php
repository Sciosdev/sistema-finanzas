@extends('layouts.vertical', ['title' => 'Accordions'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Basic Example</h5>
                <!-- Basic Example -->
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button aria-controls="collapseOne" aria-expanded="true" class="accordion-button fw-medium"
                                data-bs-target="#collapseOne" data-bs-toggle="collapse" type="button">
                                Accordion Item #1
                            </button>
                        </h2>
                        <div aria-labelledby="headingOne" class="accordion-collapse collapse show"
                            data-bs-parent="#accordionExample" id="collapseOne">
                            <div class="accordion-body">
                                <strong>This is the first item's accordion body.</strong> It is shown by default, until
                                the
                                collapse
                                plugin adds the appropriate classes that we use to style each element. These classes
                                control
                                the overall
                                appearance, as well as the showing and hiding via CSS transitions. You can modify any of
                                this with
                                custom CSS or overriding our default variables. It's also worth noting that just about
                                any
                                HTML can go
                                within the <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button aria-controls="collapseTwo" aria-expanded="false"
                                class="accordion-button fw-medium collapsed" data-bs-target="#collapseTwo"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #2
                            </button>
                        </h2>
                        <div aria-labelledby="headingTwo" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample" id="collapseTwo">
                            <div class="accordion-body">
                                <strong>This is the second item's accordion body.</strong> It is hidden by default,
                                until
                                the collapse
                                plugin adds the appropriate classes that we use to style each element. These classes
                                control
                                the overall
                                appearance, as well as the showing and hiding via CSS transitions. You can modify any of
                                this with
                                custom CSS or overriding our default variables. It's also worth noting that just about
                                any
                                HTML can go
                                within the <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button aria-controls="collapseThree" aria-expanded="false"
                                class="accordion-button fw-medium collapsed" data-bs-target="#collapseThree"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #3
                            </button>
                        </h2>
                        <div aria-labelledby="headingThree" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample" id="collapseThree">
                            <div class="accordion-body">
                                <strong>This is the third item's accordion body.</strong> It is hidden by default, until
                                the
                                collapse
                                plugin adds the appropriate classes that we use to style each element. These classes
                                control
                                the overall
                                appearance, as well as the showing and hiding via CSS transitions. You can modify any of
                                this with
                                custom CSS or overriding our default variables. It's also worth noting that just about
                                any
                                HTML can go
                                within the <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4"> Flush Accordion </h5>
                <div class="accordion accordion-flush" id="accordionFlushExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingOne">
                            <button aria-controls="flush-collapseOne" aria-expanded="false"
                                class="accordion-button collapsed" data-bs-target="#flush-collapseOne"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #1
                            </button>
                        </h2>
                        <div aria-labelledby="flush-headingOne" class="accordion-collapse collapse"
                            data-bs-parent="#accordionFlushExample" id="flush-collapseOne">
                            <div class="accordion-body">Placeholder content for this accordion, which is intended to
                                demonstrate the
                                <code>.accordion-flush</code> class. This is the first item's accordion body.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingTwo">
                            <button aria-controls="flush-collapseTwo" aria-expanded="false"
                                class="accordion-button collapsed" data-bs-target="#flush-collapseTwo"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #2
                            </button>
                        </h2>
                        <div aria-labelledby="flush-headingTwo" class="accordion-collapse collapse"
                            data-bs-parent="#accordionFlushExample" id="flush-collapseTwo">
                            <div class="accordion-body">Placeholder content for this accordion, which is intended to
                                demonstrate the
                                <code>.accordion-flush</code> class. This is the second item's accordion body. Let's
                                imagine this
                                being
                                filled with some actual content.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingThree">
                            <button aria-controls="flush-collapseThree" aria-expanded="false"
                                class="accordion-button collapsed" data-bs-target="#flush-collapseThree"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #3
                            </button>
                        </h2>
                        <div aria-labelledby="flush-headingThree" class="accordion-collapse collapse"
                            data-bs-parent="#accordionFlushExample" id="flush-collapseThree">
                            <div class="accordion-body">Placeholder content for this accordion, which is intended to
                                demonstrate the
                                <code>.accordion-flush</code> class. This is the third item's accordion body. Nothing
                                more exciting
                                happening here in terms of content, but just filling up the space to make it look, at
                                least at first
                                glance, a bit more representative of how this would look in a real-world application.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Always Open Accordion</h5>
                <div class="accordion" id="accordionPanelsStayOpenExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                            <button aria-controls="panelsStayOpen-collapseOne" aria-expanded="true"
                                class="accordion-button" data-bs-target="#panelsStayOpen-collapseOne"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #1
                            </button>
                        </h2>
                        <div aria-labelledby="panelsStayOpen-headingOne" class="accordion-collapse collapse show"
                            id="panelsStayOpen-collapseOne">
                            <div class="accordion-body">
                                <strong>This is the first item's accordion body.</strong> It is shown by default, until
                                the collapse
                                plugin adds the appropriate classes that we use to style each element. These classes
                                control the overall
                                appearance, as well as the showing and hiding via CSS transitions. You can modify any of
                                this with
                                custom CSS or overriding our default variables. It's also worth noting that just about
                                any HTML can go
                                within the <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                            <button aria-controls="panelsStayOpen-collapseTwo" aria-expanded="false"
                                class="accordion-button collapsed" data-bs-target="#panelsStayOpen-collapseTwo"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #2
                            </button>
                        </h2>
                        <div aria-labelledby="panelsStayOpen-headingTwo" class="accordion-collapse collapse"
                            id="panelsStayOpen-collapseTwo">
                            <div class="accordion-body">
                                <strong>This is the second item's accordion body.</strong> It is hidden by default,
                                until the collapse
                                plugin adds the appropriate classes that we use to style each element. These classes
                                control the overall
                                appearance, as well as the showing and hiding via CSS transitions. You can modify any of
                                this with
                                custom CSS or overriding our default variables. It's also worth noting that just about
                                any HTML can go
                                within the <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-headingThree">
                            <button aria-controls="panelsStayOpen-collapseThree" aria-expanded="false"
                                class="accordion-button collapsed" data-bs-target="#panelsStayOpen-collapseThree"
                                data-bs-toggle="collapse" type="button">
                                Accordion Item #3
                            </button>
                        </h2>
                        <div aria-labelledby="panelsStayOpen-headingThree" class="accordion-collapse collapse"
                            id="panelsStayOpen-collapseThree">
                            <div class="accordion-body">
                                <strong>This is the third item's accordion body.</strong> It is hidden by default, until
                                the collapse
                                plugin adds the appropriate classes that we use to style each element. These classes
                                control the overall
                                appearance, as well as the showing and hiding via CSS transitions. You can modify any of
                                this with
                                custom CSS or overriding our default variables. It's also worth noting that just about
                                any HTML can go
                                within the <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection