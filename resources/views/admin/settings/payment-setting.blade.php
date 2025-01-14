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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        @php
                                            $stripeApiKey = setting('stripe_api_key') ? Crypt::decryptString(setting('stripe_api_key')) : '';
                                        @endphp
                                        <x-form-input name="stripe_api_key" value="{{ old('stripe_api_key', $stripeApiKey) }}" label="Api Key" placeholder="Enter your Api Key"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        @php
                                            $stripeSecretKey = setting('stripe_secret_key') ? Crypt::decryptString(setting('stripe_secret_key')) : '';
                                        @endphp
                                        <x-form-input name="stripe_secret_key" value="{{ old('stripe_secret_key', $stripeSecretKey) }}" label="Secret Key" placeholder="Enter your Secret Key"/>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $branchName1 = setting('branch_name1') ?setting('branch_name1') : '';
                                        @endphp
                                        <x-form-input name="branch_name1" value="{{ old('branch_name1', $branchName1) }}" label="Branch Name" placeholder="Enter your Branch Name"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $branch1 = setting('branch1') ? setting('branch1') : '';
                                        @endphp
                                        <x-form-input name="branch1" value="{{ old('branch1', $branch1) }}" label="Branch" placeholder="Enter your Branch"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $type1 = setting('type1') ? setting('type1') : '';
                                        @endphp
                                        
                                        <label for="type1" class="form-label">Type</label>
                                        <select name="type1" id="type1" class="form-control">
                                            <option value="" selected disabled>Select Type</option>
                                            <option value="Saving" {{ old('type1', $type1) == 'Saving' ? 'selected' : '' }}>Saving</option>
                                            <option value="Current" {{ old('type1', $type1) == 'Current' ? 'selected' : '' }}>Current</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $accountNumber1 = setting('account_number1') ? setting('account_number1') : '';
                                        @endphp
                                        <x-form-input name="account_number1" value="{{ old('account_number1', $accountNumber1) }}" label="Account Number" placeholder="Enter your Account Number"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $branchName2 = setting('branch_name2') ? setting('branch_name2') : '';
                                        @endphp
                                        <x-form-input name="branch_name2" value="{{ old('branch_name2', $branchName2) }}" label="Branch Name1" placeholder="Enter your Branch Name1"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $branch2 = setting('branch2') ? setting('branch2') : '';
                                        @endphp
                                        <x-form-input name="branch2" value="{{ old('branch2', $branch2) }}" label="Branch1" placeholder="Enter your Branch1"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $type2 = setting('type2') ? setting('type2') : '';
                                        @endphp
                                        
                                        <label for="type2" class="form-label">Type1</label>
                                        <select name="type2" id="type1" class="form-control">
                                            <option value="" selected disabled>Select Type</option>
                                            <option value="Saving" {{ old('type2', $type2) == 'Saving' ? 'selected' : '' }}>Saving</option>
                                            <option value="Current" {{ old('type2', $type2) == 'Current' ? 'selected' : '' }}>Current</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $accountNumber2 = setting('account_number2') ? setting('account_number2') : '';
                                        @endphp
                                        <x-form-input name="account_number2" value="{{ old('account_number2', $accountNumber2) }}" label="Account Number1" placeholder="Enter your Account Number1"/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $branchName3 = setting('branch_name3') ? setting('branch_name3') : '';
                                        @endphp
                                        <x-form-input name="branch_name3" value="{{ old('branch_name3', $branchName3) }}" label="Branch Name2" placeholder="Enter your Branch Name2"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $branch3 = setting('branch3') ? setting('branch1') : '';
                                        @endphp
                                        <x-form-input name="branch3" value="{{ old('branch3', $branch3) }}" label="Branch2" placeholder="Enter your Branch2"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $type3 = setting('type3') ? setting('type3') : '';
                                        @endphp
                                        
                                        <label for="type3" class="form-label">Type2</label>
                                        <select name="type3" id="type3" class="form-control">
                                            <option value="" selected disabled>Select Type</option>
                                            <option value="Saving" {{ old('type3', $type3) == 'Saving' ? 'selected' : '' }}>Saving</option>
                                            <option value="Current" {{ old('type3', $type3) == 'Current' ? 'selected' : '' }}>Current</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        @php
                                            $accountNumber3 = setting('account_number3') ? setting('account_number3') : '';
                                        @endphp
                                        <x-form-input name="account_number3" value="{{ old('account_number3', $accountNumber3) }}" label="Account Number2" placeholder="Enter your Account Number2"/>
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