 <div class="row mb-2">
     <div class="col-md-4">
         <div class="mb-3">
             <label for="annual">Annual Threshold<span class="text-danger">*</span></label>
             <input type="number" name="annual" id="guard_annual" class="form-control" required
                 placeholder="Annual Threshold" value="{{$threshold->annual ?? ''}}">
         </div>
     </div>
     <div class="col-md-4">
         <div class="mb-3">
             <label for="monthly">Monthly Threshold</label>
             <input type="number" name="monthly" id="monthly" class="form-control" placeholder="Monthly Threshold"
                 readonly value="{{$threshold->monthly ?? ''}}">
         </div>
     </div>
     <div class="col-md-4">
         <div class="mb-3">
             <label for="fortnightly">Fortnightly Threshold</label>
             <input type="number" name="fortnightly" id="fortnightly" class="form-control"
                 placeholder="Fortnightly Threshold" readonly value="{{$threshold->fortnightly ?? ''}}">
         </div>
     </div>
     <div class="col-md-4">
         <div class="mb-3">
             <label for="effective_date">Effective Date<span class="text-danger">*</span></label>
             <input type="text" name="effective_date" id="effective_date" class="form-control datepicker"
                 placeholder="Select Date" required value="{{$threshold->effective_date ?? ''}}" >
         </div>
     </div>
 </div>

 <div class="row mb-2">
     <div class="col-lg-6 mb-2">
         <button type="submit" class="btn btn-primary w-md">Save</button>
     </div>
 </div>
 <x-include-plugins :plugins="['datePicker']"></x-include-plugins>
 <script>
     $(document).ready(function() {
         $('#guard_annual').on('input', function() {
             const annual = parseFloat($(this).val());

             if (!isNaN(annual)) {
                 const monthly = annual / 12;
                 const fortnightly = annual / 26;

                 $('#monthly').val(monthly.toFixed(2));
                 $('#fortnightly').val(fortnightly.toFixed(2));
             } else {
                 $('#monthly, #fortnightly').val('');
             }
         });
     });
 </script>
