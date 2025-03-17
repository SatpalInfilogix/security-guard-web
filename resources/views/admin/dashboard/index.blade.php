@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Security Guards</p>
                                            <h4 class="mb-0">{{$securityGuards}}</h4>
                                        </div>

                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                                <span class="avatar-title">
                                                    <i class="bx bx-copy-alt font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Employees</p>
                                            <h4 class="mb-0">{{$employees}}</h4>
                                        </div>

                                        <div class="flex-shrink-0 align-self-center ">
                                            <div class="avatar-sm rounded-circle bg-primary mini-stat-icon">
                                                <span class="avatar-title rounded-circle bg-primary">
                                                    <i class="bx bx-archive-in font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Clients</p>
                                            <h4 class="mb-0">{{$clients}}</h4>
                                        </div>

                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="avatar-sm rounded-circle bg-primary mini-stat-icon">
                                                <span class="avatar-title rounded-circle bg-primary">
                                                    <i class="bx bx-purchase-tag-alt font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mini-stats-wid">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium">Client Sites</p>
                                            <h4 class="mb-0">{{$clientSites}}</h4>
                                        </div>

                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="avatar-sm rounded-circle bg-primary mini-stat-icon">
                                                <span class="avatar-title rounded-circle bg-primary">
                                                    <i class="bx bx-shape-circle font-size-24"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end row -->
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="GET" id="attendance-form">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="date" class="form-control" id="date" value="{{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }} to {{ \Carbon\Carbon::parse($endDate)->format('Y-m-d') }}" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" id="searchBtn" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="datatable" class="table table-bordered dt-responsive nowrap w-100">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Date</th>
                                                <th>Roster Punch-In/Out</th>
                                                <th>Actual Punch-In/Out</th>
                                                <th>Working Hours</th>
                                                <th>Late Arrival</th>
                                                <th>Early Departure</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($listingData as $key => $data)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $data['user'] }}</td>
                                                    <td>{{ $data['date'] }}</td>
                                                    <td>{{ $data['expected_in'] . ' - ' . $data['expected_out'] }}</td>
                                                    <td>
                                                        @foreach($data['intervals'] as $interval)
                                                            {{ $interval['in_time'] . ' - ' . $interval['out_time'] }}<br>
                                                        @endforeach
                                                    </td>
                                                    <td>{{ $data['total_working_hours'] }}</td>
                                                    <td>{{ $data['punched_in_late'] }}</td>
                                                    <td>{{ $data['punched_out_early'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- container-fluid -->
        </div>
    </div>
    <x-include-plugins :plugins="['dataTable', 'dateRange']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            flatpickr("#date", {
                mode: 'range',
                showMonths: 2,
            });

            $('#searchBtn').on('click', function() {
                $('#attendance-form').submit();
            });
        });
    </script>
    @endsection
