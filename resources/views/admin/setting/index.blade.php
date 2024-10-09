@extends('layouts.app')

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Settings</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-xl-12">
                <x-error-message :message="$errors->first('message')" />
                <x-success-message :message="session('success')" />

                <div class="card">
                    <div class="card-body">
                        <form class="form-horizontal" action="{{ route('settings.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <x-form-input name="site_name" value="{{ old('site_name', App\Helpers\SettingHelper::setting('site_name')) }}" label="Site Name" placeholder="Enter your Site Name"/>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Logo</label>
                                        <input type="file" name="logo" id="add-logo" class="form-control">
                                        @if (App\Helpers\SettingHelper::setting('logo'))
                                            <img src="{{ asset(App\Helpers\SettingHelper::setting('logo')) }}" id="preview-Img"
                                                class="img-preview" style="width:50px;">
                                        @else
                                            <img src="" id="preview-Img" style="width:50px;" name="image" hidden>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary w-md">Submit</button>
                            </div>
                        </form>
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
        </div>
        <!-- end row -->
    </div>
</div>
<script>
    $('#add-logo').change(function() {
        var input = this;
        if (input.files && input.files[0]) {
            $('#preview-Img').prop('hidden', false);
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-Img').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    });
</script>
@endsection