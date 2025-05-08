@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Employee Payroll</h4>

                        <div class="page-title-right">
                            <a href="javascript:void(0);" id="bulkDownloadBtn" class="btn btn-primary primary-btn btn-md me-1">
                                <i class="bx bx-download"></i> Bulk Download PDFs
                            </a>
                            <a href="javascript:void(0);" id="exportBtn" class="btn btn-primary primary-btn btn-md me-1">
                                <i class="bx bx-download"></i> SO1 Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <form method="GET" id="attendance-form">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="year" id="year" class="form-control select2">
                                    @for ($i = now()->year; $i >= 2024; $i--)
                                        <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                                            {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            @php
                                $selectedMonth = request('month') ?? now()->subMonth()->month;
                            @endphp
                            <div class="col-md-3">
                                <select name="month" id="month" class="form-control select2">
                                    @foreach (range(1, 12) as $m)
                                        <option value="{{ old('month', $m) }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                        </option>
                                    @endforeach
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
                            <table id="payroll-list" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Normal Days</th>
                                        <th>Paid Leaves</th>
                                        <th>Unpaid Leaves</th>
                                        @canany(['edit employee payroll'])
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

    <x-include-plugins :plugins="['datePicker', 'dataTable', 'import', 'dateRange']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            // flatpickr("#date", {
            //     mode: 'range',
            //     showMonths: 2,
            // });

            $('.select2').select2();

            let actionColumn = [];
            @canany(['edit employee payroll'])
                actionColumn = [{
                    data: null,
                    render: function(data, type, row) {
                        var actions = '<div class="action-buttons">';

                        @can('edit employee payroll')
                            actions +=
                                `<a class="btn btn-primary waves-effect waves-light btn-sm edit" href="{{ url('admin/employee-payroll') }}/${row.id}/edit?year=${$('#year').val()}&month=${$('#month').val()}">`;
                            actions += '<i class="fas fa-pencil-alt"></i>';
                            actions += '</a>';
                        @endcan

                        actions +=
                            `<button class="btn btn-primary btn-sm" onclick="downloadInvoicePdf('${row.id}')">`;
                        actions += '<i class="fas fa-file-pdf"></i>';
                        actions += '</button>';

                        actions += '</div>';
                        return actions;
                    }
                }];
            @endcanany

            window.downloadInvoicePdf = function(invoiceId) {
                window.location.href = "{{ route('employee-payroll.download-pdf', ':invoiceId') }}".replace(
                    ':invoiceId', invoiceId);
            };

            let year = ($('#year').val() || '').trim();
            let month = ($('#month').val() || '').trim();

            let payrollTable = $('#payroll-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-employee-payroll-list') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        // d.date = $('#date').val();
                        d.year = $('#year').val(); // new
                        d.month = $('#month').val(); // new
                        return d;
                    },
                    dataSrc: function(json) {
                        return json.data || [];
                    }
                },
                columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                }, {
                    data: 'user.first_name'
                }, {
                    data: 'start_date'
                }, {
                    data: 'end_date'
                }, {
                    data: 'normal_days'
                }, {
                    data: 'leave_paid'
                }, {
                    data: 'leave_not_paid'
                }, ...actionColumn],
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'asc']
                ]
            });

            // $('#date').on('change', function() {
            //     let exportUrl =
            //         "{{ route('employee-payroll-export.csv', ['year' => '__year__', 'month' => '__month__']) }}";
            //     exportUrl = exportUrl
            //         .replace('__year__', year)
            //         .replace('__month__', month);
            //     $('#exportBtn').attr('href', exportUrl);
            // });

            $('#searchBtn').on('click', function() {
                let year = $('#year').val();
                let month = $('#month').val();

                if (!year || !month) {
                    alert('Please select both year and month.');
                    return;
                }

                let newUrl =
                    "{{ route('employee-payroll.index', ['year' => '__year__', 'month' => '__month__']) }}"
                    .replace('__year__', year)
                    .replace('__month__', month);

                window.history.pushState({}, '', newUrl);

                payrollTable.ajax.reload();
            });

            /* $('#searchBtn').on('click', function() {
                 payrollTable.ajax.reload();
             });
              $('#date').on('change', function() {
                 payrollTable.ajax.reload();
             }); */

            $('#bulkDownloadBtn').on('click', function() {
                var year = $('#year').val();
                var month = $('#month').val();

                if (!year || !month) {
                    alert('Please select both year and month before downloading.');
                    return;
                }

                var url = "{{ route('employee-payroll.bulk-download-pdf') }}";
                var fullUrl = `${url}?year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}`;

                window.location.href = fullUrl;
            });

            $('#exportBtn').on('click', function() {
                const year = $('#year').val();
                const month = $('#month').val();

                if (!year || !month) {
                    alert('Please select both year and month.');
                    return;
                }

                const exportUrl = `{{ route('employee-payroll-export.csv') }}?year=${year}&month=${month}`;
                window.location.href = exportUrl;
            });

            $('#resetBtn').on('click', function() {
                const defaultMonth = "{{ now()->subMonth()->month }}";
                const defaultYear = "{{ now()->year }}";

                $('#month').val(defaultMonth).trigger('change');
                $('#year').val(defaultYear).trigger('change');

                const baseUrl = "{{ route('employee-payroll.index') }}";
                window.history.pushState({}, '', baseUrl);

                $('#payroll-list').DataTable().ajax.reload();
            });

        });
    </script>
@endsection
