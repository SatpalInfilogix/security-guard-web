@extends('layouts.app')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Employee Deduction</h4>

                    <div class="page-title-right">
                        <a href="#" id="exportBtn" class="btn btn-primary"><i class="bx bx-export"></i>Employee Deduction Export</a>
                        {{-- <a href="{{ route('export.deductions') }}" class="btn btn-primary"><i class="bx bx-export"></i> Deduction Export</a> --}}
                        @can('create nst deduction')
                        <a href="{{ route('employee-deductions.create') }}" class="btn btn-primary">Add New Employee Deduction</a>
                        @endcan
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
                            <input type="text" name="search_name" class="form-control" placeholder="Search by Name" value="{{ request('search_name') }}" id="search_name">
                        </div>
                        <div class="col-md-3">
                            <select name="search_type" id="search_type" class="form-control{{ $errors->has('type') ? ' is-invalid' : '' }}">
                                <option value="" disabled selected>Select Type</option>
                                @php
                                $types = ['Staff Loan', 'Salary Advance', 'Medical Ins', 'PSRA', 'Garnishment', 'Missing Goods', 'Damaged Goods', 'Bank Loan', 'Approved Pension'];
                                @endphp
                                @foreach($types as $type)
                                <option value="{{ $type }}" @selected(isset($deduction->type) && $deduction->type == $type)>
                                    {{ $type }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search_document_date" class="form-control date_of_separation" placeholder="Search by Document Date" value="{{ request('search_document_date') }}" id="search_document_date">
                        </div>

                        <div class="col-md-2">
                            <input type="text" name="search_period_date" class="form-control date_of_separation" placeholder="Search by Period Date" value="{{ request('search_period_date') }}" id="search_period_date">
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
                <x-error-message :message="$errors->first('message')" />
                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif
                <x-success-message :message="session('success')" />

                <div class="card">
                    <div class="card-body">
                        <table id="deduction-list" class="table table-bordered dt-responsive  nowrap w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee No</th>
                                    <th>Employee Name</th>
                                    <th>Non Stat Deduction</th>
                                    <th>Amount</th>
                                    <th>No Of Deduction</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
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
<x-include-plugins :plugins="['dataTable', 'datePicker']"></x-include-plugins>
<script>
    $(document).ready(function() {
        let deductionTable = $('#deduction-list').DataTable({
            processing: true
            , serverSide: true
            , ajax: {
                url: "{{ route('get-employee-deductions-list') }}"
                , type: "POST"
                , data: function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.search_name = $('#search_name').val();
                    d.search_type = $('#search_type').val();
                    d.search_document_date = $('#search_document_date').val();
                    d.search_period_date = $('#search_period_date').val();
                    return d;
                }
                , dataSrc: function(json) {
                    return json.data || [];
                }
            }
            , columns: [{
                    data: null
                    , render: function(data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                }
                , {
                    data: 'user.user_code'
                }
                , {
                    data: 'user.full_name'
                    , name: 'user.full_name'
                }
                , {
                    data: 'type'
                }
                , {
                    data: 'formatted_amount'
                }
                , {
                    data: 'no_of_payroll'
                }
                , {
                    data: 'start_date'
                    , name: 'start_date'
                    , render: function(data) {
                        return data ? moment(data).format('DD-MM-YYYY') : 'N/A';
                    }
                }
                , {
                    data: 'end_date'
                    , name: 'end_date'
                    , render: function(data) {
                        return data ? moment(data).format('DD-MM-YYYY') : 'N/A';
                    }
                }
                , {
                    data: 'id'
                    , name: 'action'
                    , orderable: false
                    , searchable: false
                    , render: function(data, type, row) {
                        let editUrl = "{{ route('employee-deductions.edit', ':id') }}".replace(':id', row.id);
                        let deleteUrl = "{{ route('employee-deductions.destroy', ':id') }}".replace(':id', row.id);

                        let buttons = '';

                        @can('edit nst deduction')
                        buttons += `<a href="${editUrl}" class="btn btn-sm btn-primary me-1"><i class="bx bx-edit"></i></a>`;
                        @endcan

                        @can('delete nst deduction')
                        buttons += `
    <button data-endpoint="${deleteUrl}" data-source="Employee Deduction"
        class="delete-btn btn btn-danger waves-effect waves-light btn-sm">
        <i class="fas fa-trash-alt"></i>
    </button>
`;
                        @endcan

                        return buttons;
                    }
                }
            ]
            , paging: true
            , pageLength: 10
            , lengthMenu: [10, 25, 50, 100]
            , order: [
                [0, 'asc']
            ]
        });

        $('#exportBtn').on('click', function(e) {
            let searchName = $('#search_name').val();
            let searchType = $('#search_type').val() ? $('#search_type').val() : '';
            let searchDocumentDate = $('#search_document_date').val();
            let searchPeriodDate = $('#search_period_date').val();

            let exportUrl = "{{ route('export.employee-deductions') }}";
            exportUrl += `?search_name=${encodeURIComponent(searchName)}`;
            exportUrl += `&search_type=${encodeURIComponent(searchType)}`;
            exportUrl += `&search_document_date=${encodeURIComponent(searchDocumentDate)}`;
            exportUrl += `&search_period_date=${encodeURIComponent(searchPeriodDate)}`;

            window.location.href = exportUrl;
        });

        $('#searchBtn').on('click', function() {
            deductionTable.ajax.reload();
        });
        $(document).on('click', '.delete-btn', function() {
            let endpoint = $(this).data('endpoint');
            let source = $(this).data('source') || 'this record';

            Swal.fire({
                title: `Delete ${source}?`
                , text: "This action cannot be undone."
                , icon: 'warning'
                , showCancelButton: true
                , confirmButtonColor: '#3085d6'
                , cancelButtonColor: '#d33'
                , confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: endpoint
                        , type: 'POST'
                        , data: {
                            _token: '{{ csrf_token() }}'
                            , _method: 'DELETE'
                        }
                        , success: function(response) {
                            Swal.fire(
                                'Deleted!'
                                , `${source} has been deleted.`
                                , 'success'
                            );
                            $('#deduction-list').DataTable().ajax.reload();
                        }
                        , error: function(xhr) {
                            Swal.fire(
                                'Failed!'
                                , 'Something went wrong.'
                                , 'error'
                            );
                        }
                    });
                }
            });
        });
    });

</script>
@endsection
