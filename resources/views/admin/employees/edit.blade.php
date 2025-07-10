@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Edit Employee</h4>

                        <div class="page-title-right">
                            <a href="{{ route('employees.index', [
                                'page' => request()->input('page', 1),
                                'search_emp_code' => request()->input('search_emp_code'),
                                'search_name' => request()->input('search_name'),
                                'search_email' => request()->input('search_email'),
                                'search_phone' => request()->input('search_phone'),
                                'status' => request()->input('status'),
                            ]) }}"
                                class="btn btn-primary">
                                <i class="bx bx-arrow-back"></i> Back to Employee
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('employees.update', $user->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('admin.employees.form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
