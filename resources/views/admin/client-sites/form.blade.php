<div class="row mb-2">
    <legend>Basic Details</legend>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="client_code">Client Code<span class="text-danger">*</span></label>
            <select name="client_code" id="client_code" class="form-control{{ $errors->has('client_code') ? ' is-invalid' : '' }}">
                <option value="" selected disabled>Select Client Code</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(isset($clientSite->client_id) && $clientSite->client_id == $client->id)>
                        {{ $client->client_code }}
                    </option>
                @endforeach
            </select>
            @error('client_code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="client_id">Client<span class="text-danger">*</span></label>
            <select name="client_id" id="client_id" class="form-control{{ $errors->has('client_id') ? ' is-invalid' : '' }}">
                <option value="" selected disabled>Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(isset($clientSite->client_id) && $clientSite->client_id == $client->id)>
                        {{ $client->client_name }}
                    </option>
                @endforeach
            </select>
            @error('client_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_code" value="{{ old('location_code', $clientSite->location_code ?? '' ) }}" label="Location Code" placeholder="Enter your Location Code" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location" value="{{ old('location', $clientSite->location_code ?? '' ) }}" label="Location" placeholder="Enter your Location" required="true" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $sectors = config('clientSite.sectors'); @endphp
            <label for="sector_id">Sector</label>
            <select name="sector_id" id="sector_id" class="form-control{{ $errors->has('sector_id') ? ' is-invalid' : '' }}">
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
            <select name="region_code" id="region_code" class="form-control{{ $errors->has('region_code') ? ' is-invalid' : '' }}">
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
            <select name="region" id="region" class="form-control{{ $errors->has('region') ? ' is-invalid' : '' }}">
                @foreach ($regions as $key => $region)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->region === $key)>{{ $region }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="area_code" value="{{ old('area_code', $clientSite->area_code ?? '' ) }}" label="Area Code" placeholder="Enter your Area Code" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="area" value="{{ old('area', $clientSite->area ?? '' ) }}" label="Area" placeholder="Enter your Area" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="latitude" value="{{ old('latitude', $clientSite->latitude ?? '') }}" label="Latitude" placeholder="Enter your Latitude"  type="number"  step="any" required="true"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="longitude" value="{{ old('longitude', $clientSite->longitude ?? '') }}" label="Longitude" placeholder="Enter your Longitude"   type="number"  step="any" required="true"/>
        </div>
    </div>
</div>
<legend>Vanguard Supervison</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="sr_manager" value="{{ old('sr_manager', $clientSite->sr_manager ?? '') }}" label="Sr Manager" placeholder="Enter Sr Manager"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="sr_manager_email" value="{{ old('sr_manager_email', $clientSite->sr_manager_email ?? '') }}" label="Sr Manager Email" placeholder="Enter Sr Manager Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="manager_id">Manager<span class="text-danger">*</span></label>
            <select name="manager_id" id="manager_id" class="form-control{{ $errors->has('manager_id') ? ' is-invalid' : '' }}">
                <option value="" selected disabled>Select Manager</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(isset($clientSite->manager_id) && $clientSite->manager_id == $user->id)>
                        {{ $user->first_name . ' '.$user->last_name }}
                    </option>
                @endforeach
            </select>
            @error('manager_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="email" name="email" value="{{ old('email', $clientSite->email ?? '') }}" label="Manager Email" placeholder="Enter your Manager Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="supervisor" value="{{ old('supervisor', $clientSite->supervisor ?? '') }}" label="Supervisor" placeholder="Enter supervisor"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="supervisor_email" value="{{ old('supervisor_email', $clientSite->supervisor_email ?? '') }}" label="Supervisor Email" placeholder="Enter Supervisor Email"/>
        </div>
    </div>
</div>
<legend>Invoicing</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            @php $frequencys = config('clientSite.frequency'); @endphp
            <label for="frequency">Frequency</label>
            <select name="frequency" id="frequency" class="form-control{{ $errors->has('frequency') ? ' is-invalid' : '' }}">
                @foreach ($frequencys as $key => $frequency)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->frequency === $key)>{{ $frequency }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="status" class="form-label">Service Status</label>
            <select name="status" id="service-status" class="form-control">
                <option value="Active" @selected(isset($clientSite) && $clientSite->status === 'Active')>Active</option>
                <option value="Inactive" @selected(isset($clientSite) && $clientSite->status === 'Inactive')>Inactive</option>
            </select>
        </div>
    </div>
</div>
<legend>Client Address</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="unit_no" value="{{ old('unit_no', $clientSite->unit_no ?? '') }}" label="Unit No" placeholder="Enter Unit No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="building_name" value="{{ old('building_name', $clientSite->building_name ?? '') }}" label="Building Name" placeholder="Enter Building Name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="street_no" value="{{ old('street_no', $clientSite->street_no ?? '') }}" label="Sreet No" placeholder="Enter Sreet No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="street_road" value="{{ old('street_road', $clientSite->street_road ?? '') }}" label="Street Road" placeholder="Enter Street Road"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="parish" value="{{ old('parish', $clientSite->parish ?? '') }}" label="Parish" placeholder="Enter your parish"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="country" value="{{ old('country', $clientSite->country ?? '') }}" label="Country" placeholder="Enter Country"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="postal_code" value="{{ old('postal_code', $clientSite->postal_code ?? '') }}" label="Postal Code" placeholder="Enter Postal Code"/>
        </div>
    </div>
</div>
<legend>Location Address</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="postal_code" value="{{ old('postal_code', $clientSite->postal_code ?? '') }}" label="Postal Code" placeholder="Enter Postal Code"/>
        </div>
    </div>
</div>


<div class="row mb-2">

    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="billing_address" value="{{ old('billing_address', $clientSite->billing_address ?? '') }}" label="Billing Address" placeholder="Enter your Billing Address"/>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="contact_operation" value="{{ old('contact_operation', $clientSite->contact_operation ?? '') }}" label="Contact Operation" placeholder="Enter your Contact Operation"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="telephone_number" value="{{ old('telephone_number', $clientSite->telephone_number ?? '') }}" label="Telephone Number" placeholder="Enter your Telephone Number"/>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="invoice_recipient_main" value="{{ old('invoice_recipient_main', $clientSite->invoice_recipient_main ?? '') }}" label="Invoice Recipient - Main" placeholder="Enter your Invoice Recipient - Main"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="invoice_recipient_copy" value="{{ old('invoice_recipient_copy', $clientSite->invoice_recipient_copy ?? '') }}" label="Invoice Recipient - Copy" placeholder="Enter your Invoice Recipient - Copy"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="account_payable_contact_name" value="{{ old('account_payable_contact_name', $clientSite->account_payable_contact_name ?? '') }}" label="Account Payable Contact-Name" placeholder="Enter your Account Payable Contact-Name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="email_2" name="email_2" value="{{ old('email_2', $clientSite->email_2 ?? '') }}" label="Email" placeholder="Enter your Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="number" value="{{ old('number', $clientSite->number ?? '') }}" label="Number" placeholder="Enter your Number"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="number_2" value="{{ old('number_2', $clientSite->number_2 ?? '') }}" label="Number 2" placeholder="Enter your Number 2"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="account_payable_contact_email" value="{{ old('account_payable_contact_email', $clientSite->account_payable_contact_email ?? '') }}" label="Account Payable Contact-Email" placeholder="Enter your Account Payable Contact-Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="email_3" name="email_3" value="{{ old('email_3', $clientSite->email_3 ?? '') }}" label="Email" placeholder="Enter your Email"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="telephone_number_2" value="{{ old('telephone_number_2', $clientSite->telephone_number_2 ?? '') }}" label="Telephone Number" placeholder="Enter your Telephone Number"/>
        </div>
    </div>


    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input type="number" name="radius" value="{{ old('radius', $clientSite->radius ?? '') }}" label="Radius (in meters)" placeholder="Enter your Radius" min=0 step="any" required="true"/>
        </div>
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
        $('#client_id').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Client'
        });
    });
</script>