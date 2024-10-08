@extends('layouts.app')

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Edit Attendance</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-xl-12">
                <x-success-message :message="session('success')" />
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('attendance.update', $attendance->id) }}" method="post"enctype="multipart/form-data" id='update-profile'>
                            @method('patch')
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input name="first_name" value="{{ $attendance->user->first_name }}" label="First Name" placeholder="Enter your First Name" readonly/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input name="last_name" value="{{ $attendance->user->middle_name }}" label="Middle Name" placeholder="Enter your Middle Name" readonly/>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input name="last_name" value="{{ $attendance->user->surname }}" label="Surename" placeholder="Enter your Surename" readonly/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="text" class="date-picker-punch-in" name="punch_in" label="Punch In" value="{{ old('punch_in', $attendance->in_time) }}" placeholder="Enter your Punch In" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="text" class="date-picker-punch-out" name="punch_out" label="Punch Out" value="{{ old('punch_out', $attendance->out_time) }}" placeholder="Enter your Punch Out" />
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
<x-include-plugins :plugins="['datePicker']"></x-include-plugins>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
   $(document).ready(function() {
        $('#imageInput').on('change', function(event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result).show();
                };
                reader.readAsDataURL(file);
            } else {
                $('#imagePreview').hide();
            }
        });
    });
</script>

@endsection