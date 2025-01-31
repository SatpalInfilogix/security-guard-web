<div class="row mb-2">
    <legend>Basic Details</legend>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="client">Client<span class="text-danger">*</span></label>
            <select name="client" id="client" @class(["form-control", "is-invalid" => $errors->has('client')])>
                <option value="" selected disabled>Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" data-client-code="{{ $client->client_code }}" @selected(($clientSite->client_id ?? '') == $client->id)>
                        {{ $client->client_name }}
                    </option>
                @endforeach
            </select>
            @error('client')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_code" value="{{ $clientSite->client_code ?? '' }}" label="Client Code" placeholder="Client Code" required="true" readonly />
            @error('client_code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_code" value="{{ $clientSite->location_code ?? '' }}" label="Location Code" placeholder="Enter your Location Code" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location" value="{{ $clientSite->location_code ?? '' }}" label="Location" placeholder="Enter your Location" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $sectors = config('clientSite.sectors'); @endphp
            <label for="sector_id">Sector</label>
            <select name="sector_id" id="sector_id" @class(["form-control", "is-invalid" => $errors->has('sector_id')])>
                @foreach ($sectors as $key => $sector)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->sector_id === $key)>{{ $sector }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $region_codes = config('clientSite.region_code'); @endphp
            <label for="region_code">Region Code</label>
            <select name="region_code" id="region_code" @class(["form-control", "is-invalid" => $errors->has('region_code')])>
                @foreach ($region_codes as $key => $region_code)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->region_code === $key)>{{ $region_code }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $regions = config('clientSite.region'); @endphp
            <label for="region">Region</label>
            <select name="region" id="region" @class(["form-control", "is-invalid" => $errors->has('region')])>
                @foreach ($regions as $key => $region)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->region === $key)>{{ $region }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="area_code" value="{{ $clientSite->area_code ?? '' }}" label="Area Code" placeholder="Enter your Area Code" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="area" value="{{ $clientSite->area ?? '' }}" label="Area" placeholder="Enter your Area" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="latitude" value="{{ $clientSite->latitude ?? '' }}" label="Latitude" placeholder="Enter your Latitude"  type="number"  step="any" required="true"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="longitude" value="{{ $clientSite->longitude ?? '' }}" label="Longitude" placeholder="Enter your Longitude"   type="number"  step="any" required="true"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="radius" value="{{ $clientSite->radius ?? '' }}" label="Radius (in meters)" placeholder="Enter your Radius" min=0 step="any" required="true"/>
        </div>
    </div>
</div>
<legend>Vanguard Supervison</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="sr_manager" value="{{ $clientSite->sr_manager ?? '' }}" label="Sr Manager" placeholder="Enter Sr Manager"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="sr_manager_email" value="{{ $clientSite->sr_manager_email ?? '' }}" label="Sr Manager Email" placeholder="Enter Sr Manager Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="manager">Manager<span class="text-danger">*</span></label>
            <select name="manager" id="manager" @class(["form-control", "is-invalid" => $errors->has('manager')])>
                <option value="" selected disabled>Select Manager</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(($clientSite->manager_id ?? '') == $user->id)>
                        {{ $user->first_name . ' '.$user->last_name }}
                    </option>
                @endforeach
            </select>
            @error('manager')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="manager_email" name="email" value="{{ $clientSite->email ?? '' }}" label="Manager Email" placeholder="Enter Manager Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="supervisor" value="{{ $clientSite->supervisor ?? '' }}" label="Supervisor" placeholder="Enter supervisor"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="supervisor_email" value="{{ $clientSite->supervisor_email ?? '' }}" label="Supervisor Email" placeholder="Enter Supervisor Email"/>
        </div>
    </div>
</div>
<legend>Invoicing</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            @php $frequencys = config('clientSite.frequency'); @endphp
            <label for="frequency">Frequency</label>
            <select name="frequency" id="frequency" @class(["form-control", "is-invalid" => $errors->has('frequency')])>
                @foreach ($frequencys as $key => $frequency)
                    <option value="{{ $key }}" @selected(($clientSite->frequency ?? '') === $key)>{{ $frequency }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="service_status" class="form-label">Service Status</label>
            <select name="service_status" id="service_status" class="form-control">
                <option value="Active" @selected(($clientSite->status ?? '') === 'Active')>Active</option>
                <option value="Inactive" @selected(($clientSite->status ?? '') === 'Inactive')>Inactive</option>
            </select>
        </div>
    </div>
</div>
<legend>Client Address</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_unit_no" value="{{ $clientSite->unit_no ?? '' }}" label="Unit No" placeholder="Enter Unit No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_building_name" value="{{ $clientSite->building_name ?? '' }}" label="Building Name" placeholder="Enter Building Name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_street_no" value="{{ $clientSite->street_no ?? '' }}" label="Sreet No" placeholder="Enter Sreet No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_street_road" value="{{ $clientSite->street_road ?? '' }}" label="Street Road" placeholder="Enter Street Road"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_parish" value="{{ $clientSite->parish ?? '' }}" label="Parish" placeholder="Enter your parish"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_country" value="{{ $clientSite->country ?? '' }}" label="Country" placeholder="Enter Country"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_postal_code" value="{{ $clientSite->postal_code ?? '' }}" label="Postal Code" placeholder="Enter Postal Code"/>
        </div>
    </div>
</div>
<legend>Location Address</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_unit_no" value="{{ $clientSite->unit_no ?? '' }}" label="Unit No" placeholder="Enter Unit No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_building_name" value="{{ $clientSite->building_name ?? '' }}" label="Building Name" placeholder="Enter Building Name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_street_no" value="{{ $clientSite->street_no ?? '' }}" label="Sreet No" placeholder="Enter Sreet No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_street_road" value="{{ $clientSite->street_road ?? '' }}" label="Street Road" placeholder="Enter Street Road"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_parish" value="{{ $clientSite->parish ?? '' }}" label="Parish" placeholder="Enter your parish"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_country" value="{{ $clientSite->country ?? '' }}" label="Country" placeholder="Enter Country"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_postal_code" value="{{ $clientSite->postal_code ?? '' }}" label="Postal Code" placeholder="Enter Postal Code"/>
        </div>
    </div>
</div>
<div id="client-operation-contact-container">
    <legend>Client Operations Contact</legend>
    <div class="row mb-2" id="client-operation-contact">
        <div class="col-md-4">
            <div class="mb-3">
                <x-form-input name="client_operation_name[]" value="{{ $clientSite->parish ?? '' }}" label="Name" placeholder="Enter name"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <x-form-input name="client_operation_position[]" value="{{ $clientSite->parish ?? '' }}" label="Position" placeholder="Enter position"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <x-form-input name="client_operation_email[]" value="{{ $clientSite->parish ?? '' }}" label="Email" placeholder="Enter email"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <x-form-input name="client_operation_telephone[]" value="{{ $clientSite->parish ?? '' }}" label="Telephone" placeholder="Enter telephone"/>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <x-form-input name="client_operation_mobile[]" value="{{ $clientSite->parish ?? '' }}" label="Mobile" placeholder="Enter mobile"/>
            </div>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-primary mt-4" id="add-operation-contact">Add more contact</button>
        </div>
    </div>
</div>

<legend>Client Accounts Contact</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_account_name[]" value="{{ $clientSite->parish ?? '' }}" label="Name" placeholder="Enter name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_account_position[]" value="{{ $clientSite->parish ?? '' }}" label="Position" placeholder="Enter position"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_account_email[]" value="{{ $clientSite->parish ?? '' }}" label="Email" placeholder="Enter email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_account_telephone[]" value="{{ $clientSite->parish ?? '' }}" label="Telephone" placeholder="Enter telephone"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_account_mobile[]" value="{{ $clientSite->parish ?? '' }}" label="Mobile" placeholder="Enter mobile"/>
        </div>
    </div>
    <div class="col-md-4">
        <button type="button" class="btn btn-primary mt-4" id="add-account-contact">Add more contact</button>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<x-include-plugins :plugins="['chosen']"></x-include-plugins>
<script>
    $(function(){
        $('#client').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Client'
        });

        $('#client').change(function(){
            let clientCode = $('#client option:selected').attr('data-client-code');
            $('#client_code').val(clientCode);
        });

        $('#add-operation-contact').click(function(){
            var newContactRow = $('#client-operation-contact').first().clone();
            newContactRow.find('input').val('');
            newContactRow.find('button').toggleClass('btn-primary btn-danger').html('Delete');
            newContactRow.appendTo('#client-operation-contact-container');
            console.log('newContactRow',newContactRow)

            newContactRow.find('.remove-contact').click(function(){
                newContactRow.remove();
            });
        });
    });
</script>