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
                            <a href="{{ route('security-guards.pdf') }}" class="btn btn-primary"><i class="bx bx-download"></i>
                                Download PDF</a>
                            <a href="{{ route('export.guards') }}" class="btn btn-primary"><i class="bx bx-export"></i>
                                Security Guard Bulk Export</a>
                            <a href="{{ url('download-guard-sample') }}" class="btn btn-primary primary-btn btn-md me-1"><i
                                    class="bx bx-download"></i> Guard Sample File</a>
                            <div class="d-inline-block me-1">
                                <form id="importForm" action="{{ route('import.security-guard') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <label for="fileInput" class="btn btn-primary primary-btn btn-md mb-0">
                                        <i class="bx bx-cloud-download"></i> Import Security Guard
                                        <input type="file" id="fileInput" name="file" accept=".csv, .xlsx"
                                            style="display:none;">
                                    </label>
                                </form>
                            </div>
                            @if (Auth::user()->can('create security guards'))
                                <a href="{{ route('security-guards.create') }}" class="btn btn-primary">Add New Security
                                    Guard</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form id="filterForm" method="GET">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="search_emp_code" class="form-control" id="search_emp_code">
                                    <option value="">Select Employee Code</option>
                                    @foreach ($securityGuards as $securityGuard)
                                        @if (!empty($securityGuard->user_code))
                                            <option value="{{ $securityGuard->id }}">{{ $securityGuard->user_code }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="search_name" class="form-control" placeholder="Search by Name"
                                    value="{{ request('search_name') }}" id="search_name">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="search_email" class="form-control" placeholder="Search by Email"
                                    value="{{ request('search_email') }}" id="search_email">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="search_phone" class="form-control" placeholder="Search by Phone"
                                    value="{{ request('search_phone') }}" id="search_phone">
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control" id="is_status">
                                    <option value="">All Status</option>
                                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="searchBtn" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
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
                            <table id="security-guard-list" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Emp. Code</th>
                                        <th>First Name</th>
                                        <th>Middle Name</th>
                                        <th>Surname</th>
                                        <th>Phone</th>
                                        <th>SL</th>
                                        <th>VL</th>
                                        <th>Status</th>
                                        @canany(['edit security guards', 'delete security guards'])
                                            <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>

                                <tbody id="guardTableBody">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-include-plugins :plugins="['dataTable', 'import']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            let actionColumn = [];

            @canany(['edit security guards', 'delete security guards'])
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        var actions = '<div class="action-buttons">';
                        @can('edit security guards')
                            actions +=
                                `<a class="btn btn-primary waves-effect waves-light btn-sm edit" href="{{ url('admin/security-guards') }}/${row.id}/edit?page=${securityGuardTable.page() + 1}">`;
                            actions += '<i class="fas fa-pencil-alt"></i>';
                            actions += '</a>';
                        @endcan
                        if (row.status !== 'Active' || @json(Auth::user()->hasRole('Admin'))) {
                            @can('delete security guards')
                                actions +=
                                    `<a data-source="Security Guard" class="security-guard-delete btn btn-danger waves-effect waves-light btn-sm" href="#" data-id="${row.id}"> <i class="fas fa-trash-alt"></i></a>`;
                            @endcan
                        }

                        actions += '</div>';
                        return actions;
                    }
                }];
            @endcanany
            let securityGuardTable = $('#security-guard-list').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: {
                    url: "{{ route('get-security-guard') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.search_name = $('#search_name').val(); // Send filter values to the server
                        d.search_email = $('#search_email').val();
                        d.search_phone = $('#search_phone').val();
                        d.search_emp_code = $('#search_emp_code').val();
                        d.status = $('#is_status').val();
                        return d;
                    },
                    dataSrc: function(json) {
                        return Object.values(json.data);
                    }
                },
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1 + meta.settings._iDisplayStart;
                        }
                    },
                    {
                        data: 'user_code'
                    },
                    {
                        data: 'first_name'
                    },
                    {
                        data: 'middle_name'
                    },
                    {
                        data: 'surname'
                    },
                    {
                        data: 'phone_number'
                    },
                    {
                        data: 'pendingLeaveBalance'
                    },
                    {
                        data: 'vacationLeaveBalance'
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {
                            let statusOptions = ['Active', 'Inactive', 'Hold'];
                            let statusDropdown = '';
                            @can('edit security guards')
                                statusDropdown =
                                    `<select name="guard_status" class="form-control" data-user-id="${row.id}" `;
                                if (data === 'Active' && !@json(Auth::user()->hasRole('Admin'))) {
                                    statusDropdown +=
                                    'disabled'; // Disable the dropdown for non-super-admin users
                                }

                                statusDropdown += '>';
                                statusDropdown +=
                                    '<option value="" selected disabled>Select Status</option>';

                                const userDocuments = row.userDocuments || {};

                                statusOptions.forEach(function(value) {
                                    let disabled = '';

                                    // if (data === 'Inactive' && (
                                    //     userDocuments.trn == null || 
                                    //     userDocuments.nis == null || 
                                    //     userDocuments.birth_certificate == null || 
                                    //     userDocuments.psra == null
                                    // )) {
                                    //     if (value === 'Active') {
                                    //         disabled = 'disabled';
                                    //     }
                                    // }

                                    statusDropdown += `<option value="${value}" 
                                    ${data === value ? 'selected' : ''} 
                                    ${disabled}>
                                    ${value}
                                </option>`;
                                });

                                statusDropdown += '</select>';
                            @endcan
                            return statusDropdown;
                        }
                    },
                    ...actionColumn
                ],
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc']
                ]
            });

            $('#searchBtn').on('click', function() {
                securityGuardTable.ajax.reload();
            });
            /* $('#search_name, #search_email, #search_phone, #is_status').on('change keyup', function() {
                securityGuardTable.ajax.reload();
            }); */

            $(document).on('click', '.security-guard-delete', function() {
                let source = $(this).data('source');
                let guardId = $(this).data('id');
                var deleteApiEndpoint = "{{ route('security-guards.destroy', '') }}/" + guardId;

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
                                if (response.success) {
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

            $(document).on('change', 'select[name="guard_status"]', function() {
                let status = $(this).val();
                let userId = $(this).attr('data-user-id');
                let selectElement = $(this);
                $.ajax({
                    url: "{{ route('users.update-status') }}",
                    method: 'PUT',
                    data: {
                        user_id: userId,
                        status: status,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            swal({
                                title: "Success!",
                                text: response.message,
                                type: "success",
                                showConfirmButton: false
                            })

                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            swal({
                                title: "Missing Documents",
                                text: "Please ensure all required documents (TRN, NIS) are uploaded before setting the status to Active.",
                                type: "error",
                                showConfirmButton: true
                            });
                            if (status === "Active") {
                                selectElement.val("Inactive");
                            }
                        }
                    }
                })
            })
        });
    </script>
@endsection
