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

@if($hasPlugin('guardImage'))
    @push('scripts')
    <script>
        function showLink(input, linkId, oldLinkId) {
            const linkDiv = document.getElementById(linkId);
            const oldLinkDiv = document.getElementById(oldLinkId);

            if (input.files.length > 0) {
                const file = input.files[0];
                const fileUrl = URL.createObjectURL(file); // Create a URL for the file
                linkDiv.querySelector('a').href = fileUrl; // Set the href of the link to the file URL
                linkDiv.style.display = 'block'; // Show the link

                if (oldLinkDiv) {
                    oldLinkDiv.style.display = 'none'; // Hide the old document link
                }
            } else {
                linkDiv.style.display = 'none'; // Hide the link if no file is selected
                if (oldLinkDiv) {
                    oldLinkDiv.style.display = 'block'; // Show the old document link if no new file is selected
                }
            }
        }
        </script>
    @endpush
@endif

@if($hasPlugin('contentEditor'))
    @push('styles')
        <link href="{{ asset('assets/css/summernote/summernote-lite.min.css') }}" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/summernote/summernote-lite.min.js') }}"></script>
        <script>
            $(function(){
                $('#answer').summernote({
                    height: 400,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['fontname', ['fontname']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['table', ['table']],
                        ['insert', ['link', 'hr']],
                        ['view', ['fullscreen', 'codeview', 'help']],
                    ],
                });
            })
        </script>
    @endpush
@endif



@if($hasPlugin('dataTable'))
@push('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatables.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/jquery.dataTables.js') }}"></script>
@endpush
@endif

@if($hasPlugin('jQueryValidate'))
@push('scripts')
<script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
@endpush
@endif

@if($hasPlugin('chosen'))
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chosen.min.css') }}" />
@endpush
@push('scripts')
<script src="{{ asset('assets/js/chosen.jquery.min.js') }}"></script>
@endpush
@endif

@if($hasPlugin('datePicker'))
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/flatpickr/flatpickr.min.js') }}"></script>
<script>
    function initializeDatepickers() {
        $('.datepicker').each(function() {
            flatpickr(this, {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
        });
    }
</script>
@endpush
@endif


