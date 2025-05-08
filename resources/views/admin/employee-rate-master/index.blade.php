@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Employee Rate Master</h4>

                        <div class="page-title-right">
                            @if(Auth::user()->can('create employee rate master'))
                            <a href="{{ route('employee-rate-master.create') }}" class="btn btn-primary">Add New Employee Rate Master</a>
                            @endif
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
                                    <th>Employee No</th>
                                    <th>Employee Name</th>
                                    <th>Gross Salary</th>
                                    @canany(['edit employee rate master', 'delete employee rate master'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($employeeRateMasters as $key => $rateMaster)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $rateMaster->user->user_code ?? 'N/A' }}</td>
                                    <td>{{ $rateMaster->user->first_name ?? 'N/A'}} {{ $rateMaster->user->surname ?? 'N/A'}}</td>
                                    <td>${{formatAmount($rateMaster->gross_salary ?? '')}}</td>
                                    @canany(['edit employee rate master', 'delete employee rate master'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit employee rate master'))
                                        <a href="{{ route('employee-rate-master.edit', $rateMaster->id)}}" class="btn btn-primary waves-effect waves-light btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete employee rate master'))
                                        <button data-source="Employee Rate Master" data-endpoint="{{ route('employee-rate-master.destroy', $rateMaster->id)}}"
                                            class="delete-btn btn btn-danger waves-effect waves-light btn-sm edit">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @endif
                                    </td>
                                    @endcanany
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