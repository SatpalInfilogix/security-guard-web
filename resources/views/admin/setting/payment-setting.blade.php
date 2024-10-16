@extends('layouts.app')

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Payment Settings</h4>
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
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        @php
                                            $stripeApiKey = setting('stripe_api_key') ? Crypt::decryptString(setting('stripe_api_key')) : '';
                                        @endphp
                                        <x-form-input name="stripe_api_key" value="{{ old('stripe_api_key', $stripeApiKey) }}" label="Api Key" placeholder="Enter your Api Key"/>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        @php
                                            $stripeSecretKey = setting('stripe_secret_key') ? Crypt::decryptString(setting('stripe_secret_key')) : '';
                                        @endphp
                                        <x-form-input name="stripe_secret_key" value="{{ old('stripe_secret_key', $stripeSecretKey) }}" label="Secret Key" placeholder="Enter your Secret Key"/>
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
@endsection