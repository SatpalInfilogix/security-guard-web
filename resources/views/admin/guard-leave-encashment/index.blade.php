@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Guard Leave Encashment</h4>

                        <div class="page-title-right">
                            <a href="{{ route('guard-leave-encashment.create') }}" class="btn btn-primary">Add New Leave
                                Encashment</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->
            <!-- 🔍 Guard Filter with Search & Reset -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" action="{{ route('guard-leave-encashment.index') }}">
                        <div class="input-group">
                            <select name="guard_id" class="form-control guardselect2">
                                <option value="">-- Select Guard --</option>
                                @foreach ($guards as $guard)
                                    <option value="{{ $guard->id }}"
                                        {{ request('guard_id') == $guard->id ? 'selected' : '' }}>
                                        {{ $guard->first_name }} {{ $guard->surname }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-md btn-primary mx-1">Search</button>
                            <a href="{{ route('guard-leave-encashment.index') }}" class="btn btn-secondary">Reset</a>
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
                            <table id="datatable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Guard Name</th>
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
                                            <td>{{ $encashment->guardUser->first_name ?? '' }}
                                                {{ $encashment->guardUser->surname ?? '' }}</td>
                                            <td>{{ $encashment->pending_leaves }}</td>
                                            <td>{{ $encashment->encash_leaves }}</td>
                                            <td>{{ $encashment->created_at->format('Y-m-d') }}</td>
                                            <td>
                                                <a href="{{ route('guard-leave-encashment.edit', $encashment->id) }}"
                                                    class="btn btn-sm btn-info">Edit</a>
                                                <button type="button" data-source="Guard Leave Encashment"
                                                    data-endpoint="{{ route('guard-leave-encashment.destroy', $encashment->id) }}"
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
    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.guardselect2').select2({
                placeholder: "Select Guard",
                allowClear: true
            });
        });
    </script>
@endpush
