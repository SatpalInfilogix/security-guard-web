@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Clients</h4>

                        <div class="page-title-right">
                            @if(Auth::user()->can('create client'))
                            <a href="{{ route('clients.create') }}" class="btn btn-primary">Add New Client</a>
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
                                    <th>Client Code</th>
                                    <th>Client Name</th>
                                    @canany(['edit client', 'delete client'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($clients as $key => $client)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $client->client_code }}</td>
                                    <td>{{ $client->client_name }}</td>
                                    @canany(['edit client', 'delete client'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('delete client'))
                                        <a href="{{ route('clients.edit', $client->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete client'))
                                        <button data-source="Client" data-endpoint="{{ route('clients.destroy', $client->id) }}"
                                            class="delete-btn btn btn-outline-secondary btn-sm edit">
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