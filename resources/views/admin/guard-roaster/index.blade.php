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
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />
                    @if (session('import_errors'))
                        <div class="alert alert-danger">
                            <ul>
                                @foreach (session('import_errors') as $error)
                                    <li>{{ $error['message'] }}</li> <!-- Assuming each error has a 'message' key -->
                                @endforeach
                            </ul>
                        </div>
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
                                    <th>Action</th>
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
                                    <td class="action-buttons">
                                        <a href="{{ route('guard-roasters.edit', $guardRoaster->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        <button data-source="Guard Roaster" data-endpoint="{{ route('guard-roasters.destroy', $guardRoaster->id) }}"
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
    <script>
        $(document).ready(function() {
            $('#importButton').on('click', function() {
                $('#fileInput').click();
            });

            $('#fileInput').on('change', function(event) {
                var file = $(this).prop('files')[0];
                if (file) {
                    var fileType = file.type;
                    if (fileType === 'text/csv' || fileType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                        $('#importForm').submit();
                    } else {
                        alert('Please select a valid CSV or XLSX file.');
                    }
                }
            });
        });
    </script>
@endsection