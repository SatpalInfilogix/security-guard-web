@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Edit Leave</h4>

                        <div class="page-title-right">
                            <a href="{{ route('employee-leaves.index', [
                                'page' => request()->input('page', 1),
                                'leave_status' => request()->input('leave_status'),
                                'month' => request()->input('month'),
                                'year' => request()->input('year'),
                            ]) }}"
                                class="btn btn-primary">
                                <i class="bx bx-arrow-back"></i> Back to Leave Listing
                            </a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <form
                                action="{{ route('employee-leaves.update', [$leave->employee_id, $leave->batch_id]) }}"
                                method="post">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="startDate" value="{{ $leave->start_date }}">
                                @include('admin.employee-leaves.form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
