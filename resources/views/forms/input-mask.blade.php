@extends('layouts.vertical', ['title' => 'Input Masks'])

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Input Masks</h5>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-6">
                        <form action="#">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input class="form-control" data-mask-format="00/00/0000" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "DD/MM/YYYY"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hour</label>
                                <input class="form-control" data-mask-format="00:00:00" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "HH:MM:SS"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date &amp; Hour</label>
                                <input class="form-control" data-mask-format="00/00/0000 00:00:00"
                                    data-toggle="input-mask" type="text" />
                                <span class="fs-13 text-muted">e.g "DD/MM/YYYY HH:MM:SS"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ZIP Code</label>
                                <input class="form-control" data-mask-format="00000-000" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "xxxxx-xxx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Crazy Zip Code</label>
                                <input class="form-control" data-mask-format="0-00-00-00" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "x-xx-xx-xx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Money</label>
                                <input class="form-control" data-mask-format="000.000.000.000.000,00"
                                    data-reverse="true" data-toggle="input-mask" type="text" />
                                <span class="fs-13 text-muted">e.g "Your money"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Money 2</label>
                                <input class="form-control" data-mask-format="#.##0,00" data-reverse="true"
                                    data-toggle="input-mask" type="text" />
                                <span class="fs-13 text-muted">e.g "#.##0,00"</span>
                            </div>
                        </form>
                    </div> <!-- end col -->
                    <div class="col-md-6">
                        <form action="#">
                            <div class="mb-3">
                                <label class="form-label">Telephone</label>
                                <input class="form-control" data-mask-format="0000-0000" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "xxxx-xxxx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telephone with Code Area</label>
                                <input class="form-control" data-mask-format="(00) 0000-0000" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "(xx) xxxx-xxxx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">US Telephone</label>
                                <input class="form-control" data-mask-format="(000) 000-0000" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "(xxx) xxx-xxxx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">São Paulo Celphones</label>
                                <input class="form-control" data-mask-format="(00) 00000-0000" data-toggle="input-mask"
                                    type="text" />
                                <span class="fs-13 text-muted">e.g "(xx) xxxxx-xxxx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CPF</label>
                                <input class="form-control" data-mask-format="000.000.000-00" data-reverse="true"
                                    data-toggle="input-mask" type="text" />
                                <span class="fs-13 text-muted">e.g "xxx.xxx.xxxx-xx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CNPJ</label>
                                <input class="form-control" data-mask-format="00.000.000/0000-00" data-reverse="true"
                                    data-toggle="input-mask" type="text" />
                                <span class="fs-13 text-muted">e.g "xx.xxx.xxx/xxxx-xx"</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IP Address</label>
                                <input class="form-control" data-mask-format="099.099.099.099" data-reverse="true"
                                    data-toggle="input-mask" type="text" />
                                <span class="fs-13 text-muted">e.g "xxx.xxx.xxx.xxx"</span>
                            </div>
                        </form>
                    </div> <!-- end col -->
                </div>
            </div><!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->
</div> <!-- end row -->
@endsection