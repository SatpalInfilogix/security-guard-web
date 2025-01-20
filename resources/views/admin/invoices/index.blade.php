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
                            <button id="exportBtn" class="btn btn-success" style="display: none;" onclick="exportInvoices()">Export Invoices</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form id="filterForm" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="client_id[]" id="client_id" class="form-control" multiple>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="client_site_id[]" id="client_site_id" class="form-control" multiple>
                                    {{-- @foreach($clientSites as $clientSite)
                                        <option value="{{ $clientSite->id }}">{{ $clientSite->location_code }}</option>
                                    @endforeach --}}
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" id="date" name="date" class="form-control datePicker" value="" placeholder="Select Date Range" autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <select name="paid_status" id="paid_status" class="form-control">
                                <option value="" selected disabled>Select Status</option>
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                            </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="searchBtn" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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

    <x-include-plugins :plugins="['dataTable', 'chosen', 'datePicker']"></x-include-plugins>

    <script>
        $(function(){
            $('#client_id').chosen({
                width: '100%',
                placeholder_text_multiple: 'Select Client'
            });
            $('#client_site_id').chosen({
                width: '100%',
                placeholder_text_multiple: 'Select Client Site'
            });

            $('#client_id').on('change', function() {
                let selectedClientIds = $(this).val();
                updateClientSites(selectedClientIds);
            });

            function updateClientSites(clientIds) {
                if (clientIds.length > 0) {
                    $.ajax({
                        url: "{{ route('get-client-sites') }}",
                        type: "GET",
                        data: {
                            client_ids: clientIds
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#client_site_id').empty();
                                response.clientSites.forEach(function(clientSite) {
                                    $('#client_site_id').append(new Option(clientSite.location_code, clientSite.id)); // Add new options
                                });
                                $('#client_site_id').trigger('chosen:updated');
                            } else {
                                alert('Error fetching client sites');
                            }
                        },
                        error: function() {
                            alert('Error fetching client sites');
                        }
                    });
                } else {
                    $('#client_site_id').empty();
                    $('#client_site_id').trigger('chosen:updated');
                }
            }

            $('#client_id, #client_site_id').on('change', function() {
                checkExportButtonVisibility();
            });

            $('#date').on('change', function() {
                checkExportButtonVisibility();
            });

            function checkExportButtonVisibility() {
                let selectedClientIds = $('#client_id').val();
                let selectedClientSiteIds = $('#client_site_id').val();
                let selectedDate = $('#date').val();

                if (selectedClientIds.length > 0 && selectedDate !== '') {
                    $('#exportBtn').show();
                } else {
                    $('#exportBtn').hide();
                }
            }
        });

        $(document).ready(function() {
            let invoiceTable = $('#invoice-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-invoice-list') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.client_ids = $('#client_id').val();
                        d.client_site_ids = $('#client_site_id').val(); 
                        d.date = $('#date').val();
                        d.paid_status = $('#paid_status').val();
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
            $('#searchBtn').on('click', function() {
                invoiceTable.ajax.reload();
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

            window.exportInvoices = function() {
                let selectedClientIds = $('#client_id').val();
                let selectedClientSiteIds = $('#client_site_id').val();
                let selectDate = $('#date').val();
                
                window.location.href = `{{ route('invoice.export-csv') }}?client_ids=${selectedClientIds.join(',')}&client_site_ids=${selectedClientSiteIds.join(',')}&date=${selectDate}`;
            };
        });
    </script>
@endsection