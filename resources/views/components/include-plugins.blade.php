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


