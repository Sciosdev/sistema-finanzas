@extends('layouts.vertical', ['title' => 'Checkbox Radio'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Basic Example</h5>
                <div>
                    <select class="form-control" data-choices="" id="choices-single-default" name="choices-single-default">
                        <option value="">This is a placeholder</option>
                        <option value="Choice 1">Choice 1</option>
                        <option value="Choice 2">Choice 2</option>
                        <option value="Choice 3">Choice 3</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Option Groups Example</h5>
                <div>
                    <label class="form-label text-muted" for="choices-single-groups">Option Groups</label>
                    <select class="form-control" data-choices="" data-choices-groups="" data-placeholder="Select City" id="choices-single-groups" name="choices-single-groups">
                        <option value="">Choose a city</option>
                        <optgroup label="UK">
                            <option value="London">London</option>
                            <option value="Manchester">Manchester</option>
                            <option value="Liverpool">Liverpool</option>
                        </optgroup>
                        <optgroup label="FR">
                            <option value="Paris">Paris</option>
                            <option value="Lyon">Lyon</option>
                            <option value="Marseille">Marseille</option>
                        </optgroup>
                        <optgroup disabled="" label="DE">
                            <option value="Hamburg">Hamburg</option>
                            <option value="Munich">Munich</option>
                            <option value="Berlin">Berlin</option>
                        </optgroup>
                        <optgroup label="US">
                            <option value="New York">New York</option>
                            <option disabled="" value="Washington">Washington</option>
                            <option value="Michigan">Michigan</option>
                        </optgroup>
                        <optgroup label="SP">
                            <option value="Madrid">Madrid</option>
                            <option value="Barcelona">Barcelona</option>
                            <option value="Malaga">Malaga</option>
                        </optgroup>
                        <optgroup label="CA">
                            <option value="Montreal">Montreal</option>
                            <option value="Toronto">Toronto</option>
                            <option value="Vancouver">Vancouver</option>
                        </optgroup>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Options added via config with no search</h5>
                <div>
                    <select class="form-control" data-choices="" data-choices-removeitem="" data-choices-search-false="" id="choices-single-no-search" name="choices-single-no-search">
                        <option value="Zero">Zero</option>
                        <option value="One">One</option>
                        <option value="Two">Two</option>
                        <option value="Three">Three</option>
                        <option value="Four">Four</option>
                        <option value="Five">Five</option>
                        <option value="Six">Six</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Options added via config with no sorting</h5>
                <div>
                    <select class="form-control" data-choices="" data-choices-sorting-false="" id="choices-single-no-sorting" name="choices-single-no-sorting">
                        <option value="Madrid">Madrid</option>
                        <option value="Toronto">Toronto</option>
                        <option value="Vancouver">Vancouver</option>
                        <option value="London">London</option>
                        <option value="Manchester">Manchester</option>
                        <option value="Liverpool">Liverpool</option>
                        <option value="Paris">Paris</option>
                        <option value="Malaga">Malaga</option>
                        <option disabled="" value="Washington">Washington</option>
                        <option value="Lyon">Lyon</option>
                        <option value="Marseille">Marseille</option>
                        <option value="Hamburg">Hamburg</option>
                        <option value="Munich">Munich</option>
                        <option value="Barcelona">Barcelona</option>
                        <option value="Berlin">Berlin</option>
                        <option value="Montreal">Montreal</option>
                        <option value="New York">New York</option>
                        <option value="Michigan">Michigan</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Multiple select input</h5>
                <div>
                    <select class="form-control" data-choices="" id="choices-multiple-default" multiple="" name="choices-multiple-default">
                        <option selected="" value="Choice 1">Choice 1</option>
                        <option value="Choice 2">Choice 2</option>
                        <option value="Choice 3">Choice 3</option>
                        <option disabled="" value="Choice 4">Choice 4</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Multiple select With remove button input</h5>
                <div>
                    <select class="form-control" data-choices="" data-choices-removeitem="" id="choices-multiple-remove-button" multiple="" name="choices-multiple-remove-button">
                        <option selected="" value="Choice 1">Choice 1</option>
                        <option value="Choice 2">Choice 2</option>
                        <option value="Choice 3">Choice 3</option>
                        <option value="Choice 4">Choice 4</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Multiple select With Option groups</h5>
                <div>
                    <label class="form-label text-muted" for="choices-multiple-groups">Option groups</label>
                    <select class="form-control" data-choices="" data-choices-multiple-groups="true" id="choices-multiple-groups" multiple="" name="choices-multiple-groups">
                        <option value="">Choose a city</option>
                        <optgroup label="UK">
                            <option value="London">London</option>
                            <option value="Manchester">Manchester</option>
                            <option value="Liverpool">Liverpool</option>
                        </optgroup>
                        <optgroup label="FR">
                            <option value="Paris">Paris</option>
                            <option value="Lyon">Lyon</option>
                            <option value="Marseille">Marseille</option>
                        </optgroup>
                        <optgroup disabled="" label="DE">
                            <option value="Hamburg">Hamburg</option>
                            <option value="Munich">Munich</option>
                            <option value="Berlin">Berlin</option>
                        </optgroup>
                        <optgroup label="US">
                            <option value="New York">New York</option>
                            <option disabled="" value="Washington">Washington</option>
                            <option value="Michigan">Michigan</option>
                        </optgroup>
                        <optgroup label="SP">
                            <option value="Madrid">Madrid</option>
                            <option value="Barcelona">Barcelona</option>
                            <option value="Malaga">Malaga</option>
                        </optgroup>
                        <optgroup label="CA">
                            <option value="Montreal">Montreal</option>
                            <option value="Toronto">Toronto</option>
                            <option value="Vancouver">Vancouver</option>
                        </optgroup>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Text inputs</h5>
                <div>
                    <label class="form-label text-muted" for="choices-text-remove-button">Set limit values with remove button</label>
                    <input class="form-control" data-choices="" data-choices-limit="3" data-choices-removeitem="" id="choices-text-remove-button" type="text" value="Task-1" />
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Text inputs in Unique values only, no pasting</h5>
                <div>
                    <label class="form-label text-muted" for="choices-text-unique-values">Unique values only, no pasting</label>
                    <input class="form-control" data-choices="" data-choices-text-unique-true="" id="choices-text-unique-values" type="text" value="Project-A, Project-B" />
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="card-title mb-4">Disabled Text Inputs</h5>
                <div>
                    <label class="form-label text-muted" for="choices-text-disabled">Disabled</label>
                    <input class="form-control" data-choices="" data-choices-text-disabled-true="" id="choices-text-disabled" type="text" value="josh@joshuajohnson.co.uk, joe@bloggs.co.uk" />
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
</div>
@endsection