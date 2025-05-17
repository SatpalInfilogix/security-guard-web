@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Create a guard leave encashment</h4>

                        <div class="page-title-right">
                            <a href="{{ route('guard-leave-encashment.index') }}" class="btn btn-primary">
                                <i class="bx bx-arrow-back"></i> Back to guard leave encashment
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
                            <form action="{{ route('guard-leave-encashment.store') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                @include('admin.guard-leave-encashment.form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
