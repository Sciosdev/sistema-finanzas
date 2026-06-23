@extends('layouts.vertical', ['title' => 'Carousel'])

@section('content')

<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Slides Only
                </h5>
                <div class="carousel slide" data-bs-ride="carousel" id="carouselExampleSlidesOnly">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img alt="img-2" class="d-block w-100" src="/images/small/img-2.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="img-3" class="d-block w-100" src="/images/small/img-3.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="img-4" class="d-block w-100" src="/images/small/img-4.jpg" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    With Controls
                </h5>
                <div class="carousel slide" data-bs-ride="carousel" id="carouselExampleControls">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img alt="img-4" class="d-block w-100" src="/images/small/img-4.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="img-2" class="d-block w-100" src="/images/small/img-2.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="img-3" class="d-block w-100" src="/images/small/img-3.jpg" />
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev" data-bs-target="#carouselExampleControls"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next" data-bs-target="#carouselExampleControls"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    With Indicators
                </h5>
                <div class="carousel slide" data-bs-ride="carousel" id="carouselExampleIndicators">
                    <div class="carousel-indicators">
                        <button aria-current="true" aria-label="Slide 1" class="active" data-bs-slide-to="0"
                            data-bs-target="#carouselExampleIndicators" type="button"></button>
                        <button aria-label="Slide 2" data-bs-slide-to="1" data-bs-target="#carouselExampleIndicators"
                            type="button"></button>
                        <button aria-label="Slide 3" data-bs-slide-to="2" data-bs-target="#carouselExampleIndicators"
                            type="button"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img alt="img-5" class="d-block w-100" src="/images/small/img-5.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="img-6" class="d-block w-100" src="/images/small/img-6.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="img-7" class="d-block w-100" src="/images/small/img-7.jpg" />
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev"
                        data-bs-target="#carouselExampleIndicators" type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next"
                        data-bs-target="#carouselExampleIndicators" type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    With Captions
                </h5>
                <div class="carousel slide" data-bs-ride="carousel" id="carouselExampleCaptions">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img alt="img-6" class="d-block w-100" src="/images/small/img-6.jpg" />
                            <div class="carousel-caption d-none d-md-block">
                                <h5 class="text-white">First slide label</h5>
                                <p>Some representative placeholder content for the first slide.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img alt="img-7" class="d-block w-100" src="/images/small/img-7.jpg" />
                            <div class="carousel-caption d-none d-md-block">
                                <h5 class="text-white">Second slide label</h5>
                                <p>Some representative placeholder content for the second slide.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img alt="img-5" class="d-block w-100" src="/images/small/img-5.jpg" />
                            <div class="carousel-caption d-none d-md-block">
                                <h5 class="text-white">Third slide label</h5>
                                <p>Some representative placeholder content for the third slide.</p>
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev" data-bs-target="#carouselExampleCaptions"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next" data-bs-target="#carouselExampleCaptions"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Crossfade
                </h5>
                <div class="carousel slide carousel-fade" data-bs-ride="carousel" id="carouselExampleFade">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img alt="..." class="d-block w-100" src="/images/small/img-1.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="..." class="d-block w-100" src="/images/small/img-2.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="..." class="d-block w-100" src="/images/small/img-3.jpg" />
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev" data-bs-target="#carouselExampleFade"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next" data-bs-target="#carouselExampleFade"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Individual <code> .carousel-item </code> interval
                </h5>
                <div class="carousel slide" data-bs-ride="carousel" id="carouselExampleInterval">
                    <div class="carousel-inner">
                        <div class="carousel-item active" data-bs-interval="10000">
                            <img alt="..." class="d-block w-100" src="/images/small/img-1.jpg" />
                        </div>
                        <div class="carousel-item" data-bs-interval="2000">
                            <img alt="..." class="d-block w-100" src="/images/small/img-2.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="..." class="d-block w-100" src="/images/small/img-3.jpg" />
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev" data-bs-target="#carouselExampleInterval"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next" data-bs-target="#carouselExampleInterval"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Disable touch swiping <code> .carousel-item </code> interval
                </h5>
                <div class="carousel slide" data-bs-touch="false" id="carouselExampleControlsNoTouching">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img alt="..." class="d-block w-100" src="/images/small/img-4.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="..." class="d-block w-100" src="/images/small/img-5.jpg" />
                        </div>
                        <div class="carousel-item">
                            <img alt="..." class="d-block w-100" src="/images/small/img-6.jpg" />
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev"
                        data-bs-target="#carouselExampleControlsNoTouching" type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next"
                        data-bs-target="#carouselExampleControlsNoTouching" type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">
                    Dark Variant <code> .carousel-item </code> interval
                </h5>
                <div class="carousel carousel-dark slide" data-bs-ride="carousel" id="carouselExampleDark">
                    <div class="carousel-indicators">
                        <button aria-current="true" aria-label="Slide 1" class="active" data-bs-slide-to="0"
                            data-bs-target="#carouselExampleDark" type="button"></button>
                        <button aria-label="Slide 2" data-bs-slide-to="1" data-bs-target="#carouselExampleDark"
                            type="button"></button>
                        <button aria-label="Slide 3" data-bs-slide-to="2" data-bs-target="#carouselExampleDark"
                            type="button"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active" data-bs-interval="10000">
                            <img alt="..." class="d-block w-100" src="/images/small/img-8.jpg" />
                            <div class="carousel-caption d-none d-md-block">
                                <h5>First slide label</h5>
                                <p>Some representative placeholder content for the first slide.</p>
                            </div>
                        </div>
                        <div class="carousel-item" data-bs-interval="2000">
                            <img alt="..." class="d-block w-100" src="/images/small/img-9.jpg" />
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Second slide label</h5>
                                <p>Some representative placeholder content for the second slide.</p>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img alt="..." class="d-block w-100" src="/images/small/img-10.jpg" />
                            <div class="carousel-caption d-none d-md-block">
                                <h5>Third slide label</h5>
                                <p>Some representative placeholder content for the third slide.</p>
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" data-bs-slide="prev" data-bs-target="#carouselExampleDark"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" data-bs-slide="next" data-bs-target="#carouselExampleDark"
                        type="button">
                        <span aria-hidden="true" class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection