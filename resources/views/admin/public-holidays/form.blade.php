<div class="row mb-2">
    <div class="col-md-12">
        <div class="mb-3">
            <x-form-input name="holiday_name" value="{{ old('holiday_name', $publicHoliday->holiday_name ?? '') }}" label="Holiday Name" placeholder="Enter your Holiday Name"  required="true" />
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <x-form-input name="date" value="{{ old('date', $publicHoliday->date ?? '' ) }}" class="datepicker" label="Date" placeholder="Enter your Date" type="date" required="true"/>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<x-include-plugins :plugins="['datePicker']"></x-include-plugins>
