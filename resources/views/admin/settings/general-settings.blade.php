@extends('layouts.app')

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">General Settings</h4>
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="number" name="duty_time" value="{{ old('duty_time', setting('duty_time')) }}" label="Duty Time(in hrs)" placeholder="Enter Duty Time" min=0 step="any"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="number" name="yearly_leaves" value="{{ old('yearly_leaves', setting('yearly_leaves')) }}" label="Yearly Leaves" placeholder="Enter Leaves" min=0 step="any"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="number" name="vacation_leaves" value="{{ old('vacation_leaves', setting('vacation_leaves')) }}" label="Vacation Leaves" placeholder="Enter Leaves" min=0 step="any"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <x-form-input type="number" name="maternity_leaves" value="{{ old('maternity_leaves', setting('maternity_leaves')) }}" label="Maternity Leaves" placeholder="Enter Leaves" min=0 step="any"/>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary w-md">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection