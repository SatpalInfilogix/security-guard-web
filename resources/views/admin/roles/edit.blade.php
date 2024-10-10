@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Role Edit</h4>
                        <div class="page-title-right">
                            <a href="{{ route('roles-and-permissions.role-list') }}" class="btn btn-primary">Back to roles</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="session('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('roles-and-permissions.update',$role->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <x-form-input name="role" value="{{ $role->name }}" label="Role Name" placeholder="Enter role name"  required="true" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6 mb-2">
                                        <button type="submit" class="btn btn-primary w-md">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
@endsection