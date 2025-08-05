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
                            <div class="col-md-2">
                                <?php
                                $reasons = ['Approved', 'Pending', 'Rejected'];
                                ?>
                                <select name="leave_status" id="leave_status" class="form-control">
                                    <option value="" selected disabled>Select status</option>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason }}"
                                            {{ old('reason') == $reason ? 'selected' : '' }}>{{ $reason }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="month" id="month" class="form-control">
                                    <option value="">All Months</option>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="year" id="year" class="form-control">
                                    <option value="">All Years</option>
                                    @for ($i = date('Y'); $i >= date('Y') - 1; $i--)
                                        <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                                            {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="searchBtn" class="btn btn-primary">Search</button>
                                <button type="button" id="resetBtn" class="btn btn-secondary">Reset</button>
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
                                        <th>Start Date</th>
                                        <th>End Date</th>
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
            // const currentDate = new Date();
            // $('#month').val(currentDate.getMonth() + 1);
            // $('#year').val(currentDate.getFullYear());

            let actionColumn = [];
            @can('delete leaves')
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        // Clean URL construction using template literals
                        var editRoute = "{{ route('leaves.modify', ['guardId' => ':id', 'batchId' => ':batch']) }}"
                            .replace(':id', data.guard_id)
                            .replace(':batch', data.batch_id);

                        var actions = `
                            <div class="action-buttons">
                                <a class="btn btn-danger waves-effect waves-light btn-sm leave-delete-btn" 
                                href="javascript:void(0);" 
                                data-source="Leave" 
                                data-id="${data.guard_id}" 
                                data-batch_id="${data.batch_id}">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <a class="btn btn-primary waves-effect waves-light btn-sm leave-edit-btn" 
                                href="${editRoute}">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        `;
                        return actions;
                    }
                }];
            @endcan

            console.log('actionColumn', actionColumn)

            var table = $('#leaves-list').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
                ajax: {
                    url: "{{ route('get-leaves-list') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.leave_status = $('#leave_status').val();
                        d.month = $('#month').val();
                        d.year = $('#year').val();
                        return d;
                    }
                },
                columns: [
                    {data: null, render: function(data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }},
                    {data: 'user.first_name', render: function(data, type, row) {
                        return row.user ? `${row.user.first_name} ${row.user.surname ?? ''}` : 'N/A';
                    }},
                    {data: 'start_date'},
                    {data: 'end_date'},
                    {data: 'actual_start_date', render: function(data) {
                        return data ? data : 'N/A';
                    }},
                    {data: 'actual_end_date', render: function(data) {
                        return data ? data : 'N/A';
                    }},
                    {data: 'reason'},
                    {data: null, name: 'status', render: function(data, type, row) {
                        return `
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                    id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    ${row.status}
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                    <li><a class="dropdown-item change-status" href="javascript:void(0);" 
                                        data-status="Approved" data-id="${row.guard_id}" data-batch_id="${row.batch_id}">Approve</a></li>
                                    ${row.status !== 'Cancelled' ? `
                                        <li><a class="dropdown-item change-status" href="javascript:void(0);" 
                                            data-status="Rejected" data-id="${row.guard_id}" data-batch_id="${row.batch_id}">Reject</a></li>` : ''}
                                </ul>
                            </div>
                        `;
                    }},
                    ...actionColumn
                ]
            });

            $('#searchBtn').click(function() { table.draw(); });

            $('#resetBtn').click(function() {
                $('#leave_status, #month, #year').val('');
                table.draw();
            });

            // $('#resetBtn').click(function() {
            //     $('#leave_status').val('');
            //     $('#month').val(currentDate.getMonth() + 1);
            //     $('#year').val(currentDate.getFullYear());
            //     table.draw();
            // });
        });

        $(document).on('click', '.leave-delete-btn', function() {
            let source = $(this).data('source');
            let guardId = $(this).data('id');
            let batch_id = $(this).data('batch_id');
            var deleteApiEndpoint = "{{ route('leaves.destroy', '') }}/" + guardId + "/" + batch_id;

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
                        data: {'_token': '{{ csrf_token() }}'},
                        success: function(response) {
                            if (response.success) {
                                swal({
                                    title: "Success!",
                                    text: response.message,
                                    type: "success",
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                table.draw(false);
                            }
                        }
                    });
                }
            });
        });


        $(document).ready(function() {
            let leaveId = null;
            $(document).on('click', '.change-status', function() {
                const newStatus = $(this).data('status');
                const guardId = $(this).data('id');
                const batch_id = $(this).data('batch_id');
                const statusButton = $(this).closest('tr').find('.dropdown-toggle');

                if (newStatus === 'Rejected') {
                    $('#leaveId').val(guardId);
                    $('#confirmReject').attr('data-batch_id', batch_id);
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
                            updateLeaveStatus(guardId, newStatus, '', batch_id);
                        }
                    });
                }
            });

            $('#confirmReject').on('click', function() {
                const rejectionReason = $('#rejectionReason').val();
                const guardId = $('#leaveId').val();
                const batch_id = $(this).data('batch_id');
                
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
                updateLeaveStatus(guardId, 'Rejected', rejectionReason, batch_id);
            });

            function updateLeaveStatus(guardId, newStatus, rejectionReason = null, batch_id = null) {
                $.ajax({
                    url: `/leaves/${guardId}/update-status`,
                    method: 'POST',
                    data: {
                        status: newStatus,
                        batch_id: batch_id,
                        rejection_reason: rejectionReason,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            swal({
                                title: "Success!",
                                text: "Status updated successfully.",
                                type: "success",
                                timer: 1500,
                                showConfirmButton: false
                            });
                            table.ajax.reload(null, false);
                            
                            // Send notification if needed
                            if (response.guardId) {
                                $.ajax({
                                    url: `{{ route('leaves.sendNotification') }}/${response.guardId}/${response.status}`,
                                    type: 'GET'
                                });
                            }
                        }
                    }
                });
            }

        });
    </script>
@endsection
