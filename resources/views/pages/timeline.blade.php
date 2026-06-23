@extends('layouts.vertical', ['title' => 'Timeline'])

@section('content')
<div class="container-xxl">
    <div class="row">
        <div class="col-lg-12">
            <div class="timeline">
                <article class="timeline-time">
                    <div class="time-show d-flex align-items-center justify-content-center mt-0">
                        <h5 class="mb-0 text-uppercase fs-14 fw-semibold">Today</h5>
                    </div>
                </article>
                <article class="timeline-item timeline-item-left">
                    <div class="timeline-desk">
                        <div class="timeline-box clearfix">
                            <span class="timeline-icon"></span>
                            <div class="overflow-hidden">
                                <div class="card d-inline-block">
                                    <div class="card-body">
                                        <h5 class="mt-0 fs-16">Launched our new website design!</h5>
                                        <p class="text-muted mb-0">Our revamped website is now live, featuring a
                                            fresh look and enhanced user experience.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="timeline-item">
                    <div class="timeline-desk">
                        <div class="timeline-box clearfix">
                            <span class="timeline-icon"></span>
                            <div class="overflow-hidden">
                                <div class="card d-inline-block">
                                    <div class="card-body">
                                        <h5 class="mt-0 fs-16">Completed a successful client meeting.</h5>
                                        <p class="text-muted mb-0">Discussed upcoming project goals and
                                            finalized the strategy with the team.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="timeline-time">
                    <div class="time-show d-flex align-items-center mt-0">
                        <h5 class="mb-0 text-uppercase fs-14 fw-semibold">Yesterday</h5>
                    </div>
                </article>
                <article class="timeline-item timeline-item-left">
                    <div class="timeline-desk">
                        <div class="timeline-box clearfix">
                            <span class="timeline-icon"></span>
                            <div class="overflow-hidden">
                                <div class="card d-inline-block">
                                    <div class="card-body">
                                        <h5 class="mt-0 fs-16">Reached 10k followers on our social media.</h5>
                                        <p class="text-muted mb-0">A huge milestone! Thank you all for the
                                            support and engagement.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="timeline-item">
                    <div class="timeline-desk">
                        <div class="timeline-box clearfix">
                            <span class="timeline-icon"></span>
                            <div class="overflow-hidden">
                                <div class="card d-inline-block">
                                    <div class="card-body">
                                        <h5 class="mt-0 fs-16">Published a new blog post on UX trends.</h5>
                                        <p class="text-muted mb-0">Exploring the latest trends in UX design and
                                            how they impact digital experiences.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="timeline-time">
                    <div class="time-show d-flex align-items-center mt-0">
                        <h5 class="mb-0 text-uppercase fs-14 fw-semibold">Last Week</h5>
                    </div>
                </article>
                <article class="timeline-item timeline-item-left">
                    <div class="timeline-desk">
                        <div class="timeline-box clearfix">
                            <span class="timeline-icon"></span>
                            <div class="overflow-hidden">
                                <div class="card d-inline-block">
                                    <div class="card-body">
                                        <h5 class="mt-0 fs-16">Hosted a design workshop for startups.</h5>
                                        <p class="text-muted mb-0">Shared insights and best practices with
                                            emerging designers and entrepreneurs.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="timeline-item">
                    <div class="timeline-desk">
                        <div class="timeline-box clearfix">
                            <span class="timeline-icon"></span>
                            <div class="overflow-hidden">
                                <div class="card d-inline-block">
                                    <div class="card-body">
                                        <h5 class="mt-0 fs-16">Started development on our latest project.</h5>
                                        <p class="text-muted mb-0">Exciting new features coming soon! Stay tuned
                                            for updates.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div>
@endsection
