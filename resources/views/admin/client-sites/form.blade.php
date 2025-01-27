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
            <x-form-input name="client_code" value="{{ $clientSite->client->client_code ?? '' }}" label="Client Code" placeholder="Client Code" required="true" readonly />
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
                <option value="" selected disabled>Select Sector</option>
                @foreach ($sectors as $key => $sector)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->sector_id == $key)>{{ $sector }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $region_codes = config('clientSite.region_code'); @endphp
            <label for="region_code">Region Code</label>
            <select name="region_code" id="region_code" @class(["form-control", "is-invalid" => $errors->has('region_code')])>
                <option value="" selected disabled>Select Region Code</option>
                @foreach ($region_codes as $key => $region_code)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->region_code == $key)>{{ $region_code }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            @php $regions = config('clientSite.region'); @endphp
            <label for="region">Region</label>
            <select name="region" id="region" @class(["form-control", "is-invalid" => $errors->has('region')])>
                <option value="" selected disabled>Select Region</option>
                @foreach ($regions as $key => $region)
                    <option value="{{ $key }}" @selected(isset($clientSite) && $clientSite->region == $key)>{{ $region }}</option>
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
                <option value="" selected disabled>Select Frequency</option>
                @foreach ($frequencys as $key => $frequency)
                    <option value="{{ $key }}" @selected(($clientSite->frequency ?? '') == $key)>{{ $frequency }}</option>
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
    {{-- <div class="col-md-4">
        <div class="mb-3">
            @php $rates = config('clientSite.rateMaster'); @endphp
            <label for="rates">Rate</label>
            <select name="rates" id="rates" @class(["form-control", "is-invalid" => $errors->has('rates')])>
                <option value="" selected disabled>Select Rate</option>
                @foreach ($rates as $key => $rate)
                    <option value="{{ $key }}" @selected(($clientSite->rate ?? '') === $key)>{{ $rate }}</option>
                @endforeach
            </select>
        </div>
    </div> --}}
</div>
<legend>Client Address</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_unit_no" value="{{ $clientSite->unit_no_client ?? '' }}" label="Unit No" placeholder="Enter Unit No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_building_name" value="{{ $clientSite->building_name_client ?? '' }}" label="Building Name" placeholder="Enter Building Name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_street_no" value="{{ $clientSite->street_no_client ?? '' }}" label="Sreet No" placeholder="Enter Sreet No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_street_road" value="{{ $clientSite->street_road_client ?? '' }}" label="Street Road" placeholder="Enter Street Road"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_parish" value="{{ $clientSite->parish_client ?? '' }}" label="Parish" placeholder="Enter your parish"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_country" value="{{ $clientSite->country_client ?? '' }}" label="Country" placeholder="Enter Country"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="client_address_postal_code" value="{{ $clientSite->postal_code_client ?? '' }}" label="Postal Code" placeholder="Enter Postal Code"/>
        </div>
    </div>
</div>
<legend>Location Address</legend>
<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_unit_no" value="{{ $clientSite->unit_no_location ?? '' }}" label="Unit No" placeholder="Enter Unit No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_building_name" value="{{ $clientSite->building_name_location ?? '' }}" label="Building Name" placeholder="Enter Building Name"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_street_no" value="{{ $clientSite->street_no_location ?? '' }}" label="Sreet No" placeholder="Enter Sreet No"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_street_road" value="{{ $clientSite->street_road_location ?? '' }}" label="Street Road" placeholder="Enter Street Road"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_parish" value="{{ $clientSite->parish_location ?? '' }}" label="Parish" placeholder="Enter your parish"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_country" value="{{ $clientSite->country_location ?? '' }}" label="Country" placeholder="Enter Country"/>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="location_address_postal_code" value="{{ $clientSite->postal_code_location ?? '' }}" label="Postal Code" placeholder="Enter Postal Code"/>
        </div>
    </div>
</div>
<div id="client-operation-contact-container">
    <legend>Client Operations Contact</legend>
    <div class="row mb-2" id="client-operation-contact">
    
        @if(isset($clientSite->clientOperation) && $clientSite->clientOperation->count() > 0)
            @foreach($clientSite->clientOperation as $operationContact)
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_operation_name[]" value="{{ $operationContact->name }}" label="Name" placeholder="Enter name"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_operation_position[]" value="{{ $operationContact->position }}" label="Position" placeholder="Enter position"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_operation_email[]" value="{{ $operationContact->email }}" label="Email" placeholder="Enter email"/>
                        </div>
                    </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_operation_telephone[]" value="{{ $operationContact->telephone_number }}" label="Telephone" placeholder="Enter telephone"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_operation_mobile[]" value="{{ $operationContact->mobile }}" label="Mobile" placeholder="Enter mobile"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger mt-4" id="remove-contact">Delete</button>
                </div>
            @endforeach
        @else
            <div class="col-md-4 mb-3">
                <x-form-input name="client_operation_name[]" value="" label="Name" placeholder="Enter name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="client_operation_position[]" value="" label="Position" placeholder="Enter position"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="client_operation_email[]" value="" label="Email" placeholder="Enter email"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="client_operation_telephone[]" value="" label="Telephone" placeholder="Enter telephone"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="client_operation_mobile[]" value="" label="Mobile" placeholder="Enter mobile"/>
            </div>
            <div class="col-md-4 mb-3">
                <button type="button" class="btn btn-primary mt-4" id="add-operation-contact">Add more contact</button>
            </div>
        @endif
    </div>
</div>

<div id="client-account-contact-container">
    <legend>Client Accounts Contact</legend>
    <div class="row mb-2" id="client-account-contact">
        @if(isset($clientSite->clientAccount) && $clientSite->clientAccount->count() > 0)
            @foreach($clientSite->clientAccount as $accountContact)
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_name[]" value="{{ $accountContact->name ?? '' }}" label="Name" placeholder="Enter name"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_position[]" value="{{ $accountContact->position ?? '' }}" label="Position" placeholder="Enter position"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_email[]" value="{{ $accountContact->email ?? '' }}" label="Email" placeholder="Enter email"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_telephone[]" value="{{ $accountContact->telephone_number ?? '' }}" label="Telephone" placeholder="Enter telephone"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_mobile[]" value="{{ $accountContact->mobile ?? '' }}" label="Mobile" placeholder="Enter mobile"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger mt-4" id="remove-account">Delete</button>
                </div>
                @endforeach
            @else
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_name[]" value="" label="Name" placeholder="Enter name"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_position[]" value="" label="Position" placeholder="Enter position"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_email[]" value="" label="Email" placeholder="Enter email"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_telephone[]" value="" label="Telephone" placeholder="Enter telephone"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <x-form-input name="client_account_mobile[]" value="" label="Mobile" placeholder="Enter mobile"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary mt-4" id="add-account-contact">Add more contact</button>
                </div>
            @endif
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
            newContactRow.find('button').toggleClass('btn-primary btn-danger').html('Delete').addClass('remove-contact');
            newContactRow.appendTo('#client-operation-contact-container');
            console.log('newContactRow',newContactRow)

            newContactRow.find('.remove-contact').click(function(){
                newContactRow.remove();
            });
        });

        $('#add-account-contact').click(function(){
            var newAccountRow = $('#client-account-contact').first().clone();
            newAccountRow.find('input').val('');
            newAccountRow.find('button').toggleClass('btn-primary btn-danger').html('Delete').addClass('remove-account');
            newAccountRow.appendTo('#client-account-contact-container');
            console.log('newContactRow',newAccountRow)

            newAccountRow.find('.remove-account').click(function(){
                newAccountRow.remove();
            });
        });
    });
</script>