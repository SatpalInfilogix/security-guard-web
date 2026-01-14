<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Spatie\Permission\Models\Role;

class EmployeesExport implements FromArray
{
    public function array(): array
    {
        $userRole = Role::find(9); 

        $employees = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with([
            'guardAdditionalInformation',
            'contactDetail',
            'usersBankDetail',
            'usersKinDetail',
            'userDocuments',
        ])->latest()->get();

        $employeeArray = $employees->map(function ($employee) {
            $guardInfo = $employee->guardAdditionalInformation;
            $contact   = $employee->contactDetail;
            $bank      = $employee->usersBankDetail;
            $kin       = $employee->usersKinDetail;

            return [
                "Emp Code"               => $employee->user_code,
                "First Name"             => $employee->first_name,
                "Middle Name"            => $employee->middle_name,
                "Surname"                => $employee->surname,
                "Phone Number"           => $employee->phone_number ?? '',
                "TRN"                    => $this->trnFormat(optional($guardInfo)->trn ?? ''),
                "NIS"                    => optional($guardInfo)->nis ?? '',
                "Date Of Joining"        => optional($guardInfo)->date_of_joining ? Carbon::parse($guardInfo->date_of_joining)->format('d-m-Y') : '',
                "Date Of Birth"          => optional($guardInfo)->date_of_birth ? Carbon::parse($guardInfo->date_of_birth)->format('d-m-Y') : '',
                "Position"               => optional($guardInfo)->position ?? '',
                "Department"             => optional($guardInfo)->department ?? '',
                "Location"               => optional($guardInfo)->location ?? '',
                "Date Of Separation"     => optional($guardInfo)->date_of_seperation ? Carbon::parse($guardInfo->date_of_seperation)->format('d-m-Y') : '',
                "Apartment No"           => optional($contact)->apartment_no ?? '',
                "Building Name"          => optional($contact)->building_name ?? '',
                "Street Name"            => optional($contact)->street_name ?? '',
                "Parish"                 => optional($contact)->parish ?? '',
                "City"                   => optional($contact)->city ?? '',
                "Postal Code"            => optional($contact)->postal_code ?? '',
                "Email"                  => $employee->email ?? '',
                "Personal Email"         => optional($contact)->personal_email ?? '',
                "Work Phone Number"      => optional($contact)->work_phone_number ?? '',
                "Personal Phone Number"  => optional($contact)->personal_phone_number ?? '',
                "Bank Name"              => optional($bank)->bank_name ?? '',
                "Bank Branch Address"    => optional($bank)->bank_branch_address ?? '',
                "Account Number"         => optional($bank)->account_no ?? '',
                "Account Type"           => optional($bank)->account_type ?? '',
                "Routing Number"         => optional($bank)->routing_number ?? '',
                "Kin Surname"            => optional($kin)->surname ?? '',
                "Kin First Name"         => optional($kin)->first_name ?? '',
                "Kin Middle Name"        => optional($kin)->middle_name ?? '',
                "Kin Apartment No"       => optional($kin)->apartment_no ?? '',
                "Kin Building Name"      => optional($kin)->building_name ?? '',
                "Kin Street Name"        => optional($kin)->street_name ?? '',
                "Kin Parish"             => optional($kin)->parish ?? '',
                "Kin City"               => optional($kin)->city ?? '',
                "Kin Postal Code"        => optional($kin)->postal_code ?? '',
                "Kin Email"              => optional($kin)->email ?? '',
                "Kin Phone Number"       => optional($kin)->phone_number ?? '',
            ];
        });

        return collect([$employeeArray->first() ? array_keys($employeeArray->first()) : []])
            ->merge($employeeArray)
            ->toArray();
    }

    public function trnFormat($trn)
    {
        $new = str_replace('-', '', $trn);
        return rtrim(chunk_split($new, 3, '-'), '-');
    }
}
