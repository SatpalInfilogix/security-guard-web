<div class="row mb-3">
    <div class="col-md-4">
        <label for="employee_id">Employee <span class="text-danger">*</span></label>
        @if (isset($encashment))
            {{-- Edit Mode: Show readonly input --}}
            <input type="text" class="form-control"
                value="{{ $encashment->employee->first_name }} {{ $encashment->employee->surname }}" readonly>
            <input type="hidden" name="employee_id" value="{{ $encashment->employee_id }}">
        @else
            {{-- Create Mode: Show select2 dropdown --}}
            <select name="employee_id" id="employee_id" class="form-control select2">
                <option value="" disabled selected>Select Employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->first_name }} {{ $employee->surname }}
                    </option>
                @endforeach
            </select>
        @endif
        @error('employee_id')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-md-4">
        <label>Pending Leaves</label>
        <input type="text" name="pending_leaves" class="form-control" id="pending_leaves"
            value="{{ isset($encashment) ? $encashment->pending_leaves : '' }}" readonly>
    </div>

    <div class="col-md-4">
        <label for="encash_leaves">Leave to Encash <span class="text-danger">*</span></label>
        <input type="number" name="encash_leaves" class="form-control"
            value="{{ old('encash_leaves', $encashment->encash_leaves ?? '') }}" required min="1">
        @error('encash_leaves')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>

<button type="submit" class="btn btn-primary">Submit</button>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#employee_id').select2({
                placeholder: "Select Employee"
            });

            let pendingLeaves = parseFloat($('#pending_leaves').val()) || 0;

            @if (!isset($encashment))
                $('#employee_id').on('change', function() {
                    let employeeId = $(this).val();
                    if (employeeId) {
                        $.ajax({
                            url: '{{ route('get-pending-leaves') }}',
                            method: 'GET',
                            data: {
                                employee_id: employeeId
                            },
                            success: function(response) {
                                pendingLeaves = parseFloat(response.pending_leaves);
                                $('#pending_leaves').val(pendingLeaves);
                            }
                        });
                    }
                });
            @endif

            $('form').on('submit', function(e) {
                let encashLeaves = parseFloat($('input[name="encash_leaves"]').val()) || 0;
                if (encashLeaves > pendingLeaves) {
                    e.preventDefault();
                    alert('Encash leaves cannot be greater than pending leaves (' + pendingLeaves + ')');
                }
            });
        });
    </script>
@endpush
