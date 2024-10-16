<div class="row mb-2">
    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" label="First Name" placeholder="Enter your First Name"  required="true" />
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="middle_name" value="{{ old('middle_name', $user->middle_name ?? '') }}" label="Middle Name" placeholder="Enter your Middle Name"/>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="surname" value="{{ old('surname', $user->surname ?? '') }}" label="Surname" placeholder="Enter your Surname"/>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <x-form-input name="password" value="{{ old('password') }}" label="Password" placeholder="Enter your Password" type="password"  required="true" />
        </div>
    </div>
</div>
    <div class="col-md-6">
        <div class="mb-3">
            <?php
                $statusOptions = ['Active', 'Inactive', 'Hold'];
            ?>
            <label for="status">Status</label>
            <select name="user_status" id="user_status" class="form-control">
                <option value="" selected disabled>Select Status</option>
                @foreach ($statusOptions as $value)
                    <option value="{{ $value }}" {{ (old('status', $user->status ?? '') === $value) ? 'selected' : '' }}>
                        {{ $value }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <fieldset class="col-md-12 mb-3">
        <legend>Addtitional Detail</legend>
        <div class="row mb-2">
            <div class="col-md-4 mb-3">
                <x-form-input name="trn" value="{{ old('trn', $user->guardAdditionalInformation->trn ?? '') }}" label="Guard's TRN" placeholder="Enter your Guard's TRN"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="nis" value="{{ old('nis', $user->guardAdditionalInformation->nis ?? '') }}" label="NIS/NHT Number" placeholder="Enter your NIS/NHT Number"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="psra" value="{{ old('psra', $user->guardAdditionalInformation->psra ?? '') }}" label="PSRA Registration No" placeholder="Enter your PSRA Registration No"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="date_of_joining" value="{{ old('date_of_joining', $user->guardAdditionalInformation->date_of_joining ?? '') }}" label="Guard's Date of Joining" placeholder="Enter your Date of Joining" class="date-picker" type="text"/>
            </div>

            <div class="col-md-4 mb-2">
                <x-form-input name="date_of_birth" value="{{ old('date_of_birth', $user->guardAdditionalInformation->date_of_birth ?? '') }}" label="Date of Birth"  class="date-of-birth" placeholder="Enter your Date of Birth" type="text"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="employer_company_name" value="{{ old('employer_company_name', $user->guardAdditionalInformation->employer_company_name ?? '') }}" label="Employer Company Name" placeholder="Enter your Employer Company Name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="current_rate" value="{{ old('current_rate', $user->guardAdditionalInformation->guards_current_rate ?? '') }}" label="Guard's Current Rate" placeholder="Enter your Guard's Current Rate"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="location_code" value="{{ old('location_code', $user->guardAdditionalInformation->location_code ?? '') }}" label="Location Code" placeholder="Enter your Location Code"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="location_name" value="{{ old('location_name', $user->guardAdditionalInformation->location_name ?? '') }}" label="Location Name" placeholder="Enter your Location Name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="client_code" value="{{ old('client_code', $user->guardAdditionalInformation->client_code ?? '') }}" label="Client Code" placeholder="Enter your Client Code"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="client_name" value="{{ old('client_name', $user->guardAdditionalInformation->client_name ?? '') }}" label="Client Name" placeholder="Enter your Client Name"/>
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="guard_type">Guard Type</label>
                <select name="guard_type_id" id="guard_type" class="form-control">
                    <option value="" selected disabled>Select Guard Type</option>
                  
                    @foreach ($rateMasters as $rateMaster)
                    <option value="{{ $rateMaster->id }}" 
                        @selected((old('guard_type_id', $user->guardAdditionalInformation->guard_type_id ?? null) == $rateMaster->id))>
                        {{ $rateMaster->guard_type }}
                    </option>
                    @endforeach
                </select>
            </div>            
            <div class="col-md-4 mb-3">
                <x-form-input name="employed_as" value="{{ old('employed_as', $user->guardAdditionalInformation->employed_as ?? '') }}" label="Employed As" placeholder="Enter your Employed As"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="date_of_seperation" value="{{ old('date_of_seperation', $user->guardAdditionalInformation->date_of_seperation ?? '') }}" label="Date of Separation"  class="date_of_separation" placeholder="Enter your Date of Separation" type="text"/>
            </div>
        </div>
    </fieldset>

    <fieldset class="col-md-12 mb-3">
        <legend>Contact details</legend>
        <div class="row mb-2">
            <div class="col-md-4 mb-3">
                <x-form-input name="apartment_no" value="{{ old('apartment_no', $user->contactDetail->apartment_no ?? '') }}" label="Apartment No" placeholder="Enter your Apartment No"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="building_name" value="{{ old('building_name', $user->contactDetail->building_name ?? '') }}" label="Building Name" placeholder="Enter your Building Name"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="street_name" value="{{ old('street_name', $user->contactDetail->street_name ?? '') }}" label="Street Name" placeholder="Enter your Street Name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="parish" value="{{ old('parish', $user->contactDetail->parish ?? '') }}" label="Parish" placeholder="Enter your Parish"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="city" value="{{ old('city', $user->contactDetail->city ?? '') }}" label="City" placeholder="Enter your City"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="postal_code" value="{{ old('postal_code', $user->contactDetail->postal_code ?? '') }}" label="Postal Code" placeholder="Enter your Postal Code"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="email" value="{{ old('email', $user->email ?? '') }}" label="Email" placeholder="Enter your Email"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="phone_number" value="{{ old('phone_number', $user->phone_number ?? '') }}" label="Phone Number" placeholder="Enter your Phone Number"/>
            </div>
        </div>
    </fieldset>

    <fieldset class="col-md-12 mb-3">
        <legend>Bank details</legend>
        <div class="row mb-2">
            <div class="col-md-4 mb-3">
                <x-form-input name="bank_name" value="{{ old('bank_name', $user->usersBankDetail->bank_name ?? '') }}" label="Bank Name" placeholder="Enter your Bank Name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="branch" value="{{ old('branch', $user->usersBankDetail->bank_branch_address ?? '') }}" label="Bank Branch Address" placeholder="Enter your Branch Address"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="account_number" value="{{ old('account_number', $user->usersBankDetail->account_no ?? '') }}" label="Account Number" placeholder="Enter your Account Number"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="account_type" value="{{ old('account_type', $user->usersBankDetail->account_type ?? '') }}" label="Account Type" placeholder="Enter your Account Type"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="routing_number" value="{{ old('routing_number', $user->usersBankDetail->routing_number ?? '') }}" label="Routing Number" placeholder="Enter your Routing Number"/>
            </div>
        </div>
    </fieldset>
    
    <fieldset class="col-md-12 mb-3">
        <legend>Next of Kin details</legend>
        <div class="row mb-2">
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_surname" value="{{ old('kin_surname', $user->usersKinDetail->surname ?? '') }}" label="Surname" placeholder="Enter your Surname"/>
            </div>
        
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_first_name" value="{{ old('kin_first_name', $user->usersKinDetail->first_name ?? '') }}" label="First Name" placeholder="Enter your First Name"/>
            </div>
        
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_middle_name" value="{{ old('kin_middle_name', $user->usersKinDetail->middle_name ?? '') }}" label="Middle Name" placeholder="Enter your Middle Name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_apartment_no" value="{{ old('kin_apartment_no', $user->usersKinDetail->apartment_no ?? '') }}" label="Apartment No" placeholder="Enter your Apartment No"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="kin_building_name" value="{{ old('kin_building_name', $user->usersKinDetail->building_name ?? '') }}" label="Building Name" placeholder="Enter your Building Name"/>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input name="kin_street_name" value="{{ old('kin_street_name', $user->usersKinDetail->street_name ?? '') }}" label="Street Name" placeholder="Enter your Street Name"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_parish" value="{{ old('kin_parish', $user->usersKinDetail->parish ?? '') }}" label="Parish" placeholder="Enter your Parish"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_city" value="{{ old('kin_city', $user->usersKinDetail->city ?? '') }}" label="City" placeholder="Enter your City"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_postal_code" value="{{ old('kin_postal_code', $user->usersKinDetail->postal_code ?? '') }}" label="Postal Code" placeholder="Enter your Postal Code"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_email" value="{{ old('kin_email', $user->usersKinDetail->email ?? '') }}" label="Email" placeholder="Enter your Email"/>
            </div>
            <div class="col-md-4 mb-3">
                <x-form-input name="kin_phone_number" value="{{ old('kin_phone_number', $user->usersKinDetail->phone_number ?? '') }}" label="Phone Number" placeholder="Enter your Phone Number"/>
            </div>
        </div>
    </fieldset>

    <fieldset class="col-md-12 mb-3">
        <legend>User Documents</legend>
        <div class="row mb-2">
            <div class="col-md-4 mb-3">
                <x-form-input type="file" name="trn_doc" label="TRN Document" accept="application/pdf" onchange="showLink(this, 'trn_link', 'old_trn_link')"  required="true"/>
                @if ($user->userDocuments->trn ?? '')
                    <div class="preview mt-2" id="old_trn_link">
                        <label>TRN Document:</label>
                        <a href="{{ asset($user->userDocuments->trn) }}" target="_blank">View TRN Document</a>
                    </div>
                @endif
                <div id="trn_link" class="mt-2" style="display:none;">
                    <label>TRN Document:</label>
                    <a href="#" target="_blank">Preview TRN Document</a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input type="file" name="nis_doc" label="NIS Document" accept="application/pdf" onchange="showLink(this, 'nis_link', 'old_nis_link')"  required="true" />
                @if ($user->userDocuments->nis ?? '')
                    <div class="preview mt-2" id="old_nis_link">
                        <label>NIS Document:</label>
                        <a href="{{ asset($user->userDocuments->nis) }}" target="_blank">View NIS Document</a>
                    </div>
                @endif
                <div id="nis_link" class="mt-2" style="display:none;">
                    <label>NIS Document:</label>
                    <a href="#" target="_blank">Preview NIS Document</a>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <x-form-input type="file" name="psra_doc" label="PSRA Document" accept="application/pdf" onchange="showLink(this, 'psra_link', 'old_psra_doc')" required="true"/>
                @if ($user->userDocuments->psra ?? '')
                    <div class="preview mt-2" id="old_psra_doc">
                        <label>PSRA Document:</label>
                        <a href="{{ asset($user->userDocuments->psra) }}" target="_blank">View PSRA Document</a>
                    </div>
                @endif
                <div id="psra_link" class="mt-2" style="display:none;">
                    <label>PSRA Document:</label>
                    <a href="#" target="_blank">Preview PSRA Document</a>
                </div>
            </div>
    
            <div class="col-md-4 mb-3">
                <x-form-input type="file" name="birth_certificate" label="Birth Certificate" accept="application/pdf" onchange="showLink(this, 'birth_link', 'old_birth_certificate')"  required="true" />
                @if ($user->userDocuments->birth_certificate ?? '')
                    <div class="preview mt-2" id="old_birth_certificate">
                        <label>Birth Certificate:</label>
                        <a href="{{ asset($user->userDocuments->birth_certificate) }}" target="_blank">View Birth Certificate</a>
                    </div>
                @endif
                <div id="birth_link" class="mt-2" style="display:none;">
                    <label>Birth Certificatet:</label>
                    <a href="#" target="_blank">Preview Birth Certificate</a>
                </div>
            </div>
        </div>
    </fieldset>

    <div class="row mb-2">
        <div class="col-lg-6 mb-2">
            <button type="submit" class="btn btn-primary w-md">Submit</button>
        </div>
    </div>

    <x-include-plugins :plugins="['datePicker', 'guardImage']"></x-include-plugins>
