@extends('layouts.vertical', ['title' => 'Basic'])

@section('content')
<div class="row">
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Basic Example
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">Striped Rows Table</h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-striped table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td> Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Variants
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table">
                              <thead>
                                   <tr>
                                        <th scope="col">Class</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>Default</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-primary">
                                        <td>Primary</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-secondary">
                                        <td>Secondary</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-success">
                                        <td>Success</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-danger">
                                        <td>Danger</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-warning">
                                        <td>Warning</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-info">
                                        <td>Info</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-light">
                                        <td>Light</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr class="table-dark">
                                        <td>Dark</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Striped Rows Table Dark
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-dark table-striped table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td> Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Striped Rows Table Success
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-success table-striped table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td> Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Striped columns
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive-sm">
                         <table class="table table-striped-columns table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Striped columns Dark
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive-sm">
                         <table class="table table-dark table-striped-columns table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Striped columns success
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive-sm">
                         <table class="table table-success table-striped-columns table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Hoverable rows
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-hover table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Hoverable rows Dark <a class="anchor-link" href="#!">#</a>
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-dark table-hover table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Active Tables
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr class="table-active">
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td class="table-active">Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Active Tables Dark
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-dark table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr class="table-active">
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td class="table-active">Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Bordered Table
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-bordered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Bordered color Table
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-bordered border-primary table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Tables without borders
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-borderless table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Tables without borders Dark
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-borderless table-dark table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Small tables
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-sm">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Small Tables Dark
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-dark table-sm">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Table group dividers
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody class="table-group-divider">
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Vertical alignment
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table align-middle table-centered">
                              <thead>
                                   <tr>
                                        <th class="w-25" scope="col">Heading 1</th>
                                        <th class="w-25" scope="col">Heading 2</th>
                                        <th class="w-25" scope="col">Heading 3</th>
                                        <th class="w-25" scope="col">Heading 4</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>This cell inherits <code>vertical-align: middle;</code> from the table</td>
                                        <td>This cell inherits <code>vertical-align: middle;</code> from the table</td>
                                        <td>This cell inherits <code>vertical-align: middle;</code> from the table</td>
                                        <td>This here is some placeholder text, intended to take up quite a bit of
                                             vertical space, to demonstrate how the vertical alignment works in the
                                             preceding cells.</td>
                                   </tr>
                                   <tr class="align-bottom">
                                        <td>This cell inherits <code>vertical-align: bottom;</code> from the table row
                                        </td>
                                        <td>This cell inherits <code>vertical-align: bottom;</code> from the table row
                                        </td>
                                        <td>This cell inherits <code>vertical-align: bottom;</code> from the table row
                                        </td>
                                        <td>This here is some placeholder text, intended to take up quite a bit of
                                             vertical space, to demonstrate how the vertical alignment works in the
                                             preceding cells.</td>
                                   </tr>
                                   <tr>
                                        <td>This cell inherits <code>vertical-align: middle;</code> from the table</td>
                                        <td>This cell inherits <code>vertical-align: middle;</code> from the table</td>
                                        <td class="align-top">This cell is aligned to the top.</td>
                                        <td>This here is some placeholder text, intended to take up quite a bit of
                                             vertical space, to demonstrate how the vertical alignment works in the
                                             preceding cells.</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div>
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Nesting Table
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-bordered table-striped table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td colspan="4">
                                             <table class="table mb-0">
                                                  <thead>
                                                       <tr>
                                                            <th scope="col">Header</th>
                                                            <th scope="col">Header</th>
                                                            <th scope="col">Header</th>
                                                       </tr>
                                                  </thead>
                                                  <tbody>
                                                       <tr>
                                                            <td>A</td>
                                                            <td>First</td>
                                                            <td>Last</td>
                                                       </tr>
                                                       <tr>
                                                            <td>B</td>
                                                            <td>First</td>
                                                            <td>Last</td>
                                                       </tr>
                                                       <tr>
                                                            <td>C</td>
                                                            <td>First</td>
                                                            <td>Last</td>
                                                       </tr>
                                                  </tbody>
                                             </table>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Table head Dark
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-centered">
                              <thead class="table-dark">
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Table head
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-centered">
                              <thead class="table-light">
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Table footer
                    </h5>
               </div>
               <div class="card-body">
                    <div class="mt-2 table-responsive">
                         <table class="table table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                              <tfoot>
                                   <tr>
                                        <td>Footer</td>
                                        <td>Footer</td>
                                        <td>Footer</td>
                                        <td>Footer</td>
                                   </tr>
                              </tfoot>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Captions
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-centered">
                              <caption>List of users</caption>
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry the Bird</td>
                                        <td>Simsons</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Captions
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table caption-top table-centered">
                              <caption>List of users</caption>
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Always responsive
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table">
                              <thead>
                                   <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                        <th scope="col">Heading</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>1</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr>
                                        <td>2</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                                   <tr>
                                        <td>3</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                        <td>Cell</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         With avatars
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-hover table-centered">
                              <thead class="table-light">
                                   <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Action</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>
                                             <div class="d-flex align-items-center gap-1">
                                                  <img alt="" class="avatar-sm rounded-circle"
                                                       src="/images/users/avatar-2.jpg" />
                                                  <div class="d-block">
                                                       <h5 class="mb-0"> Tony M. Carter</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>Designer</td>
                                        <td>tonymcarter@jourrapide.com</td>
                                        <td>Member</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="d-flex align-items-center gap-1">
                                                  <img alt="" class="avatar-sm rounded-circle"
                                                       src="/images/users/avatar-1.jpg" />
                                                  <div class="d-block">
                                                       <h5 class="mb-0">James E. Chamb</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>UI/UX Designer</td>
                                        <td>jamesechambliss@teleworm.us</td>
                                        <td>Admin</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="d-flex align-items-center gap-1">
                                                  <img alt="" class="avatar-sm rounded-circle"
                                                       src="/images/users/avatar-4.jpg" />
                                                  <div class="d-block">
                                                       <h5 class="mb-0">Charlotte J. Torres</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>Copywriter</td>
                                        <td>charlotte@jourrapide.com</td>
                                        <td>Member</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr class="table-active">
                                        <td>
                                             <div class="d-flex align-items-center gap-1">
                                                  <img alt="" class="avatar-sm rounded-circle"
                                                       src="/images/users/avatar-6.jpg" />
                                                  <div class="d-block">
                                                       <h5 class="mb-0 d-flex align-items-center gap-1">Mary J.
                                                            Germain<i class="text-success"
                                                                 data-lucide="badge-check"></i></h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>Full Stack</td>
                                        <td>maryjgermain@jourrapide.com</td>
                                        <td>CEO</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="d-flex align-items-center gap-1">
                                                  <img alt="" class="avatar-sm rounded-circle"
                                                       src="/images/users/avatar-7.jpg" />
                                                  <div class="d-block">
                                                       <h5 class="mb-0">Kevin C. Reyes</h5>
                                                  </div>
                                             </div>
                                        </td>
                                        <td>Director of Product</td>
                                        <td>kevincreyes@jourrapide.com</td>
                                        <td>Member</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         With checkboxes
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-striped table-borderless table-centered">
                              <thead class="table-light">
                                   <tr>
                                        <th scope="col">
                                             <div class="form-check">
                                                  <input class="form-check-input" id="flexCheckDefault5" type="checkbox"
                                                       value="" />
                                             </div>
                                        </th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Action</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>
                                             <div class="form-check">
                                                  <input class="form-check-input" id="flexCheckDefault" type="checkbox"
                                                       value="" />
                                             </div>
                                        </td>
                                        <td> Tony M. Carter </td>
                                        <td>Designer</td>
                                        <td>tonymcarter@jourrapide.com</td>
                                        <td>Member</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="form-check">
                                                  <input class="form-check-input" id="flexCheckDefault2" type="checkbox"
                                                       value="" />
                                             </div>
                                        </td>
                                        <td>James E. Chamb</td>
                                        <td>UI/UX Designer</td>
                                        <td>jamesechambliss@teleworm.us</td>
                                        <td>Admin</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="form-check">
                                                  <input class="form-check-input" id="flexCheckDefault3" type="checkbox"
                                                       value="" />
                                             </div>
                                        </td>
                                        <td> Charlotte J. Torres </td>
                                        <td>Copywriter</td>
                                        <td>charlotte@jourrapide.com</td>
                                        <td>Member</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="form-check">
                                                  <input class="form-check-input" id="flexCheckDefault4" type="checkbox"
                                                       value="" />
                                             </div>
                                        </td>
                                        <td>
                                             Mary J. Germain <i class="text-success" data-lucide="badge-check"></i>
                                        </td>
                                        <td>Full Stack</td>
                                        <td>maryjgermain@jourrapide.com</td>
                                        <td>CEO</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                                   <tr>
                                        <td>
                                             <div class="form-check">
                                                  <input class="form-check-input" id="flexCheckDefault5" type="checkbox"
                                                       value="" />
                                             </div>
                                        </td>
                                        <td>Kevin C. Reyes</td>
                                        <td>Director of Product</td>
                                        <td>kevincreyes@jourrapide.com</td>
                                        <td>Member</td>
                                        <td><a class="btn btn-primary btn-sm w-100" href="#!">Edit</a></td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
     <div class="col-lg-12">
          <div class="card">
               <div class="card-header">
                    <h5 class="card-title">
                         Nesting Table
                    </h5>
               </div>
               <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-bordered table-striped table-centered">
                              <thead>
                                   <tr>
                                        <th scope="col">Invoice Number</th>
                                        <th scope="col">Invoice Amount</th>
                                        <th scope="col">Confirmation by the client</th>
                                        <th scope="col">Planned payment date</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <tr>
                                        <td>F-011221/21</td>
                                        <td>$ 879.500</td>
                                        <td>11/05/2023</td>
                                        <td>12/05/2023</td>
                                   </tr>
                                   <tr>
                                        <td colspan="4">
                                             <table class="table mb-0">
                                                  <thead>
                                                       <tr>
                                                            <th scope="col">ERP number</th>
                                                            <th scope="col">Carrier legal entity</th>
                                                            <th scope="col">Responsible logistician</th>
                                                            <th scope="col">Status</th>
                                                       </tr>
                                                  </thead>
                                                  <tbody>
                                                       <tr>
                                                            <td>3-128-3</td>
                                                            <td>ToBrookfield Asset Management</td>
                                                            <td>
                                                                 <div class="d-flex align-items-center gap-1">
                                                                      <img alt="" class="avatar-sm rounded-circle"
                                                                           src="/images/users/avatar-7.jpg" />
                                                                      <div class="d-block">
                                                                           <h5 class="mb-0">Kevin C. Reyes</h5>
                                                                      </div>
                                                                 </div>
                                                            </td>
                                                            <td><span
                                                                      class="badge bg-success-subtle text-success py-1 px-2">Verified</span>
                                                            </td>
                                                       </tr>
                                                       <tr>
                                                            <td>3-128-2</td>
                                                            <td>Brookfield Asset Management</td>
                                                            <td>
                                                                 <div class="d-flex align-items-center gap-1">
                                                                      <img alt="" class="avatar-sm rounded-circle"
                                                                           src="/images/users/avatar-6.jpg" />
                                                                      <div class="d-block">
                                                                           <h5 class="mb-0">Mary J. Germain</h5>
                                                                      </div>
                                                                 </div>
                                                            </td>
                                                            <td><span
                                                                      class="badge bg-warning-subtle text-warning py-1 px-2">Pending</span>
                                                            </td>
                                                       </tr>
                                                       <tr>
                                                            <td>3-128-1</td>
                                                            <td>Westfield Asset Management</td>
                                                            <td>
                                                                 <div class="d-flex align-items-center gap-1">
                                                                      <img alt="" class="avatar-sm rounded-circle"
                                                                           src="/images/users/avatar-8.jpg" />
                                                                      <div class="d-block">
                                                                           <h5 class="mb-0">Charlotte J. Torres
                                                                           </h5>
                                                                      </div>
                                                                 </div>
                                                            </td>
                                                            <td><span
                                                                      class="badge bg-danger-subtle text-danger py-1 px-2">Rejected</span>
                                                            </td>
                                                       </tr>
                                                  </tbody>
                                             </table>
                                        </td>
                                   </tr>
                                   <tr>
                                        <td>F-011221/19</td>
                                        <td>$ 93.250</td>
                                        <td>9/05/2023</td>
                                        <td>10/05/2023</td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>
               </div>
          </div> <!-- end card body -->
     </div>
</div> <!-- end row -->
@endsection