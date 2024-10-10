@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Roles List</h4>
                        <div class="page-title-right">
                            <a href="{{ route('roles-and-permissions.create') }}" class="btn btn-primary">Add New Role</a>
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
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $key=> $role)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ ucwords($role->name) }}</td>
                                        <td class="action-buttons">
                                            <a href="{{ route('roles-and-permissions.edit', $role->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                            <button data-source="Role" data-endpoint="{{ route('roles-and-permissions.destroy', $role->id)}}"
                                                class="delete-btn btn btn-outline-secondary btn-sm edit">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
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