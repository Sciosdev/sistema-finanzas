@extends('layouts.vertical', ['title' => 'Forms Basic'])

@section('content')
<div class="card">
     <div class="card-body">
          <div class="row g-5">
               <div class="col-lg-12">
                    <h5 class="card-title mb-5">
                         Basic Example
                    </h5>
                    <div class="mb-3">
                         <label class="form-label" for="simpleinput">Text</label>
                         <input class="form-control" id="simpleinput" type="text" />
                    </div>
                    <div class="mb-3">
                         <label class="form-label" for="example-email">Email</label>
                         <input class="form-control" id="example-email" name="example-email" placeholder="Email"
                              type="email" />
                    </div>
                    <div class="mb-3">
                         <label class="form-label" for="example-password">Password</label>
                         <input class="form-control" id="example-password" type="password" value="password" />
                    </div>
                    <div class="mb-3">
                         <label class="form-label" for="example-palaceholder">Placeholder</label>
                         <input class="form-control" id="example-palaceholder" placeholder="placeholder" type="text" />
                    </div>
                    <div class="mb-3">
                         <label class="form-label" for="example-textarea">Text area</label>
                         <textarea class="form-control" id="example-textarea" rows="5"></textarea>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-5">
                         Input Sizing
                    </h5>
                    <div>
                         <div class="d-flex flex-column gap-2">
                              <input aria-label=".form-control-lg example" class="form-control form-control-lg"
                                   placeholder=".form-control-lg" type="text" />
                              <input aria-label="default input example" class="form-control" placeholder="Default input"
                                   type="text" />
                              <input aria-label=".form-control-sm example" class="form-control form-control-sm"
                                   placeholder=".form-control-sm" type="text" />
                         </div>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-5">
                         Disabled Input
                    </h5>
                    <div>
                         <div class="d-flex flex-column gap-2">
                              <input aria-label="Disabled input example" class="form-control" disabled=""
                                   placeholder="Disabled input" type="text" />
                              <input aria-label="Disabled input example" class="form-control" disabled="" readonly=""
                                   type="text" value="Disabled readonly input" />
                         </div>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-5">
                         Readonly Input
                    </h5>
                    <div>
                         <div class="d-flex flex-column gap-2">
                              <input aria-label="readonly input example" class="form-control" readonly="" type="text"
                                   value="Readonly input here..." />
                              <input class="form-control-plaintext" id="staticEmail" readonly="" type="text"
                                   value="email@example.com" />
                         </div>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-5">
                         Datalists input
                    </h5>
                    <div>
                         <label class="form-label" for="exampleDataList">Datalist example</label>
                         <input class="form-control" id="exampleDataList" list="datalistOptions"
                              placeholder="Type to search..." />
                         <datalist id="datalistOptions">
                              <option value="San Francisco">
                              <option value="New York">
                              <option value="Seattle">
                              <option value="Los Angeles">
                              <option value="Chicago">
                              </option>
                              </option>
                              </option>
                              </option>
                              </option>
                         </datalist>
                    </div>
               </div>
               <div class="col-lg-12">
                    <h5 class="card-title mb-5">
                         Select
                    </h5>
                    <div class="mb-3">
                         <label class="form-label" for="example-select">Default Input Select</label>
                         <select class="form-select" id="example-select">
                              <option>1</option>
                              <option>2</option>
                              <option>3</option>
                              <option>4</option>
                              <option>5</option>
                         </select>
                    </div>
                    <p class="card-subtitle">The <code>multiple</code> attribute is also supported:</p>
                    <div class="mb-3">
                         <label class="form-label" for="example-multiselect">Multiple Select</label>
                         <select class="form-control" id="example-multiselect" multiple="">
                              <option>1</option>
                              <option>2</option>
                              <option>3</option>
                              <option>4</option>
                              <option>5</option>
                         </select>
                    </div>
                    <p class="card-subtitle">As is the <code>size</code> attribute:</p>
                    <label class="form-label" for="example-multiselectsize">Multiple Select Size</label>
                    <select aria-label="size 3 select example" class="form-select" id="example-multiselectsize"
                         size="3">
                         <option selected="">Open this select menu</option>
                         <option value="1">One</option>
                         <option value="2">Two</option>
                         <option value="3">Three</option>
                    </select>
               </div> <!-- end col -->
          </div> <!-- end row -->
     </div>
</div>
@endsection