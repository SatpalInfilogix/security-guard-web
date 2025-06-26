@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Employee Leaves</h4>
                        <div class="page-title-right">
                            @if (Auth::user()->can('create employee leaves'))
                                <a href="{{ route('employee-leaves.create') }}" class="btn btn-primary">Add New Employee Leave</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <form id="filterForm" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <?php
                                $reasons = ['Approved', 'Pending', 'Rejected'];
                                ?>
                                <select name="leave_status" id="leave_status" class="form-control">
                                    <option value="" selected>Select status</option>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason }}" {{ old('reason') == $reason ? 'selected' : '' }}>{{ $reason }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="searchBtn" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <x-success-message :message="session('success')" />
                    <div class="card">
                        <div class="card-body">
                            <table id="employee-leaves-list" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Actual Start Date</th>
                                        <th>Actual End Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        @can('delete employee leaves')
                                            <th>Action</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Provide Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" value="" id="leaveId">
                    <textarea id="rejectionReason" class="form-control" rows="4" placeholder="Enter the reason for rejection"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmReject" class="btn btn-danger">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            let actionColumn = [];
            @can('delete employee leaves')
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        var editUrlTemplate = "{{ route('employee-leaves.modify', ['id' => '__ID__', 'date' => '__DATE__']) }}";
                        var editRoute = editUrlTemplate.replace('__ID__', data.employee_id).replace('__DATE__', data.created_date);
                        var actions = '<div class="action-buttons">';
                        actions += `<a class="btn btn-danger waves-effect waves-light btn-sm leave-delete-btn" href="#" data-source="Leave" data-id="${data.employee_id}" data-date="${data.created_date}">`;
                        actions += '<i class="fas fa-trash-alt"></i>';
                        actions += '</a>';
                        actions += `<a class="btn btn-primary waves-effect waves-light btn-sm leave-edit-btn" href="${editRoute}"><i class="fas fa-edit"></i></a>`;
                        actions += '</div>';
                        return actions;
                    }
                }];
            @endcan

            var table = $('#employee-leaves-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-employee-leaves-list') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.leave_status = $('#leave_status').val();
                        return d;
                    },
                    dataSrc: function(response) {
                        return response.data;
                    }
                },
                columns: [
                    {
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1 + meta.settings._iDisplayStart;
                        }
                    },
                    {
                        data: 'user.first_name',
                        render: function(data, type, row) {
                            return row.user ? `${row.user.first_name} ${row.user.surname ?? ''}` : 'N/A';
                        }
                    },
                    {
                        data: 'start_date'
                    },
                    {
                        data: 'end_date'
                    },
                    {
                        data: 'actual_start_date',
                        render: function(data) {
                            return data ? data : 'N/A';
                        }
                    },
                    {
                        data: 'actual_end_date',
                        render: function(data) {
                            return data ? data : 'N/A';
                        }
                    },
                    {
                        data: 'reason'
                    },
                    {
                        data: null,
                        name: 'status',
                        render: function(data, type, row) {
                            return `
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                        id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        ${row.status}
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                        <li><a class="dropdown-item change-status" href="javascript:void(0);" 
                                            data-status="Approved" data-id="${data.employee_id}" data-date="${data.created_date}">Approve</a></li>
                                        ${row.status !== 'Cancelled' ? `
                                        <li><a class="dropdown-item change-status" href="javascript:void(0);" 
                                            data-status="Rejected" data-id="${data.employee_id}" data-date="${data.created_date}">Reject</a></li>` : ''}
                                    </ul>
                                </div>
                            `;
                        }
                    },
                    ...actionColumn
                ]
            });

            $('#searchBtn').click(function() {
                table.draw();
            });

            $(document).on('click', '.leave-delete-btn', function() {
                let source = $(this).data('source');
                let leaveId = $(this).data('id');
                let createDate = $(this).data('date');
                var deleteApiEndpoint = "{{ route('employee-leaves.destroy', '') }}/" + leaveId + "/" + createDate;

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
                                    });
                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.change-status', function() {
                const newStatus = $(this).data('status');
                const leaveId = $(this).data('id');
                const createdDate = $(this).data('date');
                const statusButton = $(this).closest('tr').find('.dropdown-toggle');

                if (newStatus === 'Rejected') {
                    $('#leaveId').val(leaveId);
                    $('#confirmReject').attr('data-date', createdDate);
                    $('#rejectModal').modal('show');
                } else {
                    swal({
                        title: "Are you sure?",
                        text: `You are about to change the status to "${newStatus}". Do you want to proceed?`,
                        type: "warning",
                        showCancelButton: true,
                        closeOnConfirm: false,
                    }, function(isConfirm) {
                        if (isConfirm) {
                            statusButton.text(newStatus);
                            updateLeaveStatus(leaveId, newStatus, '', createdDate);
                        }
                    });
                }
            });

            $('#confirmReject').on('click', function() {
                const rejectionReason = $('#rejectionReason').val();
                const leaveId = $('#leaveId').val();
                const createdDate = $(this).data('date');
                
                if (!rejectionReason) {
                    swal({
                        title: "Error!",
                        text: "Please provide a reason for rejection.",
                        type: "error",
                        showConfirmButton: true
                    });
                    return;
                }
                
                $('#rejectModal').modal('hide');
                swal({
                    title: "Are you sure?",
                    text: `You are about to change the status to "Rejected". Do you want to proceed?`,
                    type: "warning",
                    showCancelButton: true,
                    closeOnConfirm: false,
                }, function(isConfirm) {
                    if (isConfirm) {
                        updateLeaveStatus(leaveId, 'Rejected', rejectionReason, createdDate);
                    }
                });
            });

            function updateLeaveStatus(leaveId, newStatus, rejectionReason = null, createdDate = null) {
                $.ajax({
                    url: `/employee-leaves/${leaveId}/update-status`,
                    method: 'POST',
                    data: {
                        status: newStatus,
                        created_date: createdDate,
                        rejection_reason: rejectionReason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            swal({
                                title: "Success!",
                                text: "Status updated successfully.",
                                type: "success",
                                showConfirmButton: false
                            });
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            swal({
                                title: "Error!",
                                text: "There was an issue updating the status. Please try again.",
                                type: "error",
                                showConfirmButton: true
                            });
                        }
                    },
                    error: function(xhr) {
                        swal({
                            title: "Error!",
                            text: xhr.responseJSON?.message || "An error occurred",
                            type: "error",
                            showConfirmButton: true
                        });
                    }
                });
            }
        });
    </script>
@endsection