@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Security Guards</h4>

                        <div class="page-title-right">
                            <a href="{{ route('security-guards.create') }}" class="btn btn-primary">Add New Security Guard</a>
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
                                    <th>Surname</th>
                                    <th>Firstname</th>
                                    <th>Middlename</th>
                                    <th>Email</th>
                                    <th>Phone number</th>
                                    <th>Action</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($securityGuards as $key => $securityGuard)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $securityGuard->surname}}</td>
                                    <td>{{ $securityGuard->first_name }}</td>
                                    <td>{{ $securityGuard->middle_name }}</td>
                                    <td>{{ $securityGuard->email }}</td>
                                    <td>{{ $securityGuard->phone_number }}</td>
                                    <td class="action-buttons">
                                        <a href="{{ route('security-guards.edit', $securityGuard->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        <button data-source="Security Guard" data-endpoint="{{ route('security-guards.destroy', $securityGuard->id) }}"
                                            class="delete-btn btn btn-outline-secondary btn-sm edit">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        {{-- <form id="delete-form-{{ $securityGuard->id }}" action="{{ route('security-guards.destroy', $securityGuard->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="confirmDelete({{ $securityGuard->id }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form> --}}
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
@endsection