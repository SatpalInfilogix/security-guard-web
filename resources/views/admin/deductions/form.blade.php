<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="guard_id">Guard<span class="text-danger">*</span></label>
            <select name="guard_id" id="guard_id" class="form-control{{ $errors->has('guard_id') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Guard</option>
                @foreach($securityGuards as $securityGuard)
                    <option value="{{ $securityGuard->id }}" @selected(isset($deduction->guard_id) && $guardRoaster->guard_id == $securityGuard->id)>
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
            <label for="type">Type<span class="text-danger">*</span></label>
            <select name="type" id="type" class="form-control{{ $errors->has('type') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Type</option>
                @php
                    $types = ['Staff Loan', 'Salary Advance', 'Medical Ins', 'PSRA', 'Bank Loan', 'Approved Pension', 'Other deduction'];    
                @endphp
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(isset($deduction->type) && $deduction->type == $type)>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="amount" value="{{ old('amount', $deduction->amount ?? '') }}" label="Amount"
                placeholder="Amount" type="number"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="no_of_payroll" value="{{ old('no_of_payroll', $deduction->no_of_payroll ?? '') }}" label="No Of Payroll"
                placeholder="No Of Payroll" type="number"/>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <x-form-input name="start_date" id="start_date" value="{{ old('start_date', $deduction->start_date ?? '') }}" label="Start Date" placeholder="Enter your Start Date" class="date_of_separation" type="text"/>
    </div>
    <div class="col-md-4 mb-3">
        <x-form-input name="end_date" value="{{ old('end_date', $deduction->end_date ?? '') }}" label="End Date" placeholder="Enter your End Date" class="date-picker-guard" type="text"/>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<x-include-plugins :plugins="['datePicker']"></x-include-plugins>

<script>
$(document).ready(function() {
    $('#start_date').on('change', function() {
        triggerEndDateCalculation();
    });

    $('#no_of_payroll').on('keyup', function() {
        triggerEndDateCalculation();
    });

    function triggerEndDateCalculation() {
        var startDate = $('#start_date').val();
        var noOfPayrolls = $('#no_of_payroll').val();

        if (startDate) {
            $.ajax({
                url: '/get-end-date',
                method: 'GET',
                data: {
                    start_date: startDate,
                    no_of_payroll: noOfPayrolls
                },
                success: function(response) {
                    if (response.end_date) {
                        $('#end_date').val(response.end_date);
                    } else {
                        alert('End date could not be calculated.');
                    }
                },
            });
        }
    }
});


</script>
