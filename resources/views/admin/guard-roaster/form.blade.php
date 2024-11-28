<div class="row mb-2">
    
    <div class="col-md-4">
        <div class="mb-3">
            <label for="guard_id">Guard<span class="text-danger">*</span></label>
            <select name="guard_id" id="guard_id" class="form-control{{ $errors->has('guard_id') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Guard</option>
                @foreach($securityGuards as $securityGuard)
                    <option value="{{ $securityGuard->id }}" @selected(isset($guardRoaster->guard_id) && $guardRoaster->guard_id == $securityGuard->id)>
                        {{ $securityGuard->first_name .' '.$securityGuard->sure_name }}
                    </option>
                @endforeach
            </select>
            @error('guard_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="client_id">Client<span class="text-danger">*</span></label>
            <select name="client_id" id="client_id" class="form-control{{ $errors->has('client_id') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(isset($guardRoaster->client_id) && $guardRoaster->client_id == $client->id)>
                        {{ $client->client_name }}
                    </option>
                @endforeach
            </select>
            @error('client_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="client_site_id">Client Site<span class="text-danger">*</span></label>
            <select name="client_site_id" id="client_site_id" class="form-control{{ $errors->has('client_site_id') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Client Site</option>
                @if(isset($clientSites))
                    @foreach($clientSites as $clientSite)
                        <option value="{{ $clientSite->id }}" @selected(isset($guardRoaster->client_site_id) && $guardRoaster->client_site_id == $clientSite->id)>
                            {{ $clientSite->location_code }}
                        </option>
                    @endforeach
                @endif
            </select>
            @error('client_site_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <x-form-input name="date" id="date" value="{{ old('date', $guardRoaster->date ?? '') }}" label="Date" placeholder="Enter your Date" class="date-picker-guard" type="text"/>
        <div id="holiday-name" class="mt-2 text-danger" style="display:none;"></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input type="text" id="start_time" name="start_time" value="{{ old('start_time', $guardRoaster->start_time ?? '') }}" label="Start Time" placeholder="Enter Start Time" class="time-picker-guard" type="text"/>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input type="text" id="end_time" name="end_time" value="{{ old('end_time', $guardRoaster->end_time ?? '') }}" label="End Time" placeholder="Enter End Time" class="time-picker-guard" type="text"/>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>
<x-include-plugins :plugins="['chosen', 'datePicker', 'time']"></x-include-plugins>
<script>
     $(function(){
        $('#guard_id').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Guard'
        });
        $('#client_id').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Client'
        });
     });

    $(document).ready(function() {
        var assignedDates = [];
        var holidayDates = [];
        var leaveDates = [];
        var selectedDate = "{{ old('date', $guardRoaster->date ?? '') }}";
        var selectedGuardId = "{{ old('guard_id', $guardRoaster->guard_id ?? '') }}";

        if (selectedGuardId) {
            fetchAssignedDates(selectedGuardId);
        }

        fetchPublicHolidays();
        fetchLeaves();

        $('#client_id').change(function() {
            const clientId = $(this).val();
            const clientSiteSelect = $('#client_site_id');

            clientSiteSelect.html('<option value="" disabled selected>Select Client Site</option>');
            clientSiteSelect.chosen("destroy"); // Destroy the previous instance to avoid issues

            if (clientId) {
                $.ajax({
                    url: '/get-client-sites/' + clientId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.length > 0) {
                            data.forEach(function(item) {
                                if (item) {
                                    const option = new Option(item.location_code, item.id);
                                    clientSiteSelect.append(option);
                                }
                            });
                        }

                        clientSiteSelect.chosen({
                            width: '100%',
                            placeholder_text_multiple: 'Select Client Site'
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching client sites:', error);
                    }
                });
            } else {
                clientSiteSelect.chosen("destroy").html('<option value="" disabled selected>Select Client Site</option>');
                clientSiteSelect.chosen({
                    width: '100%',
                    placeholder_text_multiple: 'Select Client Site'
                });
            }
        });

        $('#guard_id').change(function() {
            const guardId = $(this).val();
            if (guardId) {
                $('#date').val('');  // Clear the date field when a new guard is selected
                fetchAssignedDates(guardId);
                fetchLeaves(guardId);
            } else {
                initDatePicker(assignedDates, holidayDates, leaveDates); // Reset date picker with no disabled dates
            }
        });

        function fetchAssignedDates(guardId) {
            $.ajax({
                url: '/get-assigned-dates/' + guardId,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (Array.isArray(data) && data.length) {
                        assignedDates = data;
                    }
                    initDatePicker(assignedDates, holidayDates, leaveDates); // Initialize date picker with assigned dates
                },
            });
        }

         // Fetch public holidays
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
                    initDatePicker(assignedDates, holidayDates, leaveDates);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching public holidays:', error);
                }
            });
        }

       // Fetch leaves for a specific guard
        function fetchLeaves(guardId) {
            $.ajax({
                url: '/get-leaves/' + guardId,  // Ensure this URL matches your backend route
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (Array.isArray(data) && data.length) {
                        leaveDates = data.map(leave => ({
                            date: moment(leave.date).format('YYYY-MM-DD'),  // Format date as YYYY-MM-DD
                            status: leave.status
                        }));
                    } else {
                        leaveDates = [];
                    }

                    initDatePicker(assignedDates, holidayDates, leaveDates);
                },
                error: function(xhr, status, error) {
                    alert('There was an error fetching leave data. Please try again later.');
                }
            });
        }

        function initDatePicker(assignedDates, holidayDates, leaveDates) {
            $('.date-picker-guard').flatpickr().destroy();

            $('.date-picker-guard').flatpickr({
                dateFormat: "Y-m-d",         // Set date format
                minDate: "today",            // Optionally disable past dates
                defaultDate: selectedDate ? selectedDate : null,  // Set default date if editing
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const dayDate = new Date($(dayElem).attr('aria-label'));
                    const formattedDate = moment(dayDate).format('YYYY-MM-DD');
                    const holiday = holidayDates.find(holiday => holiday.date === formattedDate);
                    if (holiday) {
                        $(dayElem).attr('title', 'H').addClass('holiday');  // Add custom 'holiday' class
                        const holidayLabel = $('<div></div>', { class: 'holiday-label', text: 'H' });
                        $(dayElem).append(holidayLabel);
                    }

                    const leave = leaveDates.find(leave => leave.date === formattedDate);
                    if (leave) {
                        const leaveTitle = leave.status === 'Approved' ? 'Approved Leave' : 'Pending Leave';
                        $(dayElem).attr('title', leaveTitle);
                        const leaveLabel = $('<div></div>', { class: 'leave-label', text: 'L' });

                        if (leave.status === 'Approved') {
                            leaveLabel.css('color', 'red');
                        } else if (leave.status === 'Pending') {
                            leaveLabel.css('color', '#f39c12');
                        }

                        $(dayElem).append(leaveLabel);
                    }
                }
            });
        }

        $('#date').change(function() {
            const selectedDate = $(this).val().trim();  // Get and trim the value from the date input field
            $('#holiday-name').hide();
            const formattedSelectedDate = moment(selectedDate).format('YYYY-MM-DD');  // Using moment.js to format
            const holiday = holidayDates.find(holiday => {
                return holiday.date === formattedSelectedDate;
            });

            if (holiday) {
                $('#holiday-name').text(`Public Holiday: ${holiday.name}`).show();
            }
        });
    });
    </script>

