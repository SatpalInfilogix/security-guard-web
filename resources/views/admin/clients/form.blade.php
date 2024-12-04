<div class="row mb-2">
    <div class="col-md-6">
        <div class="mb-3">
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id ?? '' }}">
            <x-form-input name="client_name" value="{{ old('client_name', $client->client_name ?? '') }}"
                label="Client Name" placeholder="Enter your client name" required="true" minlength="3" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="client_code" value="{{ old('client_code', $client->client_code ?? '') }}"
                label="Client Code" placeholder="Enter your client code" required="true" readonly />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="nis" value="{{ old('nis', $client->nis ?? '') }}" label="NIS/NHT Number"
                placeholder="Enter your NIS/NHT Number" />
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
        $('#nis').on('input', function() {
            let value = $(this).val().toUpperCase();

            if (value.length > 0 && /\d/.test(value.charAt(0))) {
                value = '';
            }

            value = value.replace(/[^A-Z0-9]/g, '');

            if (value.length > 1) {
                value = value.charAt(0) + value.slice(1, 7).replace(/[^0-9]/g, '');
            }

            $(this).val(value.substring(0, 7));
        });


        $('#client_name').on('input', function() {
            var clientName = $(this).val();
            var clientId = $('#client_id').val();

            if (clientName.trim() !== '') {
                $.ajax({
                    url: '{{ route('generate.client.code') }}',
                    method: 'POST',
                    data: {
                        client_name: clientName,
                        client_id: clientId,
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#client_code').val(response.client_code);
                    }
                });
            } else {
                $('#client_code').val('');
            }
        });
    });
</script>
