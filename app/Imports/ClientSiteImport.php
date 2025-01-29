<?php

namespace App\Imports;

use App\Models\ClientSite;
use App\Models\ClientOperation;
use App\Models\ClientAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ClientSiteImport implements ToModel, WithHeadingRow
{
    protected $importResults = [];
    protected $rowIndex = 0;

    public function model(array $row)
    {
        $this->rowIndex++;

        $validator = Validator::make($row, [
            'client_id'       => 'required',
            'location'        => 'required',
            'location_code'   => 'required|unique:client_sites,location_code',
            'latitude'        => 'required',
            'longitude'       => 'required',
            'radius'          => 'required',
            'manager_id'      => 'required'
        ]);

        if ($validator->fails()) {
            $this->importResults[] = [
                'row_index'      => $this->rowIndex,
                'guard_id'       => $row['client_id'],
                'status'         => 'Failed',
                'failure_reason' => $validator->errors()->toArray(),
                'row'            => $row
            ];
            return null;
        }

        $clientSite = ClientSite::create([
            'client_id'         => $row['client_id'],
            'location_code'     => $row['location_code'],
            'location'          => $row['location'],
            'region_code'       => $row['region_code'],
            'region'            => $row['region'],
            'area_code'         => $row['area_code'],
            'area'              => $row['area'],
            'latitude'          => $row['latitude'],
            'longitude'         => $row['longitude'],
            'radius'            => $row['radius'],
            'sr_manager'        => $row['sr_manager'],
            'sr_manager_email'  => $row['sr_manager_email'],
            'manager_id'        => $row['manager_id'],
            'manager_email'     => $row['manager_email'],
            'supervisor'        => $row['supervisor'],
            'supervisor_email'  => $row['supervisor_email'],
            // 'status'            => $row['service_status'],
            'unit_no_client'    => $row['unit_no_client'],
            'building_name_client' => $row['building_name_client'],
            'street_no_client'  => $row['street_no_client'],
            'street_road_client' => $row['street_road_client'],
            'parish_client'     => $row['parish_client'],
            'country_client'    => $row['country_client'],
            'postal_code_client' => $row['postal_code_client'],
            'unit_no_location'  => $row['unit_no_location'],
            'building_name_location' => $row['building_name_location'],
            'street_no_location' => $row['street_no_location'],
            'street_road_location' => $row['street_road_location'],
            'parish_location'   => $row['parish_location'],
            'country_location'  => $row['country_location'],
            'postal_code_location' => $row['postal_code_location'],
        ]);

        if($clientSite) {
            $operationNames = explode(',', $row['client_operation_names']);
            $operationPositions = explode(',', $row['client_operation_position']);
            $operationEmails = explode(',', $row['client_operation_email']);
            $operationTelephones = explode(',', $row['client_operation_telephone']);
            $operationMobiles = explode(',', $row['client_operation_mobile']);

            foreach ($operationNames as $index => $name) {
                ClientOperation::create([
                    'client_site_id'       => $clientSite->id,
                    'name'                 => $name,
                    'position'             => $operationPositions[$index] ?? null,
                    'email'                => $operationEmails[$index] ?? null,
                    'telephone_number'     => $operationTelephones[$index] ?? null,
                    'mobile'               => $operationMobiles[$index] ?? null,
                ]);
            }

            // Split client account data into arrays and create entries
            $accountNames = explode(',', $row['client_account_name']);
            $accountPositions = explode(',', $row['client_account_position']);
            $accountEmails = explode(',', $row['client_account_email']);
            $accountTelephones = explode(',', $row['client_account_telephone']);
            $accountMobiles = explode(',', $row['client_account_mobile']);

            foreach ($accountNames as $index => $name) {
                ClientAccount::create([
                    'client_site_id'       => $clientSite->id,
                    'name'                 => $name,
                    'position'             => $accountPositions[$index] ?? null,
                    'email'                => $accountEmails[$index] ?? null,
                    'telephone_number'     => $accountTelephones[$index] ?? null,
                    'mobile'               => $accountMobiles[$index] ?? null,
                ]);
            }
        }

        $this->importResults[] = [
            'row_index'      => $this->rowIndex,
            'status'         => 'Success',
            'failure_reason' => 'Created record for Client ID ' . $row['client_id'],
        ];
       
        return null;
    }

    public function getImportResults()
    {
        return collect($this->importResults);
    }
}
