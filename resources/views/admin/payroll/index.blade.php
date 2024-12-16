@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Payroll</h4>

                        <div class="page-title-right">
                            <div class="d-inline-block ">
                                <form method="GET" id="attendance-form">
                                    <label for="flat" class="mr-2">Date</label>
                                    <input type="text" id="date" name="date" class="form-control datePicker" value="" placeholder="Select Date Range" autocomplete="off">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <x-error-message :message="$errors->first('message')" />
                    <x-success-message :message="session('success')" />

                    <div class="card">
                        <div class="card-body">
                            <table id="payroll-list" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Guard</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Normal Hours</th>
                                    <th>Overtime Hours</th>
                                    <th>Public Holiday Hours</th>
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

    <x-include-plugins :plugins="['datePicker','dataTable']"></x-include-plugins>

    <script>
        $(document).ready(function() {
            let payrollTable = $('#payroll-list').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('get-payroll-list') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                        d.date = $('#date').val();
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
                    { data: 'user.first_name' },
                    { data: 'start_date' },
                    { data: 'end_date' },
                    { data: 'normal_hours' },
                    { data: 'overtime' },
                    { data: 'public_holidays' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            var actions = '<div class="action-buttons">';
                            actions += `<a class="btn btn-outline-secondary btn-sm edit" href="{{ url('admin/payrolls') }}/${row.id}">`;
                            actions += '<i class="fas fa-eye"></i>';
                            actions += '</a>';
                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[0, 'asc']]
            });

            $('#date').on('change', function() {
                payrollTable.ajax.reload();
            });
        });
    </script>
@endsection