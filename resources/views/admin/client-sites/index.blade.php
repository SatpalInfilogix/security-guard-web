@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Client Sites</h4>

                        <div class="page-title-right">
                            @if(Auth::user()->can('create client site'))
                            <a href="{{ route('client-sites.create') }}" class="btn btn-primary">Add New Client Site</a>
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
                                    <th>Client Name</th>
                                    <th>Location Code</th>
                                    <th>Parish</th>
                                    <th>Email</th>
                                    @canany(['edit client site', 'delete client site'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($clientSites as $key => $clientSite)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $clientSite->client->client_name }}</td>
                                    <td>{{ $clientSite->location_code }}</td>
                                    <td>{{ $clientSite->parish }}</td>
                                    <td>{{ $clientSite->email }}</td>
                                    @canany(['edit client site', 'delete client site'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit client site'))
                                        <a href="{{ route('client-sites.edit', $clientSite->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete client site'))
                                        <button data-source="Client Site" data-endpoint="{{ route('client-sites.destroy', $clientSite->id) }}"
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