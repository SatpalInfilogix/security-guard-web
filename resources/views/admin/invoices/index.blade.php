@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Invoices</h4>

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
                            <table id="invoice-list" class="client-list table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice Code</th>
                                    <th>Invoice Date</th>
                                    <th>Client Location Name</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
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

    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>

    <script>
        $(document).ready(function() {
            let invoiceTable = $('#invoice-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-invoice-list') }}",
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
                    { data: 'invoice_code' },
                    { data: 'invoice_date' },
                    { data: 'location_code' },
                    { data: 'total_amount' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="statusDropdown${row.id}" data-bs-toggle="dropdown" aria-expanded="false">
                                        ${row.status}
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown${row.id}">
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="updateInvoiceStatus(${row.id}, 'Paid')">Paid</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="updateInvoiceStatus(${row.id}, 'Unpaid')">Unpaid</a></li>
                                    </ul>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<button class="btn btn-danger btn-sm" onclick="downloadInvoicePdf(${row.id})"> <i class="fas fa-file-pdf"></i></button>`;
                        }
                    }
                ],
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[0, 'asc']]
            });

            window.downloadInvoicePdf = function(invoiceId) {
                window.location.href = "{{ route('invoice.download-pdf', ':invoiceId') }}".replace(':invoiceId', invoiceId);
            };

            window.downloadInvoiceCsv = function(invoiceId) {
                var form = $('<form method="POST" action="{{ route('invoice.export-csv') }}"></form>');
                form.append('@csrf');
                form.append('<input type="hidden" name="invoice_id" value="' + invoiceId + '">');
                $('body').append(form);
                form.submit();
            };

            window.updateInvoiceStatus = function(invoiceId, status) {
                if (confirm('Are you sure you want to change the status to ' + status + '?')) {
                    $.ajax({
                        url: "{{ route('invoice.update-status') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            invoice_id: invoiceId,
                            status: status
                        },
                        success: function(response) {
                            if (response.success) {
                                invoiceTable.ajax.reload();
                                alert('Invoice status updated to ' + status);
                            } else {
                                alert('Error updating status');
                            }
                        },
                        error: function() {
                            alert('Error updating status');
                        }
                    });
                }
            };
        });
    </script>
@endsection