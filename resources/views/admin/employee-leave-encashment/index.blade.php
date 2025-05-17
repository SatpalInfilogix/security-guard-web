@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Employee Overtime</h4>

                        <div class="page-title-right">
                            <a href="{{ route('employee-leave-encashment.create') }}" class="btn btn-primary">Add New Leave
                                Encashment</a>
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
                                        <th>Employee Name</th>
                                        <th>Pending Leaves</th>
                                        <th>Leaves Encashment</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($encashments as $index => $encashment)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $encashment->employee->first_name ?? '' }}
                                                {{ $encashment->employee->surname ?? '' }}</td>
                                            <td>{{ $encashment->pending_leaves }}</td>
                                            <td>{{ $encashment->encash_leaves }}</td>
                                            <td>{{ $encashment->created_at->format('Y-m-d') }}</td>
                                            <td>
                                                <a href="{{ route('employee-leave-encashment.edit', $encashment->id) }}"
                                                    class="btn btn-sm btn-info">Edit</a>
                                                <button type="button" data-source="Employee Overtime"
                                                    data-endpoint="{{ route('employee-leave-encashment.destroy', $encashment->id) }}"
                                                    class="delete-btn btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
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
