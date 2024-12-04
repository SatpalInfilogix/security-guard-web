@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Guard Roster</h4>

                        <div class="page-title-right">
                            <button id="toggleGridView" class="btn btn-primary primary-btn btn-md me-1 d-none">
                                <i class="bx bx-grid"></i> Grid View
                            </button>
                            <button id="toggleListView" class="btn btn-primary primary-btn btn-md me-1">
                                <i class="bx bx-list-ul"></i> List View
                            </button>

                            <a href="{{ route('export.csv') }}" class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Guard/Client Ids</a>
                            <a href="{{ url('download-guard-roaster-sample') }}"
                                class="btn btn-primary primary-btn btn-md me-1"><i class="bx bx-download"></i> Guard Roster Sample File</a>
                            @canany(['create guard roaster'])
                                <div class="d-inline-block ">
                                    <form id="importForm" action="{{ route('import.guard-roaster') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <label for="fileInput" class="btn btn-primary primary-btn btn-md mb-0">
                                            <i class="bx bx-cloud-download"></i> Import Guard Roster
                                            <input type="file" id="fileInput" name="file" accept=".csv, .xlsx" style="display:none;">
                                        </label>
                                    </form>
                                </div>
                                <a href="{{ route('guard-rosters.create') }}" class="btn btn-primary">Add New Guard Roster</a>
                            @endcanany
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

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
                    
                    <div class="grid-view">
                        @include('admin.guard-roster.grid-view')
                    </div>

                    <div class="list-view d-none">
                        @include('admin.guard-roster.list-view')
                    </div>
                </div> 
            </div>
        </div>
    </div>

    <x-include-plugins :plugins="['dataTable', 'import']"></x-include-plugins>
    <script>
        $(document).ready(function() {
            $('#toggleListView').click(function() {
                $('.list-view').removeClass('d-none');
                $('.grid-view').addClass('d-none');

                $('#toggleListView').addClass('d-none');
                $('#toggleGridView').removeClass('d-none');
            });

            $('#toggleGridView').click(function() {
                $('.grid-view').removeClass('d-none');
                $('.list-view').addClass('d-none');

                $('#toggleGridView').addClass('d-none');
                $('#toggleListView').removeClass('d-none');
            });
        });
    </script>
@endsection