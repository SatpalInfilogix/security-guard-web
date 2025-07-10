@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Guard Tax Threshold</h4>

                        <div class="page-title-right">
                            {{-- @can('create employee tax threshold') --}}
                                <a href="{{ route('guard-tax-threshold.create') }}" class="btn btn-primary">
                                    Add New Tax Threshold
                                </a>
                            {{-- @endcan --}}
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
                            <table id="datatable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Annual Threshold</th>
                                        <th>Monthly Threshold</th>
                                        <th>Fortnightly Threshold</th>
                                        <th>Effective Date</th>
                                        <th>Created At</th>
                                        {{-- @canany(['edit employee tax threshold', 'delete employee tax threshold']) --}}
                                            <th>Action</th>
                                        {{-- @endcanany --}}
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($thresholds as $key => $threshold)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>₹ {{ number_format($threshold->annual, 2) }}</td>
                                            <td>₹ {{ number_format($threshold->monthly, 2) }}</td>
                                            <td>₹ {{ number_format($threshold->fortnightly, 2) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($threshold->effective_date)->format('d-m-Y') }}</td>
                                            <td>{{ $threshold->created_at->format('d-m-Y') }}</td>
                                            {{-- @canany(['edit employee tax threshold', 'delete employee tax threshold']) --}}
                                                <td class="action-buttons">
                                                    {{-- @can('edit employee tax threshold') --}}
                                                        <a href="{{ route('guard-tax-threshold.edit', $threshold->id) }}"
                                                            class="btn btn-primary waves-effect waves-light btn-sm edit">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                    {{-- @endcan --}}
                                                    {{-- @can('delete employee tax threshold') --}}
                                                        <button data-source="Tax Threshold"
                                                            data-endpoint="{{ route('guard-tax-threshold.destroy', $threshold->id) }}"
                                                            class="delete-btn btn btn-danger waves-effect waves-light btn-sm edit">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    {{-- @endcan --}}
                                                </td>
                                            {{-- @endcanany --}}
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
