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
                                    <input type="text" id="flat" name="date_range" class="form-control" 
                                         value="{{ request('date_range', isset($fortnight) ? \Carbon\Carbon::parse($fortnight->start_date)->format('Y-m-d') . ' to ' . \Carbon\Carbon::parse($fortnight->end_date)->format('Y-m-d') : '') }}">
                                    {{-- <input type="text" name="date_range" class="form-control" id="date-range-picker" 
                                           value="{{ request('date_range', isset($fortnight) ? \Carbon\Carbon::parse($fortnight->start_date)->format('Y-m-d') . ' to ' . \Carbon\Carbon::parse($fortnight->end_date)->format('Y-m-d') : '') }}"> --}}
                                </form>
                            </div>
                            
                            <a href="{{ route('attendance-list.download', ['date_range' => request('date_range')]) }}" class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Attendance Report Download</a>
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
                                    <th>Total Hours</th>
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
                                    <td>@if($attendance->in_time) {{ \Carbon\Carbon::parse($attendance->in_time)->format('d-m-Y h:i A') }} @else N/A @endif</td>
                                    <td>@if($attendance->out_time) {{ \Carbon\Carbon::parse($attendance->out_time)->format('d-m-Y h:i A') }} @else N/A @endif</td>
                                    <td>{{ $attendance->total_hours }}</td>
                                    @canany(['edit attendance', 'delete attendance'])
                                    <td class="action-buttons">
                                        @if(Auth::user()->can('edit attendance'))
                                        <a href="{{ route('attendance.edit', $attendance->id)}}" class="btn btn-primary waves-effect waves-light btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        @endif
                                        @if(Auth::user()->can('delete attendance'))
                                        <button data-source="Attendance" data-endpoint="{{ route('attendance.destroy', $attendance->id) }}"
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
                </div>
            </div>

        </div>
    </div>
    <x-include-plugins :plugins="['dataTable', 'dateRange']"></x-include-plugins>
    <script type="text/javascript">
        $(document).ready(function() {
            var holidayDates = [];

            fetchPublicHolidays();

            function fetchPublicHolidays() {
                $.ajax({
                    url: '/get-public-holidays',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (Array.isArray(data) && data.length) {
                            holidayDates = data.map(holiday => ({
                                date: moment(holiday.date).format('YYYY-MM-DD'),
                                name: holiday.holiday_name
                            }));
                        }
    
                        initFlatpickr();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching public holidays:', error);
                    }
                });
            }

            function initFlatpickr() {
                flatpickr("#flat", {
                    mode: 'range',
                    dateFormat: "Y-m-d",
                    showMonths: 2,
                    monthSelectorType: "static",
                    defaultDate: "{{ request('date_range', isset($fortnight) ? \Carbon\Carbon::parse($fortnight->start_date)->format('Y-m-d') . ' to ' . \Carbon\Carbon::parse($fortnight->end_date)->format('Y-m-d') : '') }}", // Pre-fill the start and end date range
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            $('#attendance-form').submit();
                        } else {
                            console.log("Please select both dates.");
                        }
                    },
                    onDayCreate: function(dObj, dStr, fp, dayElem) {
                        var date = dayElem.dateObj;
                        var dateStr = moment(date).format('YYYY-MM-DD');
                        
                        var holiday = holidayDates.find(holiday => holiday.date === dateStr);
                        if (holiday) {
                            dayElem.classList.add('holiday');
                            dayElem.title = 'Public Holiday: ' + holiday.name; 
                            var holidayLabel = document.createElement('span');
                            holidayLabel.classList.add('holiday-label');
                            holidayLabel.innerHTML = 'H';
                            dayElem.appendChild(holidayLabel);
                        }
                    }
                });
            }
        });
    </script>
@endsection