@extends('layouts.vertical', ['title' => 'Card'])

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="card card-body">
            <h5 class="card-title mb-1">Special title treatment</h5>
            <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
            <a class="btn btn-primary" href="javascript: void(0);">Go somewhere</a>
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-sm-6">
        <div class="card card-body">
            <h5 class="card-title mb-1">Special title treatment</h5>
            <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
            <a class="btn btn-primary" href="javascript: void(0);">Go somewhere</a>
        </div> <!-- end card-->
    </div> <!-- end col-->
</div> <!-- end row -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Featured</div>
            <div class="card-body">
                <h5 class="card-title mb-1">Special title treatment</h5>
                <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                <a class="btn btn-primary" href="javascript: void(0);">Go somewhere</a>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Quote</div>
            <div class="card-body">
                <blockquote>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    <footer>Someone famous in <cite>Source Title</cite></footer>
                </blockquote>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Featured</div>
            <div class="card-body">
                <a class="btn btn-primary" href="javascript: void(0);">Go somewhere</a>
            </div>
            <div class="card-footer text-muted">2 days ago</div>
        </div> <!-- end card-->
    </div> <!-- end col-->
</div> <!-- end row -->
<!-- start cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card mb-3 mb-xl-0">
            <img alt="img-1" class="card-img-top img-fluid" src="/images/small/img-1.jpg" />
            <div class="card-body">
                <h5 class="card-title mb-2">Card title</h5>
                <p class="card-text text-muted">
                    Some quick example text to build on the card title and make
                    up the bulk of the card's content. With supporting text below as
                    a natural lead-in to additional content.
                </p>
                <a class="btn btn-primary" href="javascript:void(0);">Button</a>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->
    <div class="col-xl-3 col-md-6">
        <div class="card mb-3">
            <img alt="img-2" class="card-img-top img-fluid" src="/images/small/img-2.jpg" />
            <div class="card-body">
                <h5 class="card-title mb-2">Card title</h5>
                <p class="card-text text-muted">Some quick example text to build on the card title.</p>
            </div> <!-- end card body -->
            <ul class="list-group list-group-flush text-muted">
                <li class="list-group-item text-muted">Dapibus ac facilisis in</li>
            </ul>
            <div class="card-body">
                <a class="card-link text-primary" href="javascript:void(0);">Card link</a>
                <a class="card-link text-primary" href="javascript:void(0);">Another link</a>
            </div>
        </div> <!-- end card -->
    </div> <!-- end col -->
    <div class="col-xl-3 col-md-6">
        <div class="card mb-3 mb-xl-0">
            <img alt="img-4" class="card-img-top img-fluid" src="/images/small/img-4.jpg" />
            <div class="card-body">
                <p class="card-text text-muted">
                    Some quick example text to build on the card title and make
                    up the bulk of the card's content. With supporting text below as
                    a natural lead-in to additional content.
                </p>
                <a class="btn btn-primary" href="javascript:void(0);">Button</a>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->
    <div class="col-xl-3 col-md-6">
        <div class="card mb-3 mb-xl-0">
            <div class="card-body">
                <h5 class="card-title mb-0">Card title</h5>
            </div>
            <img alt="img-5" class="img-fluid" src="/images/small/img-5.jpg" />
            <div class="card-body">
                <p class="card-text text-muted">Some quick example text to build on the card title.</p>
                <a class="card-link text-primary" href="javascript:void(0);">Card link</a>
                <a class="card-link text-primary" href="javascript:void(0);">Another link</a>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->
</div> <!-- end row -->
<!-- Color Card Start -->
<div class="row">
    <div class="col-12">
        <h5 class="card-title mb-3">Card Colored</h5>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title text-white mb-2">Special title treatment</h5>
                <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                <a class="btn btn-light btn-sm" href="javascript: void(0);">Button</a>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <blockquote>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                </blockquote>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card text-bg-success">
            <div class="card-body">
                <blockquote>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                </blockquote>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card text-bg-info">
            <div class="card-body">
                <blockquote>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                </blockquote>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card text-bg-warning">
            <div class="card-body">
                <blockquote>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                </blockquote>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card text-bg-danger">
            <div class="card-body">
                <blockquote>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                    <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                </blockquote>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
</div> <!-- end row -->
<!-- Card Border Start -->
<div class="row">
    <div class="col-12">
        <h5 class="card-title mb-3">Card Bordered</h5>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card border-primary border">
            <div class="card-body">
                <h5 class="card-title text-primary mb-2">Special title treatment</h5>
                <p class="card-text">With supporting text below as a natural lead-in to
                    additional content.</p>
                <a class="btn btn-primary btn-sm" href="javascript: void(0);">Button</a>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card border-secondary border">
            <div class="card-body">
                <h5 class="card-title mb-2">Special title treatment</h5>
                <p class="card-text">With supporting text below as a natural lead-in to
                    additional content.</p>
                <a class="btn btn-secondary btn-sm" href="javascript: void(0);">Button</a>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-md-4">
        <div class="card border-success border">
            <div class="card-body">
                <h5 class="card-title mb-2 text-success">Special title treatment</h5>
                <p class="card-text">With supporting text below as a natural lead-in to
                    additional content.</p>
                <a class="btn btn-success btn-sm" href="javascript: void(0);">Button</a>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
</div> <!-- end row -->
<!-- Horizontal Card Start -->
<div class="row">
    <div class="col-12">
        <h5 class="card-title mb-3">Horizontal Card</h5>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="row g-0">
                <div class="col-md-4">
                    <img alt="img-1" class="img-fluid rounded-start h-100" src="/images/small/img-1.jpg" />
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">This is a wider card with supporting text below as a natural lead-in to
                            additional content. This content is a little bit longer.</p>
                        <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                    </div> <!-- end card-body-->
                </div> <!-- end col -->
            </div> <!-- end row-->
        </div> <!-- end card-->
    </div> <!-- end col-->
    <div class="col-lg-6">
        <div class="card">
            <div class="row g-0">
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">This is a wider card with supporting text below as a natural lead-in to
                            additional content. This content is a little bit longer.</p>
                        <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                    </div> <!-- end card-body -->
                </div> <!-- end col -->
                <div class="col-md-4">
                    <img alt="img-2" class="img-fluid rounded-end h-100" src="/images/small/img-2.jpg" />
                </div> <!-- end col -->
            </div> <!-- end row-->
        </div> <!-- end card -->
    </div> <!-- end col-->
</div> <!-- end row -->
<!-- Stretched Link Start -->
<div class="row">
    <div class="col-12">
        <h5 class="card-title mb-3">Stretched link</h5>
    </div>
</div>
<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <img alt="img-1" class="card-img-top" src="/images/small/img-1.jpg" />
            <div class="card-body">
                <h5 class="card-title mb-2">Card with stretched link</h5>
                <a class="btn btn-primary mt-2 stretched-link" href="javascript:void(0);">Go somewhere</a>
            </div> <!-- end card-body -->
        </div> <!-- end card -->
    </div> <!-- end col-->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <img alt="img-2" class="card-img-top" src="/images/small/img-2.jpg" />
            <div class="card-body">
                <h5 class="card-title mb-2"><a class="text-primary stretched-link" href="javascript:void(0);">Card with
                        stretched link</a></h5>
                <p class="card-text">
                    Some quick example text to build on the card up the bulk of the card's content.
                </p>
            </div> <!-- end card-body -->
        </div> <!-- end card -->
    </div> <!-- end col-->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <img alt="img-3" class="card-img-top" src="/images/small/img-3.jpg" />
            <div class="card-body">
                <h5 class="card-title mb-2">Card with stretched link</h5>
                <a class="btn btn-primary mt-2 stretched-link" href="javascript:void(0);">Go somewhere</a>
            </div> <!-- end card-body -->
        </div> <!-- end card -->
    </div> <!-- end col-->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <img alt="img-4" class="card-img-top" src="/images/small/img-4.jpg" />
            <div class="card-body">
                <h5 class="card-title mb-2"><a class="stretched-link" href="javascript:void(0);">Card with stretched
                        link</a></h5>
                <p class="card-text">
                    Some quick example text to build on the card up the bulk of the card's content.
                </p>
            </div> <!-- end card-body -->
        </div> <!-- end card -->
    </div> <!-- end col-->
</div> <!-- end row -->
<!-- Card Group Start -->
<div class="row">
    <div class="col-12">
        <h5 class="card-title mb-3">Card Group</h5>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card-group mb-3">
            <div class="card d-block">
                <img alt="img-1" class="card-img-top" src="/images/small/img-1.jpg" />
                <div class="card-body">
                    <h5 class="card-title mb-2">Card title</h5>
                    <p class="card-text">This is a wider card with supporting text below as a
                        natural lead-in to additional content. This content is a little bit
                        longer.</p>
                    <p class="card-text">
                        <small class="text-muted">Last updated 3 mins ago</small>
                    </p>
                </div>
            </div>
            <div class="card d-block">
                <img alt="img-2" class="card-img-top" src="/images/small/img-2.jpg" />
                <div class="card-body">
                    <h5 class="card-title mb-2">Card title</h5>
                    <p class="card-text">This card has supporting text below as a natural
                        lead-in to additional content.</p>
                    <p class="card-text">
                        <small class="text-muted">Last updated 3 mins ago</small>
                    </p>
                </div>
            </div>
            <div class="card d-block">
                <img alt="img-3" class="card-img-top" src="/images/small/img-3.jpg" />
                <div class="card-body">
                    <h5 class="card-title mb-2">Card title</h5>
                    <p class="card-text">This is a wider card with supporting text below as a
                        natural lead-in to additional content. This card has even longer content
                        than the first to show that equal height action.</p>
                    <p class="card-text">
                        <small class="text-muted">Last updated 3 mins ago</small>
                    </p>
                </div>
            </div>
        </div> <!-- end card-group-->
    </div> <!-- end col-->
</div> <!-- end row -->
<!-- Card Decks Start -->
<div class="row">
    <div class="col-12">
        <h5 class="card-title mb-3">Card Decks</h5>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="row row-cols-1 row-cols-md-3 g-3">
            <div class="col">
                <div class="card">
                    <img alt="img-4" class="card-img-top" src="/images/small/img-4.jpg" />
                    <div class="card-body">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">This is a longer card with supporting text below as
                            a natural lead-in to additional content. This content is a little
                            bit longer.</p>
                        <p class="card-text">
                            <small class="text-muted">Last updated 3 mins ago</small>
                        </p>
                    </div><!-- end card-body -->
                </div><!-- end card -->
            </div>
            <div class="col">
                <div class="card">
                    <img alt="img-3" class="card-img-top" src="/images/small/img-3.jpg" />
                    <div class="card-body">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">This is a longer card with supporting text below as
                            a natural lead-in to additional content. This content is a little
                            bit longer.</p>
                        <p class="card-text">
                            <small class="text-muted">Last updated 3 mins ago</small>
                        </p>
                    </div><!-- end card-body -->
                </div><!-- end card -->
            </div>
            <div class="col">
                <div class="card">
                    <img alt="img-2" class="card-img-top" src="/images/small/img-2.jpg" />
                    <div class="card-body">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">This is a longer card with supporting text below as
                            a natural lead-in to additional content. This content is a little
                            bit longer.</p>
                        <p class="card-text">
                            <small class="text-muted">Last updated 3 mins ago</small>
                        </p>
                    </div><!-- end card-body -->
                </div><!-- end card -->
            </div>
        </div>
    </div> <!-- end col-->
</div> <!-- end row -->
@endsection