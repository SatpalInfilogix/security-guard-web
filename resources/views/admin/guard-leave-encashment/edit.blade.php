@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Edit Guard Leave Encashment</h4>

                        <div class="page-title-right">
                            <a href="{{ route('guard-leave-encashment.index') }}" class="btn btn-primary">
                                <i class="bx bx-arrow-back"></i> Back to leave encashment
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
                            <form action="{{ route('guard-leave-encashment.update', $encashment->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('admin.guard-leave-encashment.form', [
                                    'guardEncashment' => $encashment,
                                ])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
