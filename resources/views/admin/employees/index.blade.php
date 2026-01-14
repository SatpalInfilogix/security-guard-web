@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Employee</h4>

                        <div class="page-title-right">
                            <button id="downloadPdfBtn" class="btn btn-primary"><i class="bx bx-download"></i> Download
                                PDF</button>
                            <a href="{{ route('export.employee') }}" class="btn btn-primary"><i class="bx bx-export"></i>
                                Employee Bulk Export</a>
                            <a href="{{ url('download-employee-sample') }}"
                                class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Employee
                                Sample File</a>
                            <div class="d-inline-block me-1">
                                <form id="importForm" action="{{ route('import.employee') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <label for="fileInput" class="btn btn-primary primary-btn btn-md mb-0">
                                        <i class="bx bx-cloud-download"></i> Import Employee
                                        <input type="file" id="fileInput" name="file" accept=".csv, .xlsx"
                                            style="display:none;">
                                    </label>
                                </form>
                            </div>
                            @if (Auth::user()->can('create employee'))
                                <a href="{{ route('employees.create') }}" class="btn btn-primary">Add New Employee</a>
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
                                    @foreach ($employees as $employee)
                                        @if (!empty($employee->user_code))
                                            <option value="{{ $employee->id }}">{{ $employee->user_code }}</option>
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
                            <table id="employee-list" class="table table-bordered dt-responsive  nowrap w-100">
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
                                        @canany(['edit employee', 'delete employee'])
                                            <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>

                                <tbody>

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

            @canany(['edit employee', 'delete employee'])
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        var actions = '<div class="action-buttons">';
                        @can('edit employee')
                            actions +=
                                `<a class="btn btn-primary waves-effect waves-light btn-sm edit" href="{{ url('admin/employees') }}/${row.id}/edit?page=${employeeTable.page() + 1}&search_emp_code=${encodeURIComponent($('#search_emp_code').val())}&search_name=${encodeURIComponent($('#search_name').val())}&search_email=${encodeURIComponent($('#search_email').val())}&search_phone=${encodeURIComponent($('#search_phone').val())}&status=${encodeURIComponent($('#is_status').val())}">`;
                            actions += '<i class="fas fa-pencil-alt"></i>';
                            actions += '</a>';
                        @endcan
                        if (row.status !== 'Active' || @json(Auth::user()->hasRole('Admin'))) {
                            @can('delete employee')
                                actions +=
                                    `<a data-source="Employee" class="employee-delete btn btn-danger waves-effect waves-light btn-sm" href="#" data-id="${row.id}"> <i class="fas fa-trash-alt"></i></a>`;
                            @endcan
                        }

                        actions += '</div>';
                        return actions;
                    }
                }];
            @endcanany
            let employeeTable = $('#employee-list').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: {
                    url: "{{ route('get-employee') }}",
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
                            @can('edit employee')
                                statusDropdown =
                                    `<select name="employee_status" class="form-control" data-user-id="${row.id}" `;
                                if (data === 'Active' && !@json(Auth::user()->hasRole('Admin'))) {
                                    statusDropdown += 'disabled';
                                }

                                statusDropdown += '>';
                                statusDropdown +=
                                    '<option value="" selected disabled>Select Status</option>';

                                const userDocuments = row.userDocuments || {};

                                statusOptions.forEach(function(value) {
                                    let disabled = '';

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
                employeeTable.ajax.reload();
            });

            $(document).on('click', '.employee-delete', function() {
                let source = $(this).data('source');
                let empId = $(this).data('id');
                var deleteApiEndpoint = "{{ route('employees.destroy', '') }}/" + empId;

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

            $(document).on('change', 'select[name="employee_status"]', function() {
                let status = $(this).val();
                let userId = $(this).attr('data-user-id');
                let selectElement = $(this);
                $.ajax({
                    url: "{{ route('employees.employee-status') }}",
                    method: 'POST',
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

            $('#downloadPdfBtn').on('click', function() {
                var filters = {
                    search_emp_code: $('#search_emp_code').val(),
                    search_name: $('#search_name').val(),
                    search_email: $('#search_email').val(),
                    search_phone: $('#search_phone').val(),
                    status: $('#is_status').val()
                };

                var downloadUrl = `{{ route('employees.pdf') }}?` + $.param(filters);
                window.location.href = downloadUrl;
            });
        });
    </script>
@endsection
