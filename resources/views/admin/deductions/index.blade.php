@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">NST Deduction</h4>

                        <div class="page-title-right">
                            <a href="{{ route('export.deductions') }}" class="btn btn-primary"><i class="bx bx-export"></i>Deduction Export</a>
                            <a href="{{ route('deductions.create') }}" class="btn btn-primary">Add New Deduction</a>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <table id="datatable" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee No</th>
                                        <th>Employee Name</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>No Of Payroll</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($deductions as $key => $deduction)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $deduction->user->user_code }}</td>
                                        <td>{{ $deduction->user->first_name }}</td>
                                        <td>{{ $deduction->type }}</td>
                                        <td>{{ $deduction->amount }}</td>
                                        <td>{{ $deduction->no_of_payroll }}</td>
                                        <td>@if($deduction->start_date){{ \Carbon\Carbon::parse($deduction->start_date)->format('d-m-Y') }}@else N/A @endif</td>
                                        <td>@if($deduction->end_date){{ \Carbon\Carbon::parse($deduction->end_date)->format('d-m-Y') }}@else N/A @endif</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>
@endsection