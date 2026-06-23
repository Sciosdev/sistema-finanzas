@extends('layouts.vertical', ['title' => 'Pricing Plans'])

@section('content')
<div class="container-xxl">
     <div class="row">
          <div class="col-12">
               <div class="text-center my-5">
                    <h3 class="fw-bold mt-5">Flexible Pricing Plans</h3>
                    <p class="text-muted text-center">
                         Choose the perfect plan that fits your needs and scale your business effortlessly.
                    </p>
               </div>
               <div class="row justify-content-center">
                    <div class="col-lg-3">
                         <div class="card card-pricing">
                              <div class="card-body">
                                   <h5 class="mt-0 mb-3 fs-14 text-uppercase fw-semibold">Starter Pack</h5>
                                   <h2 class="mt-0 mb-3 fw-bold">$5 <span class="fs-14 fw-medium text-muted">/
                                             Month</span></h2>
                                   <ul
                                        class="card-pricing-features text-muted border-top pt-2 mt-2 ps-0 list-unstyled">
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>10 GB Storage</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>200 GB Bandwidth</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>1 Domain</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Email Support</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>1 User</li>
                                   </ul>
                                   <div class="mt-4 text-center">
                                        <button class="btn btn-primary px-sm-4 w-100">Get Started</button>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-lg-3">
                         <div class="card card-pricing">
                              <div class="card-body">
                                   <div class="pricing-ribbon pricing-ribbon-primary float-end">Best Value</div>
                                   <h5 class="mt-0 mb-3 fs-14 text-uppercase fw-semibold">Growth Pack</h5>
                                   <h2 class="mt-0 mb-3 fw-bold">$15 <span class="fs-14 fw-medium text-muted">/
                                             Month</span></h2>
                                   <ul
                                        class="card-pricing-features text-muted border-top pt-2 mt-2 ps-0 list-unstyled">
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>100 GB Storage</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>1 TB Bandwidth</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>5 Domains</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Email &amp; Chat Support</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>10 Users</li>
                                   </ul>
                                   <div class="mt-4 text-center">
                                        <button class="btn btn-primary px-sm-4 disabled w-100">Current
                                             Plan</button>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-lg-3">
                         <div class="card card-pricing">
                              <div class="card-body">
                                   <h5 class="mt-0 mb-3 fs-14 text-uppercase fw-semibold">Business Pack</h5>
                                   <h2 class="mt-0 mb-3 fw-bold">$30 <span class="fs-14 fw-medium text-muted">/
                                             Month</span></h2>
                                   <ul
                                        class="card-pricing-features text-muted border-top pt-2 mt-2 ps-0 list-unstyled">
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>500 GB Storage</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>5 TB Bandwidth</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>10 Domains</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Priority Support</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>25 Users</li>
                                   </ul>
                                   <div class="mt-4 text-center">
                                        <button class="btn btn-primary px-sm-4 w-100">Get Started</button>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-lg-3">
                         <div class="card card-pricing">
                              <div class="card-body">
                                   <h5 class="mt-0 mb-3 fs-14 text-uppercase fw-semibold">Enterprise Pack</h5>
                                   <h2 class="mt-0 mb-3 fw-bold">$50 <span class="fs-14 fw-medium text-muted">/
                                             Month</span></h2>
                                   <ul
                                        class="card-pricing-features text-muted border-top pt-2 mt-2 ps-0 list-unstyled">
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Unlimited Storage</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Unlimited Bandwidth</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Unlimited Domains</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Dedicated Support</li>
                                        <li class="text-dark"><i class="text-primary fs-15 me-1"
                                                  data-lucide="circle-check"></i>Unlimited Users</li>
                                   </ul>
                                   <div class="mt-4 text-center">
                                        <button class="btn btn-primary px-sm-4 w-100">Get Started</button>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div>
     <div class="text-center my-5">
          <h3 class="fw-bold mt-5">FAQ</h3>
          <p class="text-muted text-center">
               Choose the perfect plan that fits your needs and scale your business effortlessly.
          </p>
     </div>
     <div class="row g-xl-4 mb-4">
          <div class="col-xl-6">
               <!-- FAQs -->
               <div class="accordion">
                    <div class="accordion-item">
                         <h2 class="accordion-header">
                              <button aria-controls="faq1" aria-expanded="true" class="accordion-button fw-medium"
                                   data-bs-target="#faq1" data-bs-toggle="collapse" type="button">
                                   Can I use Dummy FAQs for my website or project?
                              </button>
                         </h2>
                         <div aria-labelledby="faq1" class="accordion-collapse collapse show" id="faq1">
                              <div class="accordion-body">
                                   Yes, you can use Dummy FAQs to populate your website or project during
                                   development or testing phases. They help simulate the appearance and
                                   functionality of a real FAQ section without requiring actual content.
                              </div>
                         </div>
                    </div>
                    <div class="accordion-item">
                         <h2 class="accordion-header">
                              <button aria-controls="faq2" aria-expanded="false"
                                   class="accordion-button fw-medium collapsed" data-bs-target="#faq2"
                                   data-bs-toggle="collapse" type="button">
                                   Are Dummy FAQs suitable for customer support purposes?
                              </button>
                         </h2>
                         <div aria-labelledby="faq2" class="accordion-collapse collapse" id="faq2">
                              <div class="accordion-body">
                                   While Dummy FAQs can be used internally for training customer support teams,
                                   they are not suitable for public-facing customer support. Real FAQs should be
                                   based on genuine customer inquiries to provide accurate and helpful information.
                              </div>
                         </div>
                    </div>
                    <div class="accordion-item">
                         <h2 class="accordion-header">
                              <button aria-controls="faq3" aria-expanded="false"
                                   class="accordion-button fw-medium collapsed" data-bs-target="#faq3"
                                   data-bs-toggle="collapse" type="button">
                                   Do Dummy FAQs require attribution?
                              </button>
                         </h2>
                         <div class="accordion-collapse collapse" id="faq3">
                              <div class="accordion-body">
                                   No, Dummy FAQs do not require attribution since they are not based on real
                                   questions or contributed by individuals. You can use them freely for internal
                                   testing or demonstration purposes.
                              </div>
                         </div>
                    </div>
               </div>
          </div>
          <div class="col-xl-6">
               <!-- FAQs -->
               <div class="accordion">
                    <div class="accordion-item">
                         <h2 class="accordion-header">
                              <button aria-controls="faq7" aria-expanded="true" class="accordion-button fw-medium"
                                   data-bs-target="#faq7" data-bs-toggle="collapse" type="button">
                                   Can I test my website/app with Dummy Payments?
                              </button>
                         </h2>
                         <div aria-labelledby="faq7" class="accordion-collapse collapse show" id="faq7">
                              <div class="accordion-body">
                                   Yes, Dummy Payments are commonly used by developers and businesses to test the
                                   functionality of e-commerce platforms, mobile apps, and payment gateways. They
                                   help identify and resolve issues without risking real transactions.
                              </div>
                         </div>
                    </div>
                    <div class="accordion-item">
                         <h2 class="accordion-header">
                              <button aria-controls="faq8" aria-expanded="false"
                                   class="accordion-button fw-medium collapsed" data-bs-target="#faq8"
                                   data-bs-toggle="collapse" type="button">
                                   Are Dummy Payments secure?
                              </button>
                         </h2>
                         <div aria-labelledby="faq8" class="accordion-collapse collapse" id="faq8">
                              <div class="accordion-body">
                                   Dummy Payments used in controlled environments for training or demonstration
                                   purposes are generally secure. However, it"s crucial not to confuse them with
                                   real transactions and avoid entering genuine financial information.
                              </div>
                         </div>
                    </div>
                    <div class="accordion-item">
                         <h2 class="accordion-header">
                              <button aria-controls="faq9" aria-expanded="false"
                                   class="accordion-button fw-medium collapsed" data-bs-target="#faq9"
                                   data-bs-toggle="collapse" type="button">
                                   How can I differentiate between a Dummy Payment and a real one?
                              </button>
                         </h2>
                         <div class="accordion-collapse collapse" id="faq9">
                              <div class="accordion-body">
                                   Real payments involve the transfer of actual funds, resulting in a change in
                                   financial balances. Dummy Payments, on the other hand, do not involve any
                                   monetary exchange and are typically labeled or indicated as test transactions.
                                   Always verify the authenticity of transactions before proceeding with any
                                   action.
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </div> <!-- end row-->
</div>
@endsection