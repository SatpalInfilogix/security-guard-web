@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Attendance</h4>
                        <div class="page-title-right">
                            <div class="d-inline-block ">
                                <form method="GET" action="{{ route('attendance.index') }}" id="attendance-form">
                                    <input type="text" name="date_range" class="form-control" id="date-range-picker" 
                                           value="{{ request('date_range', isset($fortnight) ? \Carbon\Carbon::parse($fortnight->start_date)->format('Y-m-d') . ' - ' . \Carbon\Carbon::parse($fortnight->end_date)->format('Y-m-d') : '') }}">
                                </form>
                            </div>
                            
                            <a href="{{ route('attendance-list.download', ['date_range' => request('date_range')]) }}" class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Attendance Download</a>
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
                            <table id="datatable" class="table table-bordered dt-responsive  nowrap w-100">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Firstname</th>
                                    <th>Middlename</th>
                                    <th>Surname</th>
                                    <th>Punch In</th>
                                    <th>Punch Out</th>
                                    @canany(['edit attendance', 'delete attendance'])
                                    <th>Action</th>
                                    @endcanany
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($attendances as $key => $attendance)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $attendance->user->first_name }}</td>
                                    <td>{{ $attendance->user->middle_name }}</td>
                                    <td>{{ $attendance->user->surname}}</td>
                                    <td>{{ $attendance->in_time }}</td>
                                    <td>{{ $attendance->out_time }}</td>
                                    @canany(['edit attendance', 'delete attendance'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit attendance'))
                                        <a href="{{ route('attendance.edit', $attendance->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete attendance'))
                                        <button data-source="Attendance" data-endpoint="{{ route('attendance.destroy', $attendance->id) }}"
                                            class="delete-btn btn btn-outline-secondary btn-sm edit">
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
    <x-include-plugins :plugins="['dataTable', 'dateRange']"></x-include-plugins>
    <script type="text/javascript">
        $(document).ready(function() {
            var dateRange = $('#date-range-picker').val(); 
            $('#date-range-picker').daterangepicker({
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                singleDatePicker: false,
                showDropdowns: false,
                autoApply: true,
                alwaysShowCalendars: true,
            });

            $('#date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('#attendance-form').submit();
            });

            $('#date-range-picker').on('change', function() {
                $('#attendance-form').submit();
            });
        });
    </script>
@endsection