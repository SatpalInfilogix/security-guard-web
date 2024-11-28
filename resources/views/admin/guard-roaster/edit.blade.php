@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Update Guard Roaster</h4>

                        <div class="page-title-right">
                            <a href="{{ route('guard-roasters.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back To Guard Roasters</a>
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
                            <form action="{{ route('guard-roasters.update', $guardRoaster->id) }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('admin.guard-roaster.form')
                            </form>    
                        </div>    
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- container-fluid -->
    </div>
@endsection