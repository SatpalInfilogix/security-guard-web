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
                            <a href="{{ route('public-holidays.create') }}" class="btn btn-primary">Add New Public Holiday</a>
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
                                    <th>Holiday Name</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($publicHolidays as $key => $publicHoliday)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>{{ $publicHoliday->holiday_name }}</td>
                                    <td>{{ $publicHoliday->date}}</td>
                                    <td class="action-buttons">
                                        <a href="{{ route('public-holidays.edit', $publicHoliday->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
                                        <button data-source="Public Holiday" data-endpoint="{{ route('public-holidays.destroy', $publicHoliday->id)}}"
                                            class="delete-btn btn btn-outline-secondary btn-sm edit">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
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
@endsection