@extends('layouts.vertical', ['title' => 'Tabs'])

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Nav Tabs</h5>
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#home">
                            <span class="d-block d-sm-none"><i class="" data-lucide="house"></i></span>
                            <span class="d-none d-sm-block">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a aria-expanded="true" class="nav-link active" data-bs-toggle="tab" href="#profile">
                            <span class="d-block d-sm-none"><i class="" data-lucide="user"></i></span>
                            <span class="d-none d-sm-block">Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#messages">
                            <span class="d-block d-sm-none"><i class="" data-lucide="mail"></i></span>
                            <span class="d-none d-sm-block">Messages</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content text-muted">
                    <div class="tab-pane" id="home">
                        <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium,
                            totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae
                            vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                            consequuntur magni dolores eos qui
                            ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor
                            sit amet, consectetur, adipisci
                            velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
                            quaerat voluptatem?
                        </p>
                    </div>
                    <div class="tab-pane show active" id="profile">
                        <p class="mb-0">Li Europan lingues es membres del sam familie. Lor separat existentie es un
                            myth. Por scientie, musica, sport etc,
                            litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li
                            pronunciation e li plu commun vocabules.
                            Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi
                            traductores. At solmen va esser
                            necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues
                            coalesce, li grammatica del resultant
                            lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va
                            esser plu simplic e regulari quam
                            li existent Europan lingues.
                        </p>
                    </div>
                    <div class="tab-pane" id="messages">
                        <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium,
                            totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae
                            vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                            consequuntur magni dolores eos qui
                            ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor
                            sit amet, consectetur, adipisci
                            velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
                            quaerat voluptatem?
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Tabs Justified</h5>
                <ul class="nav nav-tabs nav-justified">
                    <li class="nav-item">
                        <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#homeTabsJustified">
                            <span class="d-block d-sm-none"><i class="" data-lucide="house"></i></span>
                            <span class="d-none d-sm-block">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a aria-expanded="true" class="nav-link active" data-bs-toggle="tab"
                            href="#profileTabsJustified">
                            <span class="d-block d-sm-none"><i class="" data-lucide="user"></i></span>
                            <span class="d-none d-sm-block">Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#messagesTabsJustified">
                            <span class="d-block d-sm-none"><i class="" data-lucide="mail"></i></span>
                            <span class="d-none d-sm-block">Messages</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content pt-2 text-muted">
                    <div class="tab-pane" id="homeTabsJustified">
                        <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium,
                            totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae
                            vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                            consequuntur magni dolores eos qui
                            ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor
                            sit amet, consectetur, adipisci
                            velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
                            quaerat voluptatem?
                        </p>
                    </div>
                    <div class="tab-pane show active" id="profileTabsJustified">
                        <p class="mb-0">Li Europan lingues es membres del sam familie. Lor separat existentie es un
                            myth. Por scientie, musica, sport etc,
                            litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li
                            pronunciation e li plu commun vocabules.
                            Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi
                            traductores. At solmen va esser
                            necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues
                            coalesce, li grammatica del resultant
                            lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va
                            esser plu simplic e regulari quam
                            li existent Europan lingues.
                        </p>
                    </div>
                    <div class="tab-pane" id="messagesTabsJustified">
                        <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium,
                            totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae
                            vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                            consequuntur magni dolores eos qui
                            ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor
                            sit amet, consectetur, adipisci
                            velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
                            quaerat voluptatem?
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Nav Pills</h5>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#homePill">
                            <span class="d-block d-sm-none"><i class="" data-lucide="house"></i></span>
                            <span class="d-none d-sm-block">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a aria-expanded="true" class="nav-link active" data-bs-toggle="tab" href="#profilePill">
                            <span class="d-block d-sm-none"><i class="" data-lucide="user"></i></span>
                            <span class="d-none d-sm-block">Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#messagesPill">
                            <span class="d-block d-sm-none"><i class="" data-lucide="mail"></i></span>
                            <span class="d-none d-sm-block">Messages</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content pt-2 text-muted">
                    <div class="tab-pane" id="homePill">
                        <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium,
                            totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae
                            vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                            consequuntur magni dolores eos qui
                            ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor
                            sit amet, consectetur, adipisci
                            velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
                            quaerat voluptatem?
                        </p>
                    </div>
                    <div class="tab-pane show active" id="profilePill">
                        <p class="mb-0">Li Europan lingues es membres del sam familie. Lor separat existentie es un
                            myth. Por scientie, musica, sport etc,
                            litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li
                            pronunciation e li plu commun vocabules.
                            Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi
                            traductores. At solmen va esser
                            necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues
                            coalesce, li grammatica del resultant
                            lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va
                            esser plu simplic e regulari quam
                            li existent Europan lingues.
                        </p>
                    </div>
                    <div class="tab-pane" id="messagesPill">
                        <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                            doloremque laudantium,
                            totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae
                            vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                            consequuntur magni dolores eos qui
                            ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor
                            sit amet, consectetur, adipisci
                            velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
                            quaerat voluptatem?
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Pills Justified</h5>
                <div class="d-flex flex-wrap gap-2">
                    <ul class="nav nav-pills nav-justified p-1">
                        <li class="nav-item">
                            <a aria-expanded="false" class="nav-link" data-bs-toggle="tab" href="#homePillJustified">
                                <span class="d-block d-sm-none"><i class="" data-lucide="house"></i></span>
                                <span class="d-none d-sm-block">Home</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a aria-expanded="true" class="nav-link active" data-bs-toggle="tab"
                                href="#profilePillJustified">
                                <span class="d-block d-sm-none"><i class="" data-lucide="user"></i></span>
                                <span class="d-none d-sm-block">Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a aria-expanded="false" class="nav-link" data-bs-toggle="tab"
                                href="#messagesPillJustified">
                                <span class="d-block d-sm-none"><i class="" data-lucide="mail"></i></span>
                                <span class="d-none d-sm-block">Messages</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content pt-2 text-muted">
                        <div class="tab-pane" id="homePillJustified">
                            <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                doloremque laudantium,
                                totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto
                                beatae vitae dicta sunt explicabo.
                                Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                                consequuntur magni dolores eos qui
                                ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia
                                dolor sit amet, consectetur, adipisci
                                velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam
                                aliquam quaerat voluptatem?
                            </p>
                        </div>
                        <div class="tab-pane show active" id="profilePillJustified">
                            <p class="mb-0">Li Europan lingues es membres del sam familie. Lor separat existentie es un
                                myth. Por scientie, musica, sport etc,
                                litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li
                                pronunciation e li plu commun vocabules.
                                Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar
                                custosi traductores. At solmen va esser
                                necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues
                                coalesce, li grammatica del resultant
                                lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va
                                esser plu simplic e regulari quam
                                li existent Europan lingues.
                            </p>
                        </div>
                        <div class="tab-pane" id="messagesPillJustified">
                            <p class="mb-0">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                doloremque laudantium,
                                totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto
                                beatae vitae dicta sunt explicabo.
                                Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia
                                consequuntur magni dolores eos qui
                                ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia
                                dolor sit amet, consectetur, adipisci
                                velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam
                                aliquam quaerat voluptatem?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Tabs Vertical Left</h5>
                <div class="row">
                    <div class="col-sm-3 mb-2 mb-sm-0">
                        <div aria-orientation="vertical" class="nav flex-column nav-pills" id="vl-pills-tab"
                            role="tablist">
                            <a aria-controls="vl-pills-home" aria-selected="true" class="nav-link active show"
                                data-bs-toggle="pill" href="#vl-pills-home" id="vl-pills-home-tab" role="tab">
                                <span>Home</span>
                            </a>
                            <a aria-controls="vl-pills-profile" aria-selected="false" class="nav-link"
                                data-bs-toggle="pill" href="#vl-pills-profile" id="vl-pills-profile-tab" role="tab">
                                <span>Profile</span>
                            </a>
                            <a aria-controls="vl-pills-settings" aria-selected="false" class="nav-link"
                                data-bs-toggle="pill" href="#vl-pills-settings" id="vl-pills-settings-tab" role="tab">
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-9">
                        <div class="tab-content pt-0" id="vl-pills-tabContent">
                            <div aria-labelledby="vl-pills-home-tab" class="tab-pane fade active show"
                                id="vl-pills-home" role="tabpanel">
                                <p class="mb-0">
                                    Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                    doloremque
                                    laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi
                                    architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia
                                    voluptas
                                    sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui
                                    ratione
                                    voluptatem sequi nesciunt.
                                </p>
                            </div>
                            <div aria-labelledby="vl-pills-profile-tab" class="tab-pane fade" id="vl-pills-profile"
                                role="tabpanel">
                                <p class="mb-0">
                                    Li Europan lingues es membres del sam familie. Lor separat existentie es un myth.
                                    Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues
                                    differe
                                    solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe
                                    al desirabilite de un nov lingua franca: On refusa continuar payar custosi
                                    traductores.
                                    At solmen va esser necessi far uniform.
                                </p>
                            </div>
                            <div aria-labelledby="vl-pills-settings-tab" class="tab-pane fade" id="vl-pills-settings"
                                role="tabpanel">
                                <p class="mb-0">
                                    Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                    doloremque
                                    laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi
                                    architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia
                                    voluptas
                                    sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui
                                    ratione
                                    voluptatem sequi nesciunt.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">Tabs Vertical Right</h5>
                <div class="row">
                    <div class="col-sm-9 mb-2 mb-sm-0">
                        <div class="tab-content pt-0" id="vr-pills-tabContent">
                            <div aria-labelledby="vr-pills-home-tab" class="tab-pane fade active show"
                                id="vr-pills-home" role="tabpanel">
                                <p class="mb-0">
                                    Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                    doloremque
                                    laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi
                                    architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia
                                    voluptas
                                    sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui
                                    ratione
                                    voluptatem sequi nesciunt.
                                </p>
                            </div>
                            <div aria-labelledby="vr-pills-profile-tab" class="tab-pane fade" id="vr-pills-profile"
                                role="tabpanel">
                                <p class="mb-0">
                                    Li Europan lingues es membres del sam familie. Lor separat existentie es un myth.
                                    Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues
                                    differe
                                    solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe
                                    al desirabilite de un nov lingua franca: On refusa continuar payar custosi
                                    traductores.
                                    At solmen va esser necessi far uniform.
                                </p>
                            </div>
                            <div aria-labelledby="vr-pills-settings-tab" class="tab-pane fade" id="vr-pills-settings"
                                role="tabpanel">
                                <p class="mb-0">
                                    Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                    doloremque
                                    laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi
                                    architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia
                                    voluptas
                                    sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui
                                    ratione
                                    voluptatem sequi nesciunt.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div aria-orientation="vertical" class="nav flex-column nav-pills" id="vr-pills-tab"
                            role="tablist">
                            <a aria-controls="vr-pills-home" aria-selected="true" class="nav-link active show"
                                data-bs-toggle="pill" href="#vr-pills-home" id="vr-pills-home-tab" role="tab">
                                <span>Home</span>
                            </a>
                            <a aria-controls="vr-pills-profile" aria-selected="false" class="nav-link"
                                data-bs-toggle="pill" href="#vr-pills-profile" id="vr-pills-profile-tab" role="tab">
                                <span>Profile</span>
                            </a>
                            <a aria-controls="vr-pills-settings" aria-selected="false" class="nav-link"
                                data-bs-toggle="pill" href="#vr-pills-settings" id="vr-pills-settings-tab" role="tab">
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection