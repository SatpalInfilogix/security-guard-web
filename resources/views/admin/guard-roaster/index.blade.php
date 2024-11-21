@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Guard Roaster</h4>

                        <div class="page-title-right">
                            <a href="{{ route('export.csv') }}" class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Guard Roaster Configuration File</a>
                            <a href="{{ url('download-guard-roaster-sample') }}"
                                class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Guard Roaster Sample File</a>
                                
                            @canany(['create security guards'])
                                <div class="d-inline-block me-1">
                                    <form id="importForm" action="{{ route('import.guard-roaster') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <label for="fileInput" class="btn btn-primary primary-btn btn-md mb-0">
                                            <i class="bx bx-cloud-download"></i> Import Guard Roaster
                                            <input type="file" id="fileInput" name="file" accept=".csv, .xlsx" style="display:none;">
                                        </label>
                                    </form>
                                </div>
                                <a href="{{ route('guard-roasters.create') }}" class="btn btn-primary">Add New Guard Roaster</a>
                            @endcanany
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    @if (session('downloadUrl'))
                        <script>
                            window.onload = function() {
                                window.location.href = "{{ session('downloadUrl') }}";
                            };
                        </script>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <table id="datatable" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Guard Name</th>
                                    <th>Client Name</th>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    @canany(['edit security guards', 'delete security guards'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($guardRoasters as $key => $guardRoaster)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ optional($guardRoaster->user)->first_name .' '. optional($guardRoaster->user)->surname }}</td>
                                    <td>{{ optional($guardRoaster->client)->client_name }}</td>
                                    <td>{{ $guardRoaster->date }}</td>
                                    <td>{{ $guardRoaster->start_time }}</td>
                                    <td>{{ $guardRoaster->end_time }}</td>
                                    @canany(['edit security guards', 'delete security guards'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit security guards'))
                                            <a href="{{ route('guard-roasters.edit', $guardRoaster->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete security guards'))
                                            <button data-source="Guard Roaster" data-endpoint="{{ route('guard-roasters.destroy', $guardRoaster->id) }}"
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
    <x-include-plugins :plugins="['dataTable', 'import']"></x-include-plugins>
@endsection