@extends('layouts.vertical', ['title' => 'Editors'])

@section('css')
    @vite(['node_modules/quill/dist/quill.core.css','node_modules/quill/dist/quill.snow.css','node_modules/quill/dist/quill.bubble.css'])
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Snow Editor
                </h5>
                <div id="snow-editor" style="height: 300px;">
                    <h3><span class="ql-size-large">Hello World!</span></h3>
                    <p><br /></p>
                    <h3>This is a simple editable area.</h3>
                    <p><br /></p>
                    <ul>
                        <li>Select a text to reveal the toolbar.</li>
                        <li>Edit rich document on-the-fly, so elastic!</li>
                    </ul>
                    <p><br /></p>
                    <p>End of simple area</p>
                </div>
            </div>
            <div class="col-lg-12">
                <h5 class="card-title mb-4">
                    Bubble Editor
                </h5>
                <!-- Bubble Editors -->
                <div id="bubble-editor" style="height: 300px;">
                    <h3><span class="ql-size-large">Hello World!</span></h3>
                    <p><br /></p>
                    <h3>This is a simple editable area.</h3>
                    <p><br /></p>
                    <ul>
                        <li>Select a text to reveal the toolbar.</li>
                        <li>Edit rich document on-the-fly, so elastic!</li>
                    </ul>
                    <p><br /></p>
                    <p>End of simple area</p>
                </div>
            </div>
        </div> <!-- end row -->
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/components/form-quilljs.js'])
@endsection