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
                                        <th>Guard Name</th>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        @canany(['edit leaves', 'delete leaves'])
                                        <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaves as $key => $leave)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ optional($leave->user)->first_name .' '. optional($leave->user)->surname }}</td>
                                            <td>{{ $leave->date }}</td>
                                            <td>{{ $leave->reason }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                        {{ $leave->status }}
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-status="Approved" data-id="{{ $leave->id }}">Approve</a></li>
                                                        @if($leave->status !== 'Cancelled')
                                                            <li><a class="dropdown-item" href="javascript:void(0);" data-status="Rejected" data-id="{{ $leave->id }}">Reject</a></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                            @canany(['edit leaves', 'delete leaves'])
                                            <td class="action-buttons">
                                                @if(Auth::user()->can('delete leaves'))
                                                <button data-source="Leave" data-endpoint="{{ route('leaves.destroy', $leave->id) }}"
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
            let leaveId = null;
            $('.dropdown-item').on('click', function() {
                const newStatus = $(this).data('status');  // Get the new status
                const leaveId = $(this).data('id');        // Get the leave ID
                const statusButton = $(this).closest('tr').find('.dropdown-toggle'); // Find the dropdown button in the same row

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
                console.log('Leave Id:',leaveId)
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
                        console.log(leaveId);
                        updateLeaveStatus(leaveId, 'Rejected', rejectionReason);
                    }
                });
            });

            function updateLeaveStatus(leaveId, newStatus, rejectionReason = null) {
                console.log('aas', leaveId);
                $.ajax({
                    url: `/leaves/${leaveId}/update-status`,  // The URL for the status update
                    method: 'POST',
                    data: {
                        status: newStatus,
                        rejection_reason: rejectionReason,  // Pass rejection reason if applicable
                        _token: '{{ csrf_token() }}'  // CSRF token for security
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
                                location.reload();  // Reloads the page after 2 seconds
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