<div class="row mb-2">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="employee_id">Employee<span class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id"
                class="form-control{{ $errors->has('employee_id') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" data-name="{{ $employee->first_name }}"
                        @selected(($rateMaster->employee_id ?? '') == $employee->id)>
                        {{ $employee->user_code }}
                    </option>
                @endforeach
            </select>
            @error('employee_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input id="name" name="name" value="{{ $rateMaster->first_name ?? '' }}"
                label="Employee Name" placeholder="Enter Employee Name" required="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input id="gross_salary" name="gross_salary" value="{{ $rateMaster->gross_salary ?? '' }}"
                label="Gross Salary" placeholder="Enter Gross Salary" type="number" step="any" min="0"
                required="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input id="monthly_income" name="monthly_income" value="{{ $rateMaster->monthly_income ?? '' }}"
                label="Monthly Income" placeholder="Monthly Income" type="number" step="any" min="0"
                readonly />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input id="employee_allowance" name="employee_allowance"
                value="{{ $rateMaster->employee_allowance ?? '' }}" label="Employee Allowance"
                placeholder="Enter Allowance" type="number" step="any" min="0" />
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <x-form-input id="daily_income" name="daily_income" value="{{ $rateMaster->daily_income ?? '' }}"
                label="Daily Income" placeholder="Daily Income" type="number" step="any" readonly />
        </div>
    </div>
    <div class="col-md-3">
        <div class="mb-3">
            <x-form-input id="hourly_income" name="hourly_income" value="{{ $rateMaster->hourly_income ?? '' }}"
                label="Hourly Income" placeholder="Hourly Income" type="number" step="any" readonly />
        </div>
    </div>

</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#employee_id').change(function() {
            var selectedOption = $(this).find('option:selected');
            var employeeName = selectedOption.data('name');
            $('#name').val(employeeName);
        });

        if ($('#employee_id').val() != "") {
            var selectedOption = $('#employee_id').find('option:selected');
            var employeeName = selectedOption.data('name');
            $('#name').val(employeeName);
        }

        $('#gross_salary').on('input', function() {
            var grossSalary = parseFloat($(this).val()) || 0;
            var monthlyIncome = grossSalary / 12;
            $('#monthly_income').val(monthlyIncome.toFixed(2));

            var dailyIncome = monthlyIncome / 22;
            var hourlyIncome = dailyIncome / 8;
            $('#daily_income').val(dailyIncome.toFixed(2));
            $('#hourly_income').val(hourlyIncome.toFixed(2));
        });

        if ($('#gross_salary').val() != "") {
            var grossSalary = parseFloat($('#gross_salary').val()) || 0;
            var monthlyIncome = grossSalary / 12;
            $('#monthly_income').val(monthlyIncome.toFixed(2));
            
            var dailyIncome = monthlyIncome / 22;
            var hourlyIncome = dailyIncome / 8;
            $('#daily_income').val(dailyIncome.toFixed(2));
            $('#hourly_income').val(hourlyIncome.toFixed(2));
        }
    });
</script>
