@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Payroll Detail</h4>

                        <div class="page-title-right">
                            <a href="{{ route('payrolls.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back to Payroll</a>
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
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Employee Name</strong></div>
                                <div class="col-md-3">{{ $payroll->user->first_name }}</div>
                                <div class="col-md-3"><strong>NIS No</strong></div>
                                <div class="col-md-3">{{ $payroll->user->guardAdditionalInformation->nis }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Department</strong></div>
                                <div class="col-md-3">N/A</div>
                                <div class="col-md-3"><strong>Employee TRN</strong></div>
                                <div class="col-md-3">{{ $payroll->user->guardAdditionalInformation->trn }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Category</strong></div>
                                <div class="col-md-3">N/A</div>
                                <div class="col-md-3"><strong>Payroll Period</strong></div>
                                <div class="col-md-3">{{$payroll->start_date}} to {{$payroll->end_date}}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Payroll No</strong></div>
                                <div class="col-md-3">1</div>
                                <div class="col-md-3"><strong>Date of Processing</strong></div>
                                <div class="col-md-3">N/A</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <table id="payroll-list" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Guard</th>
                                    <th>Date</th>
                                    <th>Normal Hours</th>
                                    <th>Overtime Hours</th>
                                    <th>Public Holiday Hours</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @foreach($payrollDetails as $key => $payrollDetail)
                                        <tr>
                                            <td>{{ ++$key }}
                                            <td>{{ $payrollDetail->user->first_name }}</td>
                                            <td>{{ $payrollDetail->date }}</td>
                                            <td>{{ $payrollDetail->normal_hours }}</td>
                                            <td>{{ $payrollDetail->overtime }}</td>
                                            <td>{{ $payrollDetail->public_holiday }}</td>
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

@endsection