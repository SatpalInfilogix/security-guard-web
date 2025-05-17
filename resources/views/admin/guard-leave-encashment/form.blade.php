<div class="row mb-3">
    <div class="col-md-4">
        <label for="guard_id">Guard <span class="text-danger">*</span></label>
        @if (isset($guardEncashment))
            {{-- Edit Mode: Show readonly input --}}
            <input type="text" class="form-control"
                value="{{ $guardEncashment->guardUser->first_name }} {{ $guardEncashment->guardUser->surname }}" readonly>
            <input type="hidden" name="guard_id" value="{{ $guardEncashment->guard_id }}">
        @else
            {{-- Create Mode: Show select2 dropdown --}}
            <select name="guard_id" id="guard_id" class="form-control select2">
                <option value="" disabled selected>Select Guard</option>
                @foreach ($guards as $guard)
                    <option value="{{ $guard->id }}" {{ old('guard_id') == $guard->id ? 'selected' : '' }}>
                        {{ $guard->first_name }} {{ $guard->surname }}
                    </option>
                @endforeach
            </select>
        @endif
        @error('guard_id')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-md-4">
        <label>Pending Leaves</label>
        <input type="text" class="form-control" id="pending_leaves"
            value="{{ isset($guardEncashment) ? $guardEncashment->pending_leaves : '' }}" readonly>
    </div>

    <div class="col-md-4">
        <label for="encash_leaves">Leave to Encash <span class="text-danger">*</span></label>
        <input type="number" name="encash_leaves" class="form-control"
            value="{{ old('encash_leaves', $guardEncashment->encash_leaves ?? '') }}" required min="1">
        @error('encash_leaves')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>

<button type="submit" class="btn btn-primary">Submit</button>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#guard_id').select2({
            placeholder: "Select Guard"
        });

        let pendingLeaves = parseFloat($('#pending_leaves').val()) || 0;

        @if (!isset($guardEncashment))
        $('#guard_id').on('change', function () {
            let guardId = $(this).val();
            if (guardId) {
                $.ajax({
                    url: '{{ route('get-guard-pending-leaves') }}',
                    method: 'GET',
                    data: { guard_id: guardId },
                    success: function (response) {
                        pendingLeaves = parseFloat(response.pending_leaves);
                        $('#pending_leaves').val(pendingLeaves);
                    }
                });
            }
        });
        @endif

        $('form').on('submit', function (e) {
            let encashLeaves = parseFloat($('input[name="encash_leaves"]').val()) || 0;
            if (encashLeaves > pendingLeaves) {
                e.preventDefault();
                alert('Encash leaves cannot be greater than pending leaves (' + pendingLeaves + ')');
            }
        });
    });
</script>
@endpush
