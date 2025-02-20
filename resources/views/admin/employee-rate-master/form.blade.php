<div class="row mb-2">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="employee_id">Employee<span class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id" class="form-control{{ $errors->has('employee_id') ? ' is-invalid' : '' }}">
                <option value="" disabled selected>Select Employee</option>
                @foreach($employees as $employee)
                <option value="{{ $employee->id }}" data-name="{{ $employee->first_name }}" @selected(($rateMaster->employee_id ?? '') == $employee->id)>
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
            <x-form-input id="name" name="name" value="{{ $rateMaster->first_name ?? '' }}" label="Employee Name" placeholder="Enter Employee Name" required="true"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input id="gross_salary" name="gross_salary" value="{{ $rateMaster->gross_salary ?? '' }}" label="Gross Salary" placeholder="Enter Gross Salary" type="number" step="any" min="0" required="true"/>
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

        if($('#employee_id').val() != "") {
            var selectedOption = $('#employee_id').find('option:selected');
            var employeeName = selectedOption.data('name');
            $('#name').val(employeeName);
        }
    });
</script>