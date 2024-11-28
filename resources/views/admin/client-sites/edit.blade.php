@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Update Client Site</h4>

                        <div class="page-title-right">
                            <a href="{{ route('client-sites.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back to Client Sites</a>
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
                            <form action="{{ route('client-sites.update', $clientSite->id) }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('admin.client-sites.form')
                            </form>    
                        </div>    
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- container-fluid -->
    </div>
@endsection