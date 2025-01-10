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
    
    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input type="text" id="start_date" name="start_date" value="{{ old('start_date') }}" label="Start Date" placeholder="Enter Start Date" class="datePicker-leave" type="text" required="true"/>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="show-input">
            <x-form-input type="text" id="end_date" name="end_date" value="{{ old('end_date') }}" label="End Date" placeholder="Enter End Date" class="datePicker-leave" type="text"/>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <?php
            $reasons = ['Sick Leave', 'Marrage Leave', 'Vacation Leave', 'Personal Leave', 'Other Leave'];
        ?>
        <label for="reason">Reason</label>
        <select name="reason" id="reason" class="form-control">
            <option value="" selected disabled>Select Reason</option>
            @foreach ($reasons as $reason)
                <option value="{{ $reason }}" {{ old('reason') }}>{{ $reason }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label for="description">Description</label>
        <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
    </div>

</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<x-include-plugins :plugins="['datePicker']"></x-include-plugins>
