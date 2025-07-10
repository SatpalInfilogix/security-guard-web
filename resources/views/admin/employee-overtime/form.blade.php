 <div id="formContainer">
     @if (isset($overtimes->detail) && count($overtimes->detail) > 0)
         @foreach ($overtimes->detail as $index => $overtimeItem)
             <div class="row mb-3 g-2 align-items-end" id="row-{{ $index }}">
                 {{--<input type="hidden" name="ids[]" value="{{ $overtimeItem->id }}">--}}
                 <div class="col-md-2">
                     <label class="form-label">Employee<span class="text-danger">*</span></label>
                     <select class="form-select" disabled>
                         <option value="{{ $overtimeItem->employee_id }}" selected>
                             {{ $overtimes->employee->first_name ?? '' }}
                             {{ $overtimes->employee->surname ?? '' }}
                         </option>
                     </select>
                     <input type="hidden" name="employee_id[]" value="{{ $overtimes->employee_id }}">
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Actual Date</label>
                     <input type="date" class="form-control" name="actual_date[]"
                         value="{{ $overtimeItem->actual_date }}">
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Date<span class="text-danger">*</span></label>
                     <input type="date" class="form-control" name="work_date[]"
                         value="{{ $overtimeItem->work_date }}">
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Rate<span class="text-danger">*</span></label>
                     <input type="number" class="form-control" name="rate[]" value="{{ $overtimeItem->rate }}"
                         readonly>
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Hours<span class="text-danger">*</span></label>
                     <input type="number" class="form-control" name="hours[]" value="{{ $overtimeItem->hours }}" step="0.01">
                 </div>
                 <div class="col-md-2">
                     @if ($loop->first)
                         <button type="button" class="btn btn-primary mt-2" id="addRow">Add More</button>
                     @else
                         <button type="button" class="btn btn-danger removeRow mt-2">Remove</button>
                     @endif
                 </div>
             </div>
         @endforeach
     @else
         <div class="row mb-3 g-2 align-items-end" id="row-0">
             <div class="col-md-2">
                 <label class="form-label">Employee<span class="text-danger">*</span></label>
                 <select class="form-select" name="employee_id[]">
                     <option value="">Select Employee</option>
                     @foreach ($employees as $employee)
                         <option value="{{ $employee->id }}" data-rate="{{ $employee->hourly_income }}">
                             {{ $employee->first_name }} {{ $employee->surname }}
                         </option>
                     @endforeach
                 </select>
             </div>
             <div class="col-md-2">
                 <label class="form-label">Actual Date</label>
                 <input type="date" class="form-control" name="actual_date[]" value="{{ old('actual_date') }}">
             </div>
             <div class="col-md-2">
                 <label class="form-label">Date<span class="text-danger">*</span></label>
                 <input type="date" class="form-control" name="work_date[]" value="{{ old('work_date') }}">
             </div>
             <div class="col-md-2">
                 <label class="form-label">Rate<span class="text-danger">*</span></label>
                 <input type="text" class="form-control" name="rate[]" value="{{ old('rate') }}"
                     placeholder="Rate" readonly>
             </div>
             <div class="col-md-2">
                 <label class="form-label">Hours<span class="text-danger">*</span></label>
                 <input type="number" class="form-control" name="hours[]" value="{{ old('hours') }}"
                     placeholder="Hours" step="0.01">
             </div>
             <div class="col-md-2">
                 <button type="button" class="btn btn-primary mt-2" id="addRow">Add More</button>
             </div>
         </div>
     @endif
 </div>

 <button type="submit" class="btn btn-success mt-3">
     {{ isset($overtimes) ? 'Update' : 'Submit' }}
 </button>

 <script>
     let rowIndex = {{ isset($overtimes->detail) ? count($overtimes->detail) : 1 }};

     $('#addRow').click(function() {
         const lastRow = $('#formContainer .row').last();

         let empId, empName;
         if (lastRow.find('select[name="employee_id[]"]').length) {
             empId = lastRow.find('select[name="employee_id[]"]').val();
             empName = lastRow.find('select[name="employee_id[]"] option:selected').text();
         } else {
             empId = lastRow.find('input[name="employee_id[]"]').val();
             empName = lastRow.find('select option:selected').text();
         }

         const lastWorkDate = lastRow.find('input[name="work_date[]"]').val();

         const lastActualDate = lastRow.find('input[name="actual_date[]"]').val();

         const rate = lastRow.find('input[name="rate[]"]').val();
         const hours = lastRow.find('input[name="hours[]"]').val();

         let newWorkDate = '',
             newActualDate = '';
         if (lastWorkDate) {
             const date = new Date(lastWorkDate);
             date.setDate(date.getDate() + 1);
             newWorkDate = date.toISOString().split('T')[0];
         }

         if (lastActualDate) {
             const actual = new Date(lastActualDate);
             actual.setDate(actual.getDate() + 1);
             newActualDate = actual.toISOString().split('T')[0];
         }

         const newRow = `
        <div class="row mb-3 g-2 align-items-end" id="row-${rowIndex}">
            <input type="hidden" name="ids[]" value="">
            <div class="col-md-2">
                <select class="form-select" disabled>
                    <option value="${empId}" selected>${empName}</option>
                </select>
                <input type="hidden" name="employee_id[]" value="${empId}">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="actual_date[]" value="${newActualDate}">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="work_date[]" value="${newWorkDate}">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="rate[]" value="${rate}" readonly>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="hours[]" value="${hours}" step="0.01">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger removeRow mt-2">Remove</button>
            </div>
        </div>
    `;

         $('#formContainer').append(newRow);
         rowIndex++;
     });

     $(document).on('click', '.removeRow', function() {
         $(this).closest('.row').remove();
     });

     $('form').submit(function(e) {
         let isValid = true;
         let errorMsg = '';

         $('select[name="employee_id[]"]').each(function() {
             if (!$(this).val()) {
                 isValid = false;
                 errorMsg = 'Please select an employee.';
             }
         });

         $('input[name="work_date[]"]').each(function() {
             if (!$(this).val()) {
                 isValid = false;
                 errorMsg = 'Please enter a work date.';
             }
         });

         $('input[name="rate[]"]').each(function() {
             if (!$(this).val() || parseFloat($(this).val()) <= 0) {
                 isValid = false;
                 errorMsg = 'Rate must be greater than 0.';
             }
         });

         $('input[name="hours[]"]').each(function() {
             if (!$(this).val() || parseFloat($(this).val()) <= 0) {
                 isValid = false;
                 errorMsg = 'Hours must be greater than 0.';
             }
         });

         if (!isValid) {
             e.preventDefault();
             alert(errorMsg);
         }
     });

     // Autofill rate from selected employee
     $(document).on('change', 'select[name="employee_id[]"]', function() {
         const rate = $(this).find('option:selected').data('rate') || '';
         const row = $(this).closest('.row');
         row.find('input[name="rate[]"]').val(rate);
     });

     // Trigger autofill on page load (if needed)
     $('select[name="employee_id[]"]').each(function() {
         $(this).trigger('change');
     });
 </script>
