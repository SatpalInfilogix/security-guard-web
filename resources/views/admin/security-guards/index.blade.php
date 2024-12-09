@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Security Guards</h4>

                        <div class="page-title-right">
                            <a href="{{ route('security-guards.pdf') }}" class="btn btn-primary"><i class="bx bx-download"></i> Download PDF</a>
                            <a href="{{ route('export.guards') }}" class="btn btn-primary"><i class="bx bx-export"></i> Security Guard Bulk Export</a>
                            <a href="{{ url('download-guard-sample') }}"
                                class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Guard Sample File</a>
                            <div class="d-inline-block me-1">
                                <form id="importForm" action="{{ route('import.security-guard') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label for="fileInput" class="btn btn-primary primary-btn btn-md mb-0">
                                        <i class="bx bx-cloud-download"></i> Import Security Guard
                                        <input type="file" id="fileInput" name="file" accept=".csv, .xlsx" style="display:none;">
                                    </label>
                                </form>
                            </div>
                            @if(Auth::user()->can('create security guards'))
                                <a href="{{ route('security-guards.create') }}" class="btn btn-primary">Add New Security Guard</a>
                            @endif
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
                                <input type="text" name="search_name" class="form-control" placeholder="Search by Name" value="{{ request('search_name') }}" id="search_name">
                                {{-- <select name="search_name" class="form-control" id="search_name">
                                    <option value="">Select Guard</option>
                                    @foreach($securityGuards as $guard)
                                        <option value="{{ $guard->first_name }}" {{ request('search_name') == $guard->first_name ? 'selected' : '' }}>
                                            {{ $guard->first_name }}
                                        </option>
                                    @endforeach
                                </select> --}}
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="search_email" class="form-control" placeholder="Search by Email" value="{{ request('search_email') }}" id="search_email">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="search_phone" class="form-control" placeholder="Search by Phone" value="{{ request('search_phone') }}" id="search_phone">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control" id="is_status">
                                    <option value="">All Status</option>
                                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
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
                            <table id="datatable" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Emp. Code</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Phone number</th>
                                    <th>Status</th>
                                    @canany(['edit security guards', 'delete security guards'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody id="guardTableBody">
                                @foreach($securityGuards as $key => $securityGuard)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $securityGuard->user_code}}</td>
                                    <td>{{ $securityGuard->first_name }}</td>
                                    <td>{{ $securityGuard->middle_name }}</td>
                                    <td>{{ $securityGuard->email }}</td>
                                    <td>{{ $securityGuard->phone_number }}</td>
                                    <td>
                                        @php
                                            $statusOptions = ['Active', 'Inactive', 'Hold'];
                                        @endphp
                                        <select name="guard_status" class="form-control" data-user-id="{{ $securityGuard->id }}">
                                            <option value="" selected disabled>Select Status</option>
                                            @foreach ($statusOptions as $value)
                                                <option value="{{ $value }}" 
                                                    @selected($securityGuard->status === $value) 
                                                    @if ($value === 'Active' && (
                                                            empty($securityGuard->userDocuments->trn) || 
                                                            empty($securityGuard->userDocuments->nis) || 
                                                            empty($securityGuard->userDocuments->birth_certificate) || 
                                                            empty($securityGuard->userDocuments->psra)
                                                        ))
                                                        disabled 
                                                    @endif>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    @canany(['edit security guards', 'delete security guards'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit security guards'))
                                            <a href="{{ route('security-guards.edit', $securityGuard->id)}}" class="btn btn-primary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete security guards'))
                                            <button data-source="Security Guard" data-endpoint="{{ route('security-guards.destroy', $securityGuard->id) }}"
                                                class="delete-btn btn btn-danger btn-sm edit">
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
                </div>
            </div>
        </div>
    </div>
    <x-include-plugins :plugins="['dataTable', 'import']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            function fetchFilteredData() {
                var formData = $('#filterForm').serialize();

                $.ajax({
                    url: "{{ route('security-guards.index') }}",
                    method: "GET",
                    data: formData, 
                    success: function(response) {
                        $('#guardTableBody').html(response.view);
                    },
                    error: function() {
                        alert('Error fetching data');
                    }
                });
            }

            $('#search_name, #search_email, #search_phone').on('keyup', function() {
                fetchFilteredData();
            });

            $('#is_status').on('change', function() {
                fetchFilteredData();
            });

            $('[name="guard_status"]').on('change', function(){
                let status = $(this).val();
                let userId = $(this).attr('data-user-id');
                
                $.ajax({
                    url: "{{ route('users.update-status') }}",
                    method: 'PUT',
                    data: {
                        user_id: userId,
                        status: status,
                        _token: '{{ csrf_token() }}'
                    }
                })
            })
        });
    </script>
@endsection