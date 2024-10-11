<div class="row mb-2">
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="guard_type" value="{{ $rateMaster->guard_type ?? '' }}" label="Guard Type" placeholder="Enter Guard Type" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input class="rate-calculate" name="regular_rate" value="{{ $rateMaster->regular_rate ?? '' }}" label="Regular Rate" placeholder="Enter your Rate" type="number"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input class="rate-calculate" name="laundry_allowance" value="{{ $rateMaster->laundry_allowance ?? '' }}" label="Laundry Allowance" placeholder="Enter Laundry Allowance" type="number"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input class="rate-calculate" name="canine_premium" value="{{ $rateMaster->canine_premium ?? '' }}" label="Canine Premium" placeholder="Enter Canine Premium" type="number"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input class="rate-calculate" name="fire_arm_premium" value="{{ $rateMaster->fire_arm_premium ?? '' }}" label="Fire Arm Premium" placeholder="Enter Laundry Allowance" type="number"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="gross_hourly_rate" value="{{ $rateMaster->gross_hourly_rate ?? '' }}" label="Gross Hourly Rate" type="number" readonly/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="normal_rate" value="{{ $rateMaster->gross_hourly_rate ?? '' }}" label="Normal Rate (up to 40 Hours)" type="number" readonly/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="overtime_rate" value="{{ $rateMaster->overtime_rate ?? '' }}" label="Overtime (exceeding 40 hours)"  type="number" readonly/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="holiday_rate" value="{{ $rateMaster->holiday_rate ?? '' }}" label="Holiday Rate" type="number" readonly/>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>