<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\RateMaster;

class SecurityGuardImport implements ToModel, WithHeadingRow
{
    protected $errors = [];
    protected $rowIndex = 0;

    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {

        $this->rowIndex++;

        $validator = Validator::make($row, [
            'first_name'        => 'required',
            'email'             => 'nullable|email|unique:users,email',
            'phone_number'      => 'required|numeric|unique:users,phone_number',
            'date_of_joining'   => 'nullable|date_format:d-m-Y',
            'date_of_birth'     => 'required|date|before:date_of_joining|date_format:d-m-Y',
            'date_of_separation' => 'nullable|date_format:d-m-Y',
            'trn'               => 'nullable|unique:guard_additional_information,trn',
            'nis'               => 'nullable|unique:guard_additional_information,nis',
            'psra'              => 'nullable|unique:guard_additional_information,psra',
            'account_no'        => 'nullable|unique:users_bank_details,account_no',
            'guard_type'        => 'required',
            'guard_employed_as' => 'required',
            // 'trn_document'      => 'required',
            // 'nis_document'      => 'required',
            // 'psra_document'     => 'required',
            // 'birth_certificate' => 'required',
        ]);

        if ($validator->fails()) {
            $this->errors[] = [
                'row_index' => $this->rowIndex,
                'name'      => $row['first_name'],
                'status' => 'Failed',
                'failure_reason' => $validator->errors()->toArray(),
                'row' => $row
            ];
            return null;
        }

        $guardType = RateMaster::where('id', $row['guard_type'])->first();
        if (!$guardType) {
            $this->errors[] = [
                'row_index' => $this->rowIndex,
                'name' => $row['first_name'],
                'status' => 'Failed',
                'failure_reason' => "Guard type {$row['guard_type']} does not exist",
                'row' => $row
            ];
            return null;
        }

        $guardEmployedAs = RateMaster::where('id', $row['guard_employed_as'])->first();
        if (!$guardEmployedAs) {
            $this->errors[] = [
                'row_index' => $this->rowIndex,
                'name' => $row['first_name'],
                'status' => 'Failed',
                'failure_reason' => "Guard employed as {$row['guard_employed_as']} does not exist",
                'row' => $row
            ];
            return null;
        }

        $user = User::where('phone_number', $row['phone_number'])->first();
        if (!$user) {
            // Create a new user if not found
            $user = User::create([
                'first_name'   => $row["first_name"],
                'middle_name'  => !empty($row["middle_name"]) ? $row["middle_name"] : null,
                'last_name'    => !empty($row["last_name"]) ? $row["last_name"] : null,
                'surname'      => !empty($row["surname"]) ? $row["surname"] : null,
                'email'        => $row["email"],
                'phone_number' => !empty($row["phone_number"]) ? $row["phone_number"] : null,
                'password'     => Hash::make('Guard@12345'),
            ]);
            $user->assignRole('Security Guard');

            $this->errors[] = [
                'row_index' => $this->rowIndex,
                'name'      => $row['first_name'],
                'status'    => 'Success',
                'failure_reason' => null,
                'row'       => $row,
                'message'   => "User {$row['first_name']} successfully created and stored."
            ];
        } else {
            $this->errors[] = [
                'row_index' => $this->rowIndex,
                'name'      => $row['first_name'],
                'status' => 'Failed',
                'failure_reason' => "User with email {$row['email']} or phone number {$row['phone_number']} already exists",
                'row' => $row
            ];
            return null;
        }

        // Update or create related data
        $user->guardAdditionalInformation()->updateOrCreate([], [
            'trn'                 => isset($row["trn"]) ? $this->trnFormat($row["trn"]) : null,
            'nis'                 => $row["nis"] ?? NULL,
            'psra'                => $row["psra"] ?? NULL,
            'date_of_joining'     => $this->parseDate($row["date_of_joining"] ?? null),
            'date_of_birth'       => $this->parseDate($row["date_of_birth"] ?? null),
            /* 'employer_company_name' => $row["employer_company_name"] ?? null,
            'guards_current_rate' => $row["guards_current_rate"] ?? null,
            'location_code'       => $row["location_code"] ?? null,
            'location_name'       => $row["location_name"] ?? null,
            'client_code'         => $row["client_code"] ?? null,
            'client_name'         => $row["client_name"] ?? null, */
            'guard_type_id'          => $row["guard_type"] ?? null,
            'guard_employee_as_id' => $row["guard_employed_as"] ?? null,
            'date_of_seperation'  => $this->parseDate($row["date_of_seperation"] ?? null),
        ]);

        // Update contact details
        $user->contactDetail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'apartment_no'  => $row["apartment_no"] ?? null,
                'building_name' => $row["building_name"] ?? null,
                'street_name'   => $row["street_name"] ?? null,
                'parish'        => $row["parish"] ?? null,
                'city'          => $row["city"] ?? null,
                'postal_code'   => $row["postal_code"] ?? null,
            ]
        );

        // Update bank details
        $user->usersBankDetail()->updateOrCreate([], [
            'user_id'             => $user->id,
            'bank_name'           => $row["bank_name"] ?? null,
            'bank_branch_address' => $row["bank_branch_address"] ?? null,
            'account_no'          => $row["account_number"] ?? null,
            'account_type'        => $row["account_type"] ?? null,
            'routing_number'      => $row["routing_number"] ?? null,
            'recipient_id'        => $row["recipient_id"] ?? null
        ]);

        // Update next of kin details
        $user->usersKinDetail()->updateOrCreate([], [
            'user_id'       => $user->id,
            'surname'       => $row["kin_surname"] ?? null,
            'first_name'    => $row["kin_first_name"] ?? null,
            'middle_name'   => $row["kin_middle_name"] ?? null,
            'apartment_no'  => $row["kin_apartment_no"] ?? null,
            'building_name' => $row["kin_building_name"] ?? null,
            'street_name'   => $row["kin_street_name"] ?? null,
            'parish'        => $row["kin_parish"] ?? null,
            'city'          => $row["kin_city"] ?? null,
            'postal_code'   => $row["kin_postal_code"] ?? null,
            'email'         => $row["kin_email"] ?? null,
            'phone_number'  => $row["kin_phone_number"] ?? null,
        ]);

        $user->userDocuments()->updateOrCreate([], [
            'user_id'           => $user->id,
            'trn'               => Null,
            'nis'               => NULL,
            'psra'              => NULL,
            'birth_certificate' => NULL,
        ]);

        return null;
    }

    function uploadDoc($file, $destinationPath)
    {
        if ($file && file_exists($file)) {
            $filename = basename($file);

            $destination = public_path($destinationPath) . $filename;

            if (!file_exists($destination)) {
                copy($file, $destination); // Using copy instead of move
            }

            return asset($destinationPath . $filename); // Use asset() to generate the full public URL
        }

        return null;
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    /**
     * Get all errors encountered during the import process.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function trnFormat($trn)
    {
        $new = str_replace('-', '', $trn);
        return rtrim(chunk_split($new, 3, '-'), '-');
    }
}
