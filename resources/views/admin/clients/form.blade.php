<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id ?? '' }}">
            <x-form-input name="client_name" value="{{ old('client_name', $client->client_name ?? '') }}"
                label="Client Name" placeholder="Enter your client name" required="true" minlength="3" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_code" value="{{ old('client_code', $client->client_code ?? '') }}"
                label="Client Code" placeholder="Enter your client code" required="true" readonly />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="nis" value="{{ old('nis', $client->nis ?? '') }}" label="NIS/NHT Number"
                placeholder="Enter your NIS/NHT Number" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="gct" value="{{ old('gct', $client->gct ?? '') }}" label="GCT%"
                placeholder="Enter GCT%" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $sectors = config('clientSite.sectors'); @endphp
            <label for="sector_id">Sector</label>
            <select name="sector_id" id="sector_id" @class(["form-control", "is-invalid" => $errors->has('sector_id')])>
                <option value="" selected disabled>Select Sector</option>
                @foreach ($sectors as $key => $sector)
                    <option value="{{ $key }}" @selected(isset($client) && $client->sector_id == $key)>{{ $sector }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $frequencys = config('clientSite.frequency'); @endphp
            <label for="frequency">Frequency</label>
            <select name="frequency" id="frequency" @class(["form-control", "is-invalid" => $errors->has('frequency')])>
                <option value="" selected disabled>Select Frequency</option>
                @foreach ($frequencys as $key => $frequency)
                    <option value="{{ $key }}" @selected(($client->frequency ?? '') == $key)>{{ $frequency }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @foreach($rateMasters as $key => $rateMaster)
    <div class="row mb-2" id="rateMasterBlock-{{ $rateMaster->id }}">
        <legend>{{ $rateMaster->guard_type ?? '' }}</legend>
        <div class="col-md-4">
            <div class="mb-3">
                <x-form-input name="guard_type[]" value="{{ $rateMaster->guard_type ?? '' }}" label="Guard Type" placeholder="Enter Guard Type" required="true" readonly/>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                <label for="rate_master_id">Regular Rate</label>
                <input class="form-control rate-calculate" id="regular-rate-{{ $rateMaster->id }}" name="regular_rate[]" value="{{ $rateMaster->regular_rate ?? '' }}" placeholder="Enter your Rate" type="number" step="any" min="0" required="true"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Laundry Allowance</label>
                <input class="form-control rate-calculate" id="laundry_allowance_{{ $rateMaster->id }}" name="laundry_allowance[]" value="{{ $rateMaster->laundry_allowance ?? '' }}"  placeholder="Enter Laundry Allowance" type="number" step="any" min="0" required="true"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Canine Premium</label>
                <input class="form-control rate-calculate" id="canine_premium_{{ $rateMaster->id }}" name="canine_premium[]" value="{{ $rateMaster->canine_premium ?? '' }}" placeholder="Enter Canine Premium" type="number" step="any" min="0" />
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Fire Arm Premium</label>
                <input class="form-control rate-calculate" id="fire_arm_premium_{{ $rateMaster->id }}" name="fire_arm_premium[]" value="{{ $rateMaster->fire_arm_premium ?? '' }}" placeholder="Enter Fire Arm Premium" type="number" step="any" min="0"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Gross Hourly Rate</label>
                <input name="gross_hourly_rate[]" id="gross_hourly_rate_{{ $rateMaster->id }}" value="{{ $rateMaster->gross_hourly_rate ?? '' }}"  class="form-control" type="number" step="any" min="0" readonly/>
                <span class="text-muted form-text" id="gross_hourly_display_{{ $rateMaster->id }}"></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Normal Rate (up to 40 Hours)</label>
                <input name="normal_rate[]" id="normal_rate_{{ $rateMaster->id }}" value="{{ $rateMaster->gross_hourly_rate ?? '' }}" class="form-control" type="number" step="any" min="0" readonly/>
                <span class="text-muted form-text" id="normal_rate_display_{{ $rateMaster->id }}"></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Overtime Rate (exceeding 40 hours)</label>
                <input name="overtime_rate[]" id="overtime_rate_{{ $rateMaster->id }}" value="{{ $rateMaster->overtime_rate ?? '' }}" class="form-control" type="number" step="any" min="0" readonly/>
                <span class="text-muted form-text" id="overtime_rate_display_{{ $rateMaster->id }}"></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="rate_master_id">Holiday Rate</label>
                <input name="holiday_rate[]" id="holiday_rate_{{ $rateMaster->id }}" value="{{ $rateMaster->holiday_rate ?? '' }}" class="form-control" type="number" step="any" min="0" readonly/>
                <span class="text-muted form-text" id="holiday_rate_display_{{ $rateMaster->id }}"></span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>
<script>
    $(document).ready(function() {
        function calculateRates(rateMasterId) {
            let regularRate = parseFloat($('#regular-rate-' + rateMasterId).val()) || 0;
            console.log(regularRate);
            let laundryAllowance = parseFloat($('#laundry_allowance_' + rateMasterId).val()) || 0;
            let caninePremium = parseFloat($('#canine_premium_' + rateMasterId).val()) || 0;
            let fireArmPremium = parseFloat($('#fire_arm_premium_' + rateMasterId).val()) || 0;

            let total = regularRate + laundryAllowance + caninePremium + fireArmPremium;

            if (regularRate === 0 && laundryAllowance === 0 && caninePremium === 0 && fireArmPremium === 0) {
                $('#gross_hourly_display_' + rateMasterId).text('');
                $('#normal_rate_display_' + rateMasterId).text('');
                $('#overtime_rate_display_' + rateMasterId).text('');
                $('#holiday_rate_display_' + rateMasterId).text('');
            } else {
                $('#gross_hourly_rate_' + rateMasterId).val(total.toFixed(2));
                $('#normal_rate_' + rateMasterId).val(total.toFixed(2));
                $('#gross_hourly_display_' + rateMasterId).text(`(${regularRate} + ${laundryAllowance} + ${caninePremium} + ${fireArmPremium}) = ${total.toFixed(2)}`);
                $('#normal_rate_display_' + rateMasterId).text(`(${regularRate} + ${laundryAllowance} + ${caninePremium} + ${fireArmPremium}) = ${total.toFixed(2)}`);

                let overtimeRate = ((regularRate + caninePremium) * 1.5) + laundryAllowance;
                $('#overtime_rate_' + rateMasterId).val(overtimeRate.toFixed(2));
                $('#overtime_rate_display_' + rateMasterId).text(`(${regularRate} + ${caninePremium} * 1.5) + ${laundryAllowance} = ${overtimeRate.toFixed(2)}`);

                let holidayRate = ((regularRate + caninePremium) * 2) + laundryAllowance;
                $('#holiday_rate_' + rateMasterId).val(holidayRate.toFixed(2));
                $('#holiday_rate_display_' + rateMasterId).text(`(${regularRate} + ${caninePremium} * 2) + ${laundryAllowance} = ${holidayRate.toFixed(2)}`);
            }
        }

        $('.rate-calculate').on('input', function() {
            var rateMasterId = $(this).closest('.row').attr('id').split('-')[1];
            calculateRates(rateMasterId);
        });

        @foreach($rateMasters as $rateMaster)
            calculateRates('{{ $rateMaster->id }}');
        @endforeach

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
