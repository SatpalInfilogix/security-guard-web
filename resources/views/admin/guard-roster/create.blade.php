@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 font-size-18">Create a new guard roster</h4>

                        <div class="page-title-right">
                            <a href="{{ route('guard-rosters.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back to Guard Rosters</a>
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
                            <form action="{{ route('guard-rosters.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @include('admin.guard-roster.form')
                            </form>    
                        </div>    
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- container-fluid -->
    </div>
    <script>
     $(document).ready(function() {
        $('#date').change(function() {
            const guardId = $('#guard_id').val();
            const selectedDate = $(this).val().trim();  // Get and trim the selected date
            if (guardId && selectedDate) {
                fillAssignedDateDetails(selectedDate, guardId);  // Fill details based on the date and guard
            } else {
                console.log("Guard or Date is missing");
            }
        });

        function fillAssignedDateDetails(date, guardId) {
            $.ajax({
                url: '/get-guard-roster-details',
                method: 'GET',
                data: { date: date, guard_id: guardId },
                dataType: 'json',
                success: function(data) {
                    console.log(data);
                    if (data) {
                        $('#client_id').val(data.client_id).trigger('chosen:updated'); 

                        var clientSiteSelect = $('#client_site_id');
                        clientSiteSelect.html('<option value="" disabled selected>Select Client Site</option>'); // Clear previous options
                    
                        data.client_sites.forEach(function(clientSite) {
                            const option = new Option(clientSite.location_code, clientSite.id);
                            clientSiteSelect.append(option);
                        });

                        clientSiteSelect.trigger('chosen:updated');

                        if (data.client_site_id) {
                            $('#client_site_id').val(data.client_site_id).trigger('chosen:updated');
                        } else {
                            clientSiteSelect.val('').trigger('chosen:updated');
                        }

                        $('#start_time').val(moment(data.start_time, 'HH:mm:ss').format('HH:mm'));  
                        $('#end_time').val(moment(data.end_time, 'HH:mm:ss').format('HH:mm'));  // Auto-fill the end time
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching assigned details:', error);
                }
            });
        }
    });
    </script>
@endsection