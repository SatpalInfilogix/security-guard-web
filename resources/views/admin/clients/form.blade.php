<div class="row mb-2">
    
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="client_name" value="{{ old('client_name', $client->client_name ?? '' ) }}" label="Client Name" placeholder="Enter your client name" required="true"  minlength="3" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="client_code" value="{{ old('client_code', $client->client_code ?? '') }}" label="Client Code" placeholder="Enter your client code"  required="true" />
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<script>
    $(document).ready(function () {
    $('#client_name').on('input', function () {
        var clientName = $(this).val();

        if (clientName.trim() !== '') {
            $.ajax({
                url: '{{ route('generate.client.code') }}',
                method: 'POST',
                data: {
                    client_name: clientName,
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

