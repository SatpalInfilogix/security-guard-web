@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Payroll</h4>

                        <div class="page-title-right">
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
                            <table id="datatable" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Guard</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Normal Hours</th>
                                    <th>Overtime Hours</th>
                                    <th>Public holiday Hours</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($payrolls as $key => $payroll)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $payroll->user->first_name }}</td>
                                    <td>{{ $payroll->start_date}}</td>
                                    <td>{{ $payroll->end_date}}</td>
                                    <td>{{ $payroll->normal_hours }}</td>
                                    <td>{{ $payroll->overtime }}</td>
                                    <td>{{ $payroll->public_holidays }}</td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>
@endsection