@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Public Holidays</h4>

                        <div class="page-title-right">
                            @if (Auth::user()->can('create public holiday'))
                                <a href="{{ route('public-holidays.create') }}" class="btn btn-primary">Add New Public
                                    Holiday</a>
                            @endif
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
                            <table id="public-holidays" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Holiday Name</th>
                                        <th>Date</th>
                                        @canany(['edit public holiday', 'delete public holiday'])
                                            <th>Action</th>
                                        @endcanany
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($publicHolidays as $key => $publicHoliday)
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ $publicHoliday->holiday_name }}</td>
                                            <td>
                                                @if ($publicHoliday->date)
                                                    {{ \Carbon\Carbon::parse($publicHoliday->date)->format('d-m-Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            @canany(['edit public holiday', 'delete public holiday'])
                                                <td class="action-buttons">
                                                    @if (Auth::user()->can('edit public holiday'))
                                                        <a href="{{ route('public-holidays.edit', [
                                                            'public_holiday' => $publicHoliday->id,
                                                            'page' => request()->input('page', 1),
                                                            'year' => request('year'),
                                                        ]) }}"
                                                            class="btn btn-primary waves-effect waves-light btn-sm edit">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                    @endif
                                                    @if (Auth::user()->can('delete public holiday'))
                                                        <button data-source="Public Holiday"
                                                            data-endpoint="{{ route('public-holidays.destroy', $publicHoliday->id) }}"
                                                            class="delete-btn btn btn-danger waves-effect waves-light btn-sm edit">
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
    <x-include-plugins :plugins="['dataTable']"></x-include-plugins>
    @push('scripts')
        <Script>
            var table = $('#public-holidays').DataTable({
                stateSave: true,
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
            });
        </Script>
    @endpush
@endsection
