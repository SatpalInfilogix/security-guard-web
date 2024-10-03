@if($hasPlugin('datePicker'))
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
      document.addEventListener('DOMContentLoaded', function() {
        flatpickr('.date-picker', {
            dateFormat: "Y-m-d",
            allowInput: true
        });
        flatpickr('.date-of-birth', {
            dateFormat: "Y-m-d",
            allowInput: true
        });
        flatpickr('.date_of_separation', {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    })
</script>
@endpush
@endif


