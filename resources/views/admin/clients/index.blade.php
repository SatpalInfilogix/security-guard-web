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
                            <table id="datatable" class="client-list table table-bordered dt-responsive  nowrap w-100">
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
            let actionColumn = [];
            @canany(['edit client', 'delete client'])
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        var actions = '<div class="action-buttons">';

                    @can('edit client')
                            actions += `<a class="btn btn-primary waves-effect waves-light btn-sm edit" href="{{ url('admin/clients') }}/${row.id}/edit">`;
                            actions += '<i class="fas fa-pencil-alt"></i>';
                            actions += '</a>';
                        @endcan

                        @can('delete client')
                            actions += `<a class="btn btn-danger waves-effect waves-light btn-sm client-delete-btn" href="#" data-source="Client" data-id="${row.id}">`;
                            actions += '<i class="fas fa-trash-alt"></i>';
                            actions += '</a>';
                        @endcan

                        actions += '</div>';
                        return actions;
                    }
                }];
            @endcanany

            let clientTable = $('.client-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-client-list') }}",
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
                    { data: 'client_code' },
                    { data: 'client_name' },
                    ...actionColumn
                ],
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[0, 'asc']]
            });

            // Handle Delete button click
            $(document).on('click', '.client-delete-btn', function() {
                let source = $(this).data('source');
                let clientId = $(this).data('id');
                var deleteApiEndpoint = "{{ route('clients.destroy', '') }}/" + clientId;

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