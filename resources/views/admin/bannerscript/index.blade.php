@extends('layouts.admin')

@section('title', 'Admins')

<!-- @push('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/datatables.css') }}">
@endpush -->

@push('page-styles')
@endpush

@section('body')
 <style>
    .bannerscript{
        margin:0 !important;
    }
    .cke_contents{
        height: 600px !important;
    }
</style>
<!-- <script src="https://cdn.ckeditor.com/4.20.1/full/ckeditor.js"></script> -->
<div class="row bannerscript">
    <div class="col-md-12 mt-5">
        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('admin.store-banner-script')}}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="bannerCode"><strong>Banner:</strong></label>
                        <p>*Disclaimer the image size should be 1920 x 320 pixels</p>
                        <input type="hidden" name="id" value="{{ isset($descriprtion->id) ? $descriprtion->id : '' }}">
                        <div class="mb-3">
                            @if(isset($descriprtion->description))
                                <img src="{{ asset($descriprtion->description) }}" alt="Image" style="max-width: 300px; max-height: 300px;">
                                <a href="{{ route('admin.removeBanner', $descriprtion->id) }}"><button type="button" class="btn btn-danger">Delete</button></a>
                            @else
                                <div></div>
                            @endif
                        </div>
                        <!-- <div class="text-center">
                        </div> -->
                        <div class="mb-3">
                            <label for="imageInput" class="form-label">Upload Image:</label>
                            <input type="file" name="image" id="imageInput" onchange="previewImage(event)" class="form-control" accept="image/jpeg, image/png, image/jpg, image/gif">
                        </div>
                        <div class="mb-3">
                            <img src="" id="imagePreview" alt="Image Preview" style="display: none; max-width: 300px; max-height: 300px;">
                        </div>
                        <!-- Add other form fields as needed -->
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-success btn-lg">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script> 
    function previewImage(event) {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imagePreview = document.getElementById("imagePreview");
                imagePreview.src = e.target.result;
                imagePreview.style.display = "block";
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>


<!-- <script>
    CKEDITOR.replace('editor1');
</script> -->
@endsection

<!-- @push('vendor-scripts')
    <script src="{{ asset('assets/js/datatable/datatables/jquery.dataTables.min.js') }}"></script>
@endpush -->

@push('page-scripts')

@endpush
