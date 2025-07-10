<div class="row mb-2">

    {{-- Guard dropdown --}}
    <div class="col-md-4">
        <div class="mb-3">
            <label for="guard_id">Guard <span class="text-danger">*</span></label>
            <select name="guard_id" id="guard_id" class="form-control{{ $errors->has('guard_id') ? ' is-invalid' : '' }}">
                <option value="" disabled {{ old('guard_id', $leave->guard_id ?? '') == '' ? 'selected' : '' }}>Select Guard</option>
                @foreach ($securityGuards as $securityGuard)
                    <option value="{{ $securityGuard->id }}"
                        @selected(old('guard_id', $leave->guard_id ?? '') == $securityGuard->id)>
                        {{ $securityGuard->first_name . ' ' . $securityGuard->surname }}
                    </option>
                @endforeach
            </select>
            @error('guard_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Start Date --}}
    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input
                id="start_date"
                name="start_date"
                value="{{ old('start_date', $leave->start_date ?? '') }}"
                label="Start Date"
                placeholder="Enter Start Date"
                class="datePicker-leave"
                type="text"
                required="true" />
        </div>
    </div>

    {{-- End Date --}}
    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input
                id="end_date"
                name="end_date"
                value="{{ old('end_date', $leave->end_date ?? '') }}"
                label="End Date"
                placeholder="Enter End Date"
                class="datePicker-leave"
                type="text" />
        </div>
    </div>

    {{-- Reason --}}
    <div class="col-md-4 mb-3">
        @php
            $reasons = ['Sick Leave', 'Marrage Leave', 'Vacation Leave', 'Personal Leave', 'Other Leave'];
        @endphp
        <label for="reason">Reason</label>
        <select name="reason" id="reason" class="form-control">
            <option value="" selected disabled>Select Reason</option>
            @foreach ($reasons as $reason)
                <option value="{{ $reason }}"
                    @selected(old('reason', $leave->reason ?? '') == $reason)>
                    {{ $reason }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Actual Start Date --}}
    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input
                id="actual_start_date"
                name="actual_start_date"
                value="{{ old('actual_start_date', $leave->actual_start_date ?? '') }}"
                label="Actual Start Date"
                placeholder="Enter Actual Start Date"
                class="datePicker-leave"
                type="text" />
        </div>
    </div>

    {{-- Actual End Date --}}
    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input
                id="actual_end_date"
                name="actual_end_date"
                value="{{ old('actual_end_date', $leave->actual_end_date ?? '') }}"
                label="Actual End Date"
                placeholder="Enter Actual End Date"
                class="datePicker-leave"
                type="text" />
        </div>
    </div>

    {{-- Description --}}
    <div class="col-md-4 mb-3">
        <label for="description">Description</label>
        <textarea name="description" id="description" class="form-control">{{ old('description', $leave->description ?? '') }}</textarea>
    </div>

</div>

{{-- Submit Button --}}
<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

{{-- Include date picker plugin --}}
<x-include-plugins :plugins="['datePicker']" />
