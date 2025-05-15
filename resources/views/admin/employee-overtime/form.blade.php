 <div id="formContainer">
     @if (isset($overtimes) && count($overtimes) > 0)
         @foreach ($overtimes as $index => $overtimeItem)
             <div class="row mb-3 g-2 align-items-end" id="row-{{ $index }}">
                 <input type="hidden" name="ids[]" value="{{ $overtimeItem->id }}">
                 <div class="col-md-3">
                     <label class="form-label">Employee</label>
                     <select class="form-select" disabled>
                         <option value="{{ $overtimeItem->employee_id }}" selected>
                             {{ $overtimeItem->employee->first_name ?? '' }}
                             {{ $overtimeItem->employee->surname ?? '' }}
                         </option>
                     </select>
                     <input type="hidden" name="employee_id[]" value="{{ $overtimeItem->employee_id }}">
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Date</label>
                     <input type="date" class="form-control" name="work_date[]"
                         value="{{ $overtimeItem->work_date }}">
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Rate</label>
                     <input type="number" class="form-control" name="rate[]" value="{{ $overtimeItem->rate }}">
                 </div>
                 <div class="col-md-2">
                     <label class="form-label">Hours</label>
                     <input type="number" class="form-control" name="hours[]" value="{{ $overtimeItem->hours }}">
                 </div>
                 <div class="col-md-3">
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
             <div class="col-md-3">
                 <label class="form-label">Employee</label>
                 <select class="form-select" name="employee_id[]">
                     <option value="">Select Employee</option>
                     @foreach ($employees as $employee)
                         <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->surname }}
                         </option>
                     @endforeach
                 </select>
             </div>
             <div class="col-md-2">
                 <label class="form-label">Date</label>
                 <input type="date" class="form-control" name="work_date[]" value="{{ old('work_date') }}">
             </div>
             <div class="col-md-2">
                 <label class="form-label">Rate</label>
                 <input type="number" class="form-control" name="rate[]" value="{{ old('rate') }}"
                     placeholder="Rate">
             </div>
             <div class="col-md-2">
                 <label class="form-label">Hours</label>
                 <input type="number" class="form-control" name="hours[]" value="{{ old('hours') }}"
                     placeholder="Hours">
             </div>
             <div class="col-md-3">
                 <button type="button" class="btn btn-primary mt-2" id="addRow">Add More</button>
             </div>
         </div>
     @endif
 </div>

 <button type="submit" class="btn btn-success mt-3">
     {{ isset($overtimes) ? 'Update' : 'Submit' }}
 </button>

 <script>
     let rowIndex = {{ isset($overtimes) ? count($overtimes) : 1 }};

     $('#addRow').click(function() {
         const firstRow = $('#formContainer .row').last();

         let empId, empName;
         if (firstRow.find('select[name="employee_id[]"]').length) {
             empId = firstRow.find('select[name="employee_id[]"]').val();
             empName = firstRow.find('select[name="employee_id[]"] option:selected').text();
         } else {
             empId = firstRow.find('input[name="employee_id[]"]').val();
             empName = firstRow.find('select option:selected').text();
         }

         const dateVal = firstRow.find('input[name="work_date[]"]').val();
         const rate = firstRow.find('input[name="rate[]"]').val();
         const hours = firstRow.find('input[name="hours[]"]').val();

         let newDate = '';
         if (dateVal) {
             const date = new Date(dateVal);
             date.setDate(date.getDate() + 1);
             newDate = date.toISOString().split('T')[0];
         }

         const newRow = `
            <div class="row mb-3 g-2 align-items-end" id="row-${rowIndex}">
                <input type="hidden" name="ids[]" value="">
                <div class="col-md-3">
                    <select class="form-select" disabled>
                        <option value="${empId}" selected>${empName}</option>
                    </select>
                    <input type="hidden" name="employee_id[]" value="${empId}">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="work_date[]" value="${newDate}">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="rate[]" value="${rate}">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="hours[]" value="${hours}">
                </div>
                <div class="col-md-3">
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
 </script>
