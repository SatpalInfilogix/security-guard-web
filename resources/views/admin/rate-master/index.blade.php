@extends('layouts.app')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Rate Master</h4>

                    <div class="page-title-right">
                        @if(Auth::user()->can('create rate master'))
                        <a href="{{ route('rate-master.create') }}" class="btn btn-primary">Add New Rate Master</a>
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
                                    <th>Guard Type</th>
                                    <th>Gross Hourly Rate</th>
                                    <th>Normal Rate</th>
                                    <th>Overtime Rate</th>
                                    <th>Holiday Rate</th>
                                    @canany(['edit rate master', 'delete rate master'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($rateMasters as $key => $rateMaster)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $rateMaster->guard_type }}</td>
                                    <td>${{ formatAmount($rateMaster->gross_hourly_rate)}}</td>
                                    <td>${{ formatAmount($rateMaster->gross_hourly_rate)}}</td>
                                    <td>${{ formatAmount($rateMaster->overtime_rate)}}</td>
                                    <td>${{ formatAmount($rateMaster->holiday_rate)}}</td>
                                    @canany(['edit rate master', 'delete rate master'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit rate master'))
                                        <a href="{{ route('rate-master.edit', $rateMaster->id)}}" class="btn btn-primary waves-effect waves-light btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete rate master'))
                                        <button data-source="Rate Master" data-endpoint="{{ route('rate-master.destroy', $rateMaster->id)}}" class="delete-btn btn btn-danger waves-effect waves-light btn-sm edit">
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
            </div> <!-- end col -->
        </div> <!-- end row -->

    </div> <!-- container-fluid -->
</div>
<x-include-plugins :plugins="['dataTable']"></x-include-plugins>
@endsection
