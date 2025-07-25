@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Edit Employee Deduction</h4>

                        <div class="page-title-right">
                            <a href="{{ route('employee-deductions.index', [
                                'page' => request()->input('page', 1),
                                'search_name' => request()->input('search_name'),
                                'search_type' => request()->input('search_type'),
                                'search_document_date' => request()->input('search_document_date'),
                                'search_period_date' => request()->input('search_period_date'),
                            ]) }}"
                                class="btn btn-primary">
                                <i class="bx bx-arrow-back"></i> Back to Deduction
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
                            <form action="{{ route('employee-deductions.update', $deduction->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('admin.employee-deductions.form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
