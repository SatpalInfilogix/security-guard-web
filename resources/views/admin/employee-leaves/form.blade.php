<div class="row mb-2">
    <div class="col-md-3">
        <div class="mb-3">
            <label for="employee_id">Employee <span class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id"
                class="form-control select2{{ $errors->has('employee_id') ? ' is-invalid' : '' }}">
                <option value="" disabled
                    {{ old('employee_id', $leave->employee_id ?? '') == '' ? 'selected' : '' }}>Select Employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $leave->employee_id ?? '') == $employee->id)>
                        {{ $employee->first_name . ' ' . $employee->surname }}
                    </option>
                @endforeach
            </select>
            @error('employee_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <label for="type">Type <span class="text-danger">*</span></label>
        <select name="type" id="type" class="form-control" required>
            <option value="" disabled>Select Type</option>
            <option value="Full Day" @selected(old('type', $leave->type ?? '') == 'Full Day')>Full Day</option>
            <option value="Half Day" @selected(old('type', $leave->type ?? '') == 'Half Day')>Half Day</option>
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <div class="show-input">
            <x-form-input id="start_date" name="start_date" value="{{ old('start_date', $leave->start_date ?? '') }}"
                label="Start Date" placeholder="Enter Start Date" class="datePicker-leave" type="text"
                required="true" />
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="show-input">
            <x-form-input id="end_date" name="end_date" value="{{ old('end_date', $leave->end_date ?? '') }}"
                label="End Date" placeholder="Enter End Date" class="datePicker-leave" type="text" />
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <label for="reason">Reason</label>
        <select name="reason" id="reason" class="form-control">
            <option value="" disabled>Select Reason</option>
            <option value="Sick Leave" @selected(old('reason', $leave->reason ?? '') == 'Sick Leave')>Sick Leave</option>
            <option value="Maternity Leave" @selected(old('reason', $leave->reason ?? '') == 'Maternity Leave')>Maternity Leave</option>
            <option value="Vacation Leave" @selected(old('reason', $leave->reason ?? '') == 'Vacation Leave')>Vacation Leave</option>
            <option value="Personal Leave" @selected(old('reason', $leave->reason ?? '') == 'Personal Leave')>Personal Leave</option>
            <option value="Other Leave" @selected(old('reason', $leave->reason ?? '') == 'Other Leave')>Other Leave</option>
            <option value="Compassionate leave" @selected(old('reason', $leave->reason ?? '') == 'Compassionate leave')>Compassionate leave</option>
            <option value="Jury leave" @selected(old('reason', $leave->reason ?? '') == 'Jury leave')>Jury leave</option>
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label for="leave_type">Leave Type <span class="text-danger">*</span></label>
        <select name="leave_type" id="leave_type" class="form-control" required>
            <option value="" disabled
                {{ old('leave_type', $leave->leave_type ?? null) == null ? 'selected' : '' }}>
                Select Leave Type
            </option>
            <option value="Sick Leave" @selected(old('leave_type', $leave->leave_type ?? null) == 'Sick Leave')>
                Sick Leave
            </option>
            <option value="Vacation Leave" @selected(old('leave_type', $leave->leave_type ?? null) == 'Vacation Leave')>
                Vacation Leave
            </option>
            <option value="Maternity Leave" @selected(old('leave_type', $leave->leave_type ?? null) == 'Maternity Leave')>
                Maternity Leave
            </option>
        </select>
        @error('leave_type')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <div class="show-input">
            <x-form-input id="actual_start_date" name="actual_start_date"
                value="{{ old('actual_start_date', $leave->actual_start_date ?? '') }}" label="Actual Start Date"
                placeholder="Enter Actual Start Date" class="datePicker-leave" type="text" />
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="show-input">
            <x-form-input id="actual_end_date" name="actual_end_date"
                value="{{ old('actual_end_date', $leave->actual_end_date ?? '') }}" label="Actual End Date"
                placeholder="Enter Actual End Date" class="datePicker-leave" type="text" />
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <label for="description">Description</label>
        <textarea name="description" id="description" class="form-control">{{ old('description', $leave->description ?? '') }}</textarea>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<x-include-plugins :plugins="['datePicker']" />

<script>
    $(document).ready(function() {
        
        $('#employee_id').select2({
            placeholder: 'Select an employee',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#employee_id').parent()
        });

        $('#employee_id').on('change', function() {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });

        $('#type').change(function() {
            if ($(this).val() === 'Half Day') {
                $('#end_date').prop('disabled', true).val('');
            } else {
                $('#end_date').prop('disabled', false);
            }
        }).trigger('change');
    });
</script>
