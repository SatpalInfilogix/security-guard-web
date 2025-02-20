<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmployeeImport implements ToModel, WithHeadingRow
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
            'date_of_separation'=> 'nullable|date_format:d-m-Y',
            'trn'               => 'nullable|unique:guard_additional_information,trn',
            'nis'               => 'nullable|unique:guard_additional_information,nis',
            'account_no'        => 'nullable|unique:users_bank_details,account_no',
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
    
        $user = User::where('phone_number', $row['phone_number'])->first();
        if (!$user) {
            $user = User::create([
                'first_name'   => $row["first_name"],
                'middle_name'  => !empty($row["middle_name"]) ? $row["middle_name"] : null,
                'last_name'    => !empty($row["last_name"]) ? $row["last_name"] : null,
                'surname'      => !empty($row["surname"]) ? $row["surname"] : null,
                'email'        => $row["email"],
                'phone_number' => !empty($row["phone_number"]) ? $row["phone_number"] : null,
                'password'     => Hash::make('Guard@12345'),
            ]);
            $user->assignRole('Employee');

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
            'date_of_joining'     => $this->parseDate($row["date_of_joining"] ?? null),
            'date_of_birth'       => $this->parseDate($row["date_of_birth"] ?? null),
            'position'            => $row['position'] ?? null,
            'department'          => $row['department'] ?? null,
            'location'            => $row['location'] ?? null,
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
                'personal_email'=> $row["personal_email"] ?? null,
                'work_phone_number' => $row["work_phone_number"] ?? null,
                'personal_phone_number' => $row["personal_phone"] ?? null,
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
}

