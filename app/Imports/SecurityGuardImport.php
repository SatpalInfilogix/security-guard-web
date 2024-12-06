<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

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
            'phone_number'      => 'required|unique:users,phone_number',
            'date_of_joining'   => 'nullable|date_format:Y-m-d',
            'date_of_birth'     => 'nullable|date_format:Y-m-d',
            'date_of_separation'=> 'nullable|date_format:Y-m-d',
            'trn'               => 'nullable|unique:guard_additional_information,trn',
            'nis'               => 'nullable|unique:guard_additional_information,nis',
            'psra'              => 'nullable|unique:guard_additional_information,psra',
            'account_no'        => 'nullable|unique:users_bank_details,account_no'
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
            return null; // Skip processing if validation fails
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
            'trn'                 => $row["trn"] ?? NULL,
            'nis'                 => $row["nis"] ?? NULL,
            'psra'                => $row["psra"] ?? NULL,
            'date_of_joining'     => !empty($row["date_of_joining"]) ? $row["date_of_joining"] : null,
            'date_of_birth'       => !empty($row["date_of_birth"]) ? $row["date_of_birth"] : null,
            'employer_company_name' => $row["employer_company_name"] ?? null,
            'guards_current_rate' => $row["guards_current_rate"] ?? null,
            'location_code'       => $row["location_code"] ?? null,
            'location_name'       => $row["location_name"] ?? null,
            'client_code'         => $row["client_code"] ?? null,
            'client_name'         => $row["client_name"] ?? null,
            'guard_type_id'       => $row["guard_type_id"] ?? null,
            'employed_as'         => $row["employed_as"] ?? null,
            'date_of_seperation'  => !empty($row["date_of_seperation"]) ? $row["date_of_seperation"] : null,
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
            'account_no'          => $row["account_no"] ?? null,
            'account_type'        => $row["account_type"] ?? null,
            'routing_number'      => $row["routing_number"] ?? null,
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
            'trn'               => $this->uploadDoc($row['trn_document'] ?? null, 'uploads/user-documents/trn/'),
            'nis'               => $this->uploadDoc($row['nis_document'] ?? null, 'uploads/user-documents/nis/'),
            'psra'              => $this->uploadDoc($row['psra_document'] ?? null, 'uploads/user-documents/psra/'),
            'birth_certificate' => $this->uploadDoc($row['birth_certificate'] ?? null, 'uploads/user-documents/birth_certificate/'),
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

    /**
     * Get all errors encountered during the import process.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
