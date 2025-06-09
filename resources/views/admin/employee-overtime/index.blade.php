@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Employee Overtime</h4>

                        @can('create employee overtime')
                            <div class="page-title-right">
                                <a href="{{ route('employee-overtime.create') }}" class="btn btn-primary">Add New Employee
                                    Overtime</a>
                            </div>
                        @endcan

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
                                        <th>Employee Name</th>
                                        <th>Rate</th>
                                        <th>Hours</th>
                                        <th>Work Date</th>
                                        <th>Created Date</th>
                                        <th>Actual Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($overtimes as $index => $overtime)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $overtime->employee->first_name ?? '' }}
                                                {{ $overtime->employee->surname ?? '' }}</td>
                                            <td>{{ number_format($overtime->rate, 2) }}</td>
                                            <td>{{ $overtime->total_hours }}</td>
                                            <td>{{ $overtime->work_date ? \Carbon\Carbon::parse($overtime->created_at)->format('Y-m-d') : 'N/A' }}
                                            </td>
                                            <td>{{ $overtime->created_at ? \Carbon\Carbon::parse($overtime->created_at)->format('Y-m-d') : 'N/A' }}
                                            </td>
                                            <td>{{ $overtime->actual_date ? \Carbon\Carbon::parse($overtime->actual_date)->format('Y-m-d') : 'N/A' }}
                                            </td>
                                            <td>
                                                @can('edit employee overtime')
                                                    <a href="{{ route('employee-overtime.edit', [$overtime->employee_id, $overtime->id]) }}"
                                                        class="btn btn-sm btn-info">Edit</a>
                                                @endcan
                                                @can('delete employee overtime')
                                                    <button type="button" data-source="Employee Overtime"
                                                        data-endpoint="{{ route('employee-overtime.destroy', [$overtime->employee_id, $overtime->id]) }}"
                                                        class="delete-btn btn btn-danger btn-sm">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endcan
                                            </td>
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
