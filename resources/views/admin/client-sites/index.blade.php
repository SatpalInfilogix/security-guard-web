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
                            <a href="{{ route('export.client') }}" class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Client/Manager Ids</a>
                            <a href="{{ url('download-client-site-sample') }}"
                            class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Client Sample File</a>
                            <div class="d-inline-block ">
                                <form id="importForm" action="{{ route('import.client-site') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label for="fileInput" class="btn btn-primary primary-btn btn-md mb-0">
                                        <i class="bx bx-cloud-download"></i> Import Client Site
                                        <input type="file" id="fileInput" name="file" accept=".csv, .xlsx" style="display:none;">
                                    </label>
                                </form>
                            </div>
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
                    @if (session('downloadUrl'))
                    <script>
                        window.onload = function() {
                            window.location.href = "{{ session('downloadUrl') }}";
                        };
                    </script>
                @endif
                    <div class="card">
                        <div class="card-body">
                            <table id="client-site-list" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client Code</th>
                                    <th>Client Name</th>
                                    <th>Location Code</th>
                                    <th>Location</th>
                                    @canany(['edit client site', 'delete client site'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->

        </div> <!-- container-fluid -->
    </div>
    <x-include-plugins :plugins="['dataTable', 'import']"></x-include-plugins>
    <script>
         $(document).ready(function() {
            let clientTable = $('#client-site-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-client-site-list') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        return d;
                    },
                    dataSrc: function(json) {
                        return json.data || [];
                    }
                },
                columns: [
                    { 
                        data: null, 
                        render: function(data, type, row, meta) {
                            return meta.row + 1 + meta.settings._iDisplayStart;
                        }
                    },
                    { data: 'client.client_code' },
                    { data: 'client.client_name' },
                    { data: 'location_code' },
                    { data: 'location' }, 
                    {
                        data: null,
                        render: function(data, type, row) {
                            var actions = '<div class="action-buttons">';

                            @can('edit client site')
                                actions += `<a class="btn btn-primary waves-effect waves-light btn-sm edit" href="{{ url('admin/client-sites') }}/${row.id}/edit">`;
                                actions += '<i class="fas fa-pencil-alt"></i>';
                                actions += '</a>';
                            @endcan

                            @can('delete client site')
                                actions += `<a class="btn btn-danger waves-effect waves-light btn-sm clientSite-delete-btn" href="#" data-source="Client site" data-id="${row.id}">`;
                                actions += '<i class="fas fa-trash-alt"></i>';
                                actions += '</a>';
                            @endcan

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[0, 'asc']]
            });

            // Handle Delete button click
            $(document).on('click', '.clientSite-delete-btn', function() {
                let source = $(this).data('source');
                let clientId = $(this).data('id');
                var deleteApiEndpoint = "{{ route('client-sites.destroy', '') }}/" + clientId;

                swal({
                    title: "Are you sure?",
                    text: `You really want to remove this ${source}?`,
                    type: "warning",
                    showCancelButton: true,
                    closeOnConfirm: false,
                }, function(isConfirm) {
                    if (isConfirm) {
                        $.ajax({
                            url: deleteApiEndpoint,
                            method: 'DELETE',
                            data: {
                                '_token': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if(response.success){
                                    swal({
                                        title: "Success!",
                                        text: response.message,
                                        type: "success",
                                        showConfirmButton: false
                                    }) 

                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                }
                            }
                        })
                    }
                });
            })
        });  
    </script>
@endsection