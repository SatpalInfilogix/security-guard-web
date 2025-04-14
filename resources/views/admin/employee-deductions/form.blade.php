<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="employee_id">Employee<span class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id" class="form-control{{ $errors->has('employee_id') ? ' is-invalid' : '' }}">
                @php
                $selectedEmployeeId = old('employee_id', $deduction->employee_id ?? '');
                @endphp
                <option value="" disabled {{ $selectedEmployeeId == '' ? 'selected' : '' }}>Select Employee</option>
                @foreach($employees as $employee)
                <option value="{{ $employee->id }}" {{ (string)$selectedEmployeeId === (string)$employee->id ? 'selected' : '' }}>
                    {{ '#' . $employee->user_code . ' ' . $employee->first_name . ' ' . $employee->surname }}
                </option>
                @endforeach
            </select>
            @error('employee_id')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="type">Non Stat Deduction<span class="text-danger">*</span></label>
            <select name="type" id="type" class="form-control{{ $errors->has('type') ? ' is-invalid' : '' }}">
                @php
                $types = ['Staff Loan', 'Salary Advance', 'Medical Ins', 'PSRA', 'Garnishment', 'Missing Goods', 'Damaged Goods', 'Bank Loan', 'Approved Pension'];
                $selectedType = old('type', $deduction->type ?? '');
                @endphp
                <option value="" disabled {{ $selectedType == '' ? 'selected' : '' }}>Select Type</option>
                @foreach($types as $type)
                <option value="{{ $type }}" {{ $selectedType == $type ? 'selected' : '' }}>
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
            <x-form-input name="amount" value="{{ old('amount', $deduction->amount ?? '') }}" label="Amount" placeholder="Amount" type="number" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="no_of_payroll" value="{{ old('no_of_payroll', $deduction->no_of_payroll ?? '') }}" label="No Of Deduction" placeholder="No Of Deduction" type="number" />
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <x-form-input name="document_date" id="document_date" value="{{ old('document_date', $deduction->document_date ?? '') }}" label="Document Date" placeholder="Enter your Document Date" class="datepicker" type="text" required="true" />
    </div>

    <div class="col-md-4 mb-3">
        <x-form-input name="start_date" id="start_date" value="{{ old('start_date', $deduction->start_date ?? '') }}" label="Start Date" placeholder="Enter your Start Date" class="date-picker-guard" type="text" required="true" />
    </div>
    <div class="col-md-4 mb-3">
        <x-form-input name="end_date" value="{{ old('end_date', $deduction->end_date ?? '') }}" label="End Date" placeholder="Enter your End Date" class="date-picker-guard" type="text" required="true" />
    </div>
    <div class="col-md-4 mb-3">
        <x-form-input name="employee_document" value="" label="Upload Document" placeholder="Select your document" class="form-control" type="file" />
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
        $('#document_date').on('change', function() {
            triggerEndDateCalculation();
        });

        $('#employee_id').select2({
            placeholder: "Select Employee"
            , allowClear: true
        });

        $('#no_of_payroll').on('keyup', function() {
            triggerEndDateCalculation();
        });

        function triggerEndDateCalculation() {
            var date = $('#document_date').val();
            var noOfPayrolls = $('#no_of_payroll').val();

            if (date) {
                $.ajax({
                    url: '/get-employee-end-date'
                    , method: 'GET'
                    , data: {
                        date: date
                        , no_of_payroll: noOfPayrolls
                    }
                    , success: function(response) {
                        if (response.end_date) {
                            $('#start_date').val(response.start_date);
                            $('#end_date').val(response.end_date);
                        } else {
                            alert('End date could not be calculated.');
                        }
                    }
                , });
            }
        }
    });

</script>
