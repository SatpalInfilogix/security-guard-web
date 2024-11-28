<div class="row mb-2">

    <div class="col-md-6">
        <div class="mb-3">
            <input type="hidden" name="client_id" id="client_id" value="{{$client->id ?? ''}}">
            <x-form-input name="client_name" value="{{ old('client_name', $client->client_name ?? '' ) }}" label="Client Name" placeholder="Enter your client name" required="true"  minlength="3" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="client_code" value="{{ old('client_code', $client->client_code ?? '') }}" label="Client Code" placeholder="Enter your client code"  required="true" readonly/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="nis" value="{{ old('nis', $client->nis ?? '') }}" label="NIS/NHT Number" placeholder="Enter your NIS/NHT Number"/>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>
<x-include-plugins :plugins="['trn&nisFormat']"></x-include-plugins>
<script>
     document.addEventListener('DOMContentLoaded', function() {
            function formatInput(input) {
                let value = input.value.replace(/\D/g, '');
                let formattedValue = value.replace(/(\d{3})(?=\d)/g, '$1-');
                input.value = formattedValue;
            }

            const nisInput = document.querySelector('input[name="nis"]');

            nisInput.addEventListener('input', function() {
                formatInput(nisInput);
            });
        });
    $(document).ready(function () {
        $('#client_name').on('input', function () {
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

