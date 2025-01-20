<?php

namespace App\Imports;

use App\Models\ClientSite;
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
            'client_id'           => 'required',
            'location_code'       => 'required|unique:client_sites,location_code',
            'latitude'            => 'required',
            'longitude'           => 'required',
            'radius'              => 'required',
            'vanguard_manager_id' => 'required'
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

        ClientSite::create([
            'client_id'                     => $row['client_id'],
            'location_code'                 => $row['location_code'],
            'parish'                        => $row['parish'],
            'billing_address'               => $row['billing_address'],
            'manager_id'                    => $row['vanguard_manager_id'],
            'contact_operation'             => $row['contact_operation'],
            'telephone_number'              => $row['telephone_number'],
            'email'                         => $row['email'],
            'invoice_recipient_main'        => $row['invoice_recipient_main'],
            'invoice_recipient_copy'        => $row['invoice_recipient_copy'],
            'account_payable_contact_name'  => $row['account_payable_contact_name'],
            'email_2'                       => $row['email_2'],
            'number'                        => $row['number'],
            'number_2'                      => $row['number_2'],
            'account_payable_contact_email' => $row['account_payable_contact_email'],
            'email_3'                       => $row['email_3'],
            'telephone_number_2'            => $row['telephone_number_2'],
            'latitude'                      => $row['latitude'],
            'longitude'                     => $row['longitude'],
            'radius'                        => $row['radius']
        ]);

        $this->importResults[] = [
            'row_index'      => $this->rowIndex,
            'status'         => 'Success',
            'failure_reason' => 'Updated record for Client ID ' . $row['client_id'],
        ];
       
        return null;
    }

    public function getImportResults()
    {
        return collect($this->importResults);
    }
}
