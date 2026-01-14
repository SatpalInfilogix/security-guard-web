@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Profile</h4>
                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row">
                <div class="col-xl-12">
                    <x-success-message :message="session('success')" />
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('profile.update', Auth::id()) }}"
                                method="post"enctype="multipart/form-data" id='update-profile'>
                                @method('patch')
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <x-form-input name="first_name" value="{{ $user->first_name }}"
                                                label="First Name" placeholder="Enter your First Name" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <x-form-input name="last_name" value="{{ $user->last_name }}" label="Last Name"
                                                placeholder="Enter your Last Name" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <x-form-input name="email" value="{{ $user->email }}" label="Email"
                                                placeholder="Enter your Email" readonly />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <x-form-input type="number" name="phone_no" label="Phone"
                                                value="{{ $user->phone_number }}" placeholder="Enter your Phone Number" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>Image</label>
                                            <input type="file" class="form-control" name="profile_image" id="imageInput"
                                                accept="image/*">
                                            <img id="imagePreview" src="#" alt="Image Preview">
                                            @if ($user->profile_picture)
                                                <img src="{{ asset($user->profile_picture) }}" width="150" height="150"
                                                    id="image-preview">
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <x-form-input type="password" name="password" label="Password"
                                                placeholder="Enter new password" />
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#imageInput').on('change', function(event) {
                var file = event.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result).show();
                        $('#image-preview').hide();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').hide();
                }
            });
        });
    </script>
@endsection
