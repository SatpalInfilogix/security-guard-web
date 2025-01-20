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
                                        <x-form-input name="surname" value="{{ $attendance->user->surname }}" label="Surename" placeholder="Enter your Surename" readonly/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="text" class="date-picker-punch-in" name="punch_in" label="Punch In" value="{{ old('punch_in', isset($attendance->in_time) ? \Carbon\Carbon::parse($attendance->in_time)->format('d-m-Y H:i:s') : '') }}" placeholder="Enter your Punch In" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="in_location" class="form-label">In Location</label>
                                    <textarea name="in_loaction" class="form-control" rows="4" cols="50" readonly>{{$attendance->in_location}}</textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="in_location" class="form-label">Out Location</label>
                                    <textarea name="out_loaction" class="form-control" rows="4" cols="50" readonly>{{$attendance->out_location}}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="text" class="date-picker-punch-out" name="punch_out" label="Punch Out" value="{{ old('punch_out', isset($attendance->out_time) ? \Carbon\Carbon::parse($attendance->out_time)->format('d-m-Y H:i:s') : '') }}" placeholder="Enter your Punch Out" />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="punchInImage" class="form-label">Punch In Image</label>
                                        <img id="punchInImage" src="{{ asset($attendance->in_image) }}" height="500" width="150" alt="Punch In Image" class="img-thumbnail">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="punchOutImage" class="form-label">Punch Out Image</label>
                                        <img id="punchOutImage" src="{{ asset($attendance->out_image) }}" height="500" width="150" alt="Punch Out Image" class="img-thumbnail">
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