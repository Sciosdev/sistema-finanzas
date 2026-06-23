@extends('layouts.vertical', ['title' => 'Invoice Details'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Logo & title -->
                <div class="clearfix">
                    <div class="float-sm-end">
                        <div class="auth-logo">
                            <img alt="logo-dark" class="logo-dark me-1" height="24" src="/images/logo-dark.png" />
                            <img alt="logo-dark" class="logo-light me-1" height="24" src="/images/logo-white.png" />
                        </div>
                        <address class="mt-3">
                            123 E-Commerce St,<br />
                            New York, NY, 10001 <br />
                            <abbr title="Phone">P:</abbr> (212) 555-7890
                        </address>
                    </div>
                    <div class="float-sm-start">
                        <h5 class="card-title mb-2">Invoice: #EC20241234</h5>
                        <p>20 Feb, 2025</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="fw-normal text-muted">Customer</h6>
                        <h6 class="fs-14 fw-bold"> Jessica Carter</h6>
                        <address>
                            567 Market Ave,<br />
                            Los Angeles, CA, 90012<br />
                            <abbr title="Phone">P:</abbr> (310) 555-1234
                        </address>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive table-borderless text-nowrap mt-3 table-centered">
                            <table class="table mb-0">
                                <thead class="bg-light bg-opacity-50">
                                    <tr>
                                        <th class="border-0 py-2">Product Name</th>
                                        <th class="border-0 py-2">Quantity</th>
                                        <th class="border-0 py-2">Price</th>
                                        <th class="text-end border-0 py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Wireless Bluetooth Earbuds</td>
                                        <td>2</td>
                                        <td>$79.99</td>
                                        <td class="text-end">$159.98</td>
                                    </tr>
                                    <tr>
                                        <td>Smartwatch Series 7</td>
                                        <td>1</td>
                                        <td>$299.99</td>
                                        <td class="text-end">$299.99</td>
                                    </tr>
                                    <tr>
                                        <td>4K Ultra HD Smart TV - 55"</td>
                                        <td>1</td>
                                        <td>$699.99</td>
                                        <td class="text-end">$699.99</td>
                                    </tr>
                                    <tr>
                                        <td>Gaming Keyboard RGB</td>
                                        <td>3</td>
                                        <td>$49.99</td>
                                        <td class="text-end">$149.97</td>
                                    </tr>
                                    <tr>
                                        <td>Wireless Charging Station</td>
                                        <td>2</td>
                                        <td>$39.99</td>
                                        <td class="text-end">$79.98</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-sm-7">
                        <div class="clearfix pt-xl-3 pt-0">
                            <h6 class="text-muted">Notes:</h6>
                            <small class="text-muted">
                                Payment is due within 7 days from the date of invoice. Payments can be made via credit
                                card, PayPal, or bank transfer.
                                Late payments may be subject to additional charges.
                            </small>
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <div class="float-end">
                            <p><span class="fw-medium">Sub-total :</span>
                                <span class="float-end">$1389.91</span>
                            </p>
                            <p><span class="fw-medium">Discount (5%) :</span>
                                <span class="float-end">    $69.50</span>
                            </p>
                            <h3>$1320.41 USD</h3>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <div class="mt-5 mb-1">
                    <div class="text-end d-print-none">
                        <a class="btn btn-primary" href="javascript:window.print()">Print</a>
                        <a class="btn btn-outline-primary" href="javascript:void(0);">Submit</a>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end col -->
</div> <!-- end row -->
@endsection