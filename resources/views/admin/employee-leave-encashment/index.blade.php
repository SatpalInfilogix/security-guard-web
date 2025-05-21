@extends('layouts.app')

@section('content')
<div class="page-content">
    <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between p-1 mb-1">
                        <h4 class="mb-sm-0 font-size-18">Employee Leave Encashment</h4>

                    <div class="page-title-right d-flex gap-2">
                        {{-- Import Leave Encashment Form --}}
                        <a href="{{ url('download-leave-encashment-sample') }}"
                            class="btn btn-primary btn-md">
                            <i class="bx bx-download"></i> Download Sample File
                        </a>
                        <form id="importForm" action="{{ route('employee-leave-encashment.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label for="fileInput" class="btn btn-primary mb-0">
                                <i class="bx bx-cloud-upload"></i> Import Leave Encashment
                                <input type="file" id="fileInput" name="import_file" accept=".csv, .xlsx" style="display: none;">
                            </label>

                        </form>

                        {{-- Add New Leave Encashment --}}
                        <a href="{{ route('employee-leave-encashment.create') }}" class="btn btn-primary">
                            Add New Leave Encashment
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- 🔍 Employee Filter with Search & Reset -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="{{ route('employee-leave-encashment.index') }}">
                    <div class="input-group">
                        <select name="employee_id" class="form-control select2">
                            <option value="">-- Select Employee --</option>
                            @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}"
                                {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->surname }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-md btn-primary mx-1 ">Search</button>
                        <a href="{{ route('employee-leave-encashment.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Messages -->
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
                                        <th>Employee Name</th>
                                        <th>Pending Leaves</th>
                                        <th>Leaves Encashment</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($encashments as $index => $encashment)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $encashment->employee->first_name ?? '' }}
                                                {{ $encashment->employee->surname ?? '' }}</td>
                                            <td>{{ $encashment->pending_leaves }}</td>
                                            <td>{{ $encashment->encash_leaves }}</td>
                                            <td>{{ $encashment->created_at->format('Y-m-d') }}</td>
                                            <td>
                                                <a href="{{ route('employee-leave-encashment.edit', $encashment->id) }}"
                                                    class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                                <button type="button" data-source="Employee Overtime"
                                                    data-endpoint="{{ route('employee-leave-encashment.destroy', $encashment->id) }}"
                                                    class="delete-btn btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-include-plugins :plugins="['dataTable']" />
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select Employee",
            allowClear: true
        });
        $('#importBtn').on('click', function() {
            $('#fileInput').click();
        });
        $('#fileInput').on('change', function() {
            if (this.files.length > 0) {
                $('#importForm').submit();
            }
        });
    });
</script>
@endpush