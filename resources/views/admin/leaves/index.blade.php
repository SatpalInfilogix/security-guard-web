@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Leaves</h4>

                        <div class="page-title-right">
                            @if (Auth::user()->can('create leaves'))
                                <a href="{{ route('leaves.create') }}" class="btn btn-primary">Add New Leave</a>
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
                                $reasons = ['Approve', 'Pending', 'Reject'];
                                ?>
                                <select name="leave_status" id="leave_status" class="form-control">
                                    <option value="" selected disabled>Select status</option>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason }}" {{ old('reason') }}>{{ $reason }}</option>
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
                            <table id="leaves-list" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Guard Name</th>
                                        <th>Date</th>
                                        <th>Actual Start Date</th>
                                        <th>Actual End Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        @can('delete leaves')
                                            <th>Action</th>
                                        @endcan
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
            @can('delete leaves')
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        var actions = '<div class="action-buttons">';
                        actions +=
                            `<a class="btn btn-danger waves-effect waves-light btn-sm leave-delete-btn" href="#" data-source="Leave" data-id="${row.id}">`;
                        actions += '<i class="fas fa-trash-alt"></i>';
                        actions += '</a>';
                        actions += '</div>';
                        return actions;
                    }
                }];
            @endcan

            console.log('actionColumn', actionColumn)

            var table = $('#leaves-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-leaves-list') }}",
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
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1 + meta.settings._iDisplayStart;
                        }
                    },
                    {
                        data: 'user.first_name'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'actual_start_date',
                        render: function(data, type, row) {
                            return data ? data : 'N/A';
                        }
                    },
                    {
                        data: 'actual_end_date',
                        render: function(data, type, row) {
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
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        ${row.status}
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                        <li><a class="dropdown-item" href="javascript:void(0);" data-status="Approved" data-id="${row.id}">Approve</a></li>
                                        ${row.status !== 'Cancelled' ? `<li><a class="dropdown-item" href="javascript:void(0);" data-status="Rejected" data-id="${row.id}">Reject</a></li>` : ''}
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
        });

        $(document).on('click', '.leave-delete-btn', function() {
            let source = $(this).data('source');
            let leaveId = $(this).data('id');
            var deleteApiEndpoint = "{{ route('leaves.destroy', '') }}/" + leaveId;

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

        $(document).ready(function() {
            let leaveId = null;
            $(document).on('click', '.dropdown-item', function() {
                const newStatus = $(this).data('status');
                const leaveId = $(this).data('id');
                const statusButton = $(this).closest('tr').find('.dropdown-toggle');

                if (newStatus === 'Rejected') {
                    $('#leaveId').val(leaveId);
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
                            updateLeaveStatus(leaveId, newStatus);
                        }
                    });
                }
            });

            $('#confirmReject').on('click', function() {
                const rejectionReason = $('#rejectionReason').val();
                const leaveId = $('#leaveId').val();
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
                        updateLeaveStatus(leaveId, 'Rejected', rejectionReason);
                    }
                });
            });

            function updateLeaveStatus(leaveId, newStatus, rejectionReason = null) {
                $.ajax({
                    url: `/leaves/${leaveId}/update-status`,
                    method: 'POST',
                    data: {
                        status: newStatus,
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
                });
            }
        });
    </script>
@endsection
