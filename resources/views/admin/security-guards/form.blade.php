<div class="row mb-2">
    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" label="First Name"
                placeholder="Enter First Name" required="true" />
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="middle_name" value="{{ old('middle_name', $user->middle_name ?? '') }}"
                label="Middle Name" placeholder="Enter Middle Name" />
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="surname" value="{{ old('surname', $user->surname ?? '') }}" label="Surname"
                placeholder="Enter Surname" />
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <x-form-input type="number" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? '') }}"
            label="Phone Number" placeholder="Enter Phone Number" required />
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <x-form-input name="password" value="{{ old('password') }}" label="Password" placeholder="Enter Password"
                type="password" required="true" />
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="mb-3">
        <?php
        $statusOptions = ['Active', 'Inactive', 'Hold'];
        ?>
        <label for="status">Status</label>
        <select name="user_status" id="user_status" class="form-control" {{ isset($user) && $user->status == 'Active' && Auth::user()->getRoleNames()->first() != 'Super Admin' ? 'disabled' : '' }}>
            <option value="" selected disabled>Select Status</option>
            @foreach ($statusOptions as $value)
                <option value="{{ $value }}"
                    {{ old('user_status', $user->status ?? 'Inactive') === $value ? 'selected' : '' }}>
                    {{ $value }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="col-md-4">
    <div class="mb-3">
        <label class="form-check-label" for="SwitchCheckSizelg">Statutory Applicable</label>
        <div class="form-check form-switch form-switch-lg mb-3" dir="ltr">
            <input class="form-check-input" type="checkbox" id="SwitchCheckSizelg" name="is_statutory"
                data-on="Statutory" data-off="Non-Statutory" value="0"
                {{ isset($user) && $user->is_statutory == 0 ? 'checked' : '' }} {{ !isset($user) ? 'checked' : '' }}>
            <!-- For create page, default to checked -->
        </div>
        <input type="hidden" id="is_statutory" name="is_statutory"
            value="{{ isset($user) ? $user->is_statutory : 0 }}">
    </div>
</div>

<fieldset class="col-md-12 mb-3">
    <legend>Guard Credentials</legend>
    <div class="row mb-2">
        <div class="col-md-4 mb-3">
            <x-form-input name="trn" value="{{ old('trn', $user->guardAdditionalInformation->trn ?? '') }}"
                label="Guard's TRN" placeholder="Enter Guard's TRN" oninput="formatInput(this, 'trn')" maxlength="11" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="nis" value="{{ old('nis', $user->guardAdditionalInformation->nis ?? '') }}"
                label="NIS/NHT Number" placeholder="Enter NIS/NHT Number" maxlength="7" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="psra" value="{{ old('psra', $user->guardAdditionalInformation->psra ?? '') }}"
                label="PSRA Registration No" placeholder="Enter PSRA Registration No"
                oninput="formatInput(this, 'psra')" maxlength="9" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="date_of_joining"
               value="{{ old('date_of_joining', isset($user) && $user->guardAdditionalInformation && $user->guardAdditionalInformation->date_of_joining ? \Carbon\Carbon::parse($user->guardAdditionalInformation->date_of_joining)->format('d-m-Y') : '') }}"
                label="Guard's Date of Joining" placeholder="Enter Date of Joining" class="date-picker"
                type="text" />
        </div>

        <div class="col-md-4 mb-2">
            <x-form-input name="date_of_birth"
                value="{{ old('date_of_birth', isset($user) && $user->guardAdditionalInformation ? \Carbon\Carbon::parse($user->guardAdditionalInformation->date_of_birth)->format('d-m-Y') : '') }}"
                label="Date of Birth" class="date-of-birth" placeholder="Enter Date of Birth" type="text" required="true" />
        </div>

        {{--<div class="col-md-4 mb-3">
             <x-form-input name="employer_company_name"
                value="{{ old('employer_company_name', $user->guardAdditionalInformation->employer_company_name ?? '') }}"
                label="Employer Company Name" placeholder="Enter Employer Company Name" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="current_rate"
                value="{{ old('current_rate', $user->guardAdditionalInformation->guards_current_rate ?? '') }}"
                label="Guard's Current Rate" placeholder="Enter Guard's Current Rate" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="location_code"
                value="{{ old('location_code', $user->guardAdditionalInformation->location_code ?? '') }}"
                label="Location Code" placeholder="Enter Location Code" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="location_name"
                value="{{ old('location_name', $user->guardAdditionalInformation->location_name ?? '') }}"
                label="Location Name" placeholder="Enter Location Name" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="client_code"
                value="{{ old('client_code', $user->guardAdditionalInformation->client_code ?? '') }}"
                label="Client Code" placeholder="Enter Client Code" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="client_name"
                value="{{ old('client_name', $user->guardAdditionalInformation->client_name ?? '') }}"
                label="Client Name" placeholder="Enter Client Name" />
        </div> --}}

        <div class="col-md-4 mb-3">
            <label for="guard_employee_as">Guard Employed As<span class="text-danger">*</span></label>
            <select name="guard_employee_as_id" id="guard_employee_as_id" class="form-control{{ $errors->has('guard_employee_as_id') ? ' is-invalid' : '' }}">
                <option value="" selected disabled>Select Guard Employed As</option>
                @foreach ($rateMasters as $rateMaster)
                    <option value="{{ $rateMaster->id }}" @selected(old('guard_employee_as_id', $user->guardAdditionalInformation->guard_employee_as_id ?? null) == $rateMaster->id)>
                        {{ $rateMaster->guard_type }}
                    </option>
                @endforeach
            </select>
            @error('guard_employee_as_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="guard_type">Guard Duty Type<span class="text-danger">*</span></label>
            <select name="guard_type_id" id="guard_type" class="form-control{{ $errors->has('guard_type_id') ? ' is-invalid' : '' }}">
                <option value="" selected disabled>Select Guard Type</option>
                @foreach ($rateMasters as $rateMaster)
                    <option value="{{ $rateMaster->id }}" @selected(old('guard_type_id', $user->guardAdditionalInformation->guard_type_id ?? null) == $rateMaster->id)>
                        {{ $rateMaster->guard_type }}
                    </option>
                @endforeach
            </select>
            @error('guard_type_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="date_of_seperation"
                value="{{ old('date_of_seperation', isset($user) && $user->guardAdditionalInformation && $user->guardAdditionalInformation->date_of_seperation ? \Carbon\Carbon::parse($user->guardAdditionalInformation->date_of_seperation)->format('d-m-Y') : '') }}"
                label="Date of Separation" class="date_of_separation" placeholder="Enter Date of Separation"
                type="text" />
        </div>
    </div>
</fieldset>

<fieldset class="col-md-12 mb-3">
    <legend>Contact details</legend>
    <div class="row mb-2">
        <div class="col-md-4 mb-3">
            <x-form-input name="apartment_no"
                value="{{ old('apartment_no', $user->contactDetail->apartment_no ?? '') }}" label="Apartment No"
                placeholder="Enter Apartment No" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="building_name"
                value="{{ old('building_name', $user->contactDetail->building_name ?? '') }}" label="Building Name"
                placeholder="Enter Building Name" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="street_name"
                value="{{ old('street_name', $user->contactDetail->street_name ?? '') }}" label="Street Name"
                placeholder="Enter Street Name" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="parish" value="{{ old('parish', $user->contactDetail->parish ?? '') }}"
                label="Parish" placeholder="Enter Parish" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="city" value="{{ old('city', $user->contactDetail->city ?? '') }}" label="City"
                placeholder="Enter City" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="postal_code"
                value="{{ old('postal_code', $user->contactDetail->postal_code ?? '') }}" label="Postal Code"
                placeholder="Enter Postal Code" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="email" value="{{ old('email', $user->email ?? '') }}" label="Email"
                placeholder="Enter Email" />
        </div>
    </div>
</fieldset>

<fieldset class="col-md-12 mb-3">
    <legend>Bank details</legend>
    <div class="row mb-2">
        <div class="col-md-4 mb-3">
            <x-form-input name="bank_name" value="{{ old('bank_name', $user->usersBankDetail->bank_name ?? '') }}"
                label="Bank Name" placeholder="Enter Bank Name" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="branch"
                value="{{ old('branch', $user->usersBankDetail->bank_branch_address ?? '') }}"
                label="Bank Branch Address" placeholder="Enter Branch Address" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="account_number"
                value="{{ old('account_number', $user->usersBankDetail->account_no ?? '') }}" label="Account Number"
                placeholder="Enter Account Number" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="account_type"
                value="{{ old('account_type', $user->usersBankDetail->account_type ?? '') }}" label="Account Type"
                placeholder="Enter Account Type" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="routing_number"
                value="{{ old('routing_number', $user->usersBankDetail->routing_number ?? '') }}"
                label="Routing Number" placeholder="Enter Routing Number" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="recipient_id"
                value="{{ old('recipient_id', $user->usersBankDetail->recipient_id ?? '') }}"
                label="Recipient Id" placeholder="Enter Recipient Id" />
        </div>
    </div>
</fieldset>

<fieldset class="col-md-12 mb-3">
    <legend>Next of Kin details</legend>
    <div class="row mb-2">
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_surname" value="{{ old('kin_surname', $user->usersKinDetail->surname ?? '') }}"
                label="Surname" placeholder="Enter Surname" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="kin_first_name"
                value="{{ old('kin_first_name', $user->usersKinDetail->first_name ?? '') }}" label="First Name"
                placeholder="Enter First Name" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="kin_middle_name"
                value="{{ old('kin_middle_name', $user->usersKinDetail->middle_name ?? '') }}" label="Middle Name"
                placeholder="Enter Middle Name" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_apartment_no"
                value="{{ old('kin_apartment_no', $user->usersKinDetail->apartment_no ?? '') }}" label="Apartment No"
                placeholder="Enter Apartment No" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="kin_building_name"
                value="{{ old('kin_building_name', $user->usersKinDetail->building_name ?? '') }}"
                label="Building Name" placeholder="Enter Building Name" />
        </div>

        <div class="col-md-4 mb-3">
            <x-form-input name="kin_street_name"
                value="{{ old('kin_street_name', $user->usersKinDetail->street_name ?? '') }}" label="Street Name"
                placeholder="Enter Street Name" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_parish" value="{{ old('kin_parish', $user->usersKinDetail->parish ?? '') }}"
                label="Parish" placeholder="Enter Parish" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_city" value="{{ old('kin_city', $user->usersKinDetail->city ?? '') }}"
                label="City" placeholder="Enter City" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_postal_code"
                value="{{ old('kin_postal_code', $user->usersKinDetail->postal_code ?? '') }}" label="Postal Code"
                placeholder="Enter Postal Code" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_email" value="{{ old('kin_email', $user->usersKinDetail->email ?? '') }}"
                label="Email" placeholder="Enter Email" />
        </div>
        <div class="col-md-4 mb-3">
            <x-form-input name="kin_phone_number"
                value="{{ old('kin_phone_number', $user->usersKinDetail->phone_number ?? '') }}" label="Phone Number"
                placeholder="Enter Phone Number" />
        </div>
    </div>
</fieldset>

<fieldset class="col-md-12 mb-3">
    <legend>User Documents</legend>
    <div class="row mb-2">
        <div class="col-md-4 mb-3">
            <x-form-input type="file" name="trn_doc" label="TRN Document" accept="application/pdf"
                onchange="showLink(this, 'trn_link', 'old_trn_link')" />
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
            <x-form-input type="file" name="nis_doc" label="NIS Document" accept="application/pdf"
                onchange="showLink(this, 'nis_link', 'old_nis_link')" />
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
            <x-form-input type="file" name="psra_doc" label="PSRA Document" accept="application/pdf"
                onchange="showLink(this, 'psra_link', 'old_psra_doc')" />
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
            <x-form-input type="file" name="birth_certificate" label="Birth Certificate" accept="application/pdf"
                onchange="showLink(this, 'birth_link', 'old_birth_certificate')" />
            @if ($user->userDocuments->birth_certificate ?? '')
                <div class="preview mt-2" id="old_birth_certificate">
                    <label>Birth Certificate:</label>
                    <a href="{{ asset($user->userDocuments->birth_certificate) }}" target="_blank">View Birth
                        Certificate</a>
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
<script>
    function formatInput(input, type) {
        let value = input.value.replace(/\D/g, ''); // Remove non-digit characters

        if (type === 'trn') {
            // TRN: Format as XXX-XXX-XXX (9 digits)
            if (value.length > 3 && value.length <= 6) {
                value = value.replace(/(\d{3})(\d{0,3})/, '$1-$2');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1-$2-$3');
            }
        } else if (type === 'psra') {
            // PSRA: No special formatting, just remove non-digits
            value = value.slice(0, 9); // Limit to 9 digits
        }

        input.value = value;
    }

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

        if ($('#SwitchCheckSizelg').prop('checked')) {
            $('#is_statutory').val('0'); // checked -> 0
            $('#SwitchCheckSizelg').val('0');
        } else {
            $('#is_statutory').val('1'); // unchecked -> 1
            $('#SwitchCheckSizelg').val('1');

        }

        $('#SwitchCheckSizelg').change(function() {
            if ($(this).prop('checked')) {
                $('#is_statutory').val('0'); // checked -> 0
                $('#SwitchCheckSizelg').val('0');
            } else {
                $('#is_statutory').val('1'); // unchecked -> 1
                $('#SwitchCheckSizelg').val('1');
            }
        });
    });
</script>
