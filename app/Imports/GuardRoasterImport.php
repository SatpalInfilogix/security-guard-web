<?php

namespace App\Imports;

use App\Models\GuardRoaster;
use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class GuardRoasterImport implements ToModel, WithHeadingRow
{
    protected $errors = [];
    protected $rowIndex = 0;

    /**
     * Handle the import of a single row.
     *
     * @param array $row
     * @return GuardRoaster|null
     */
    public function model(array $row)
    {
        $this->rowIndex++;

        $validator = Validator::make($row, [
            'guard_id'        => 'required',
            'client_id'       => 'required',
            'client_site_id'  => 'required',
            'date'            => 'required|date_format:d-m-Y|after_or_equal:' . Carbon::today()->format('d-m-Y'),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->errors[] = [
                        'message' => "Row ". $this->rowIndex .": " . $message,
                        'row'     => $row,
                    ];
                }
            }
            return null;
        }

        $formattedDate = Carbon::createFromFormat('d-m-Y', $row['date'])->format('Y-m-d');

        $client = Client::find($row['client_id']);
        if ($client && !$client->clientSites->contains('id', $row['client_site_id'])) {
            $this->errors[] = [
                'message' => "Row {$this->rowIndex} : Client ID '{$row['client_id']}' does not have Client Site ID '{$row['client_site_id']}'.",
                'row'     => $row,
            ];
            return null;
        }

        $guardRoaster = GuardRoaster::where('guard_id', $row['guard_id'])->where('date', $formattedDate)->first();

        if ($guardRoaster) {
            $this->errors[] = [
                'message' => "Row {$this->rowIndex} : A GuardRoaster record with Guard ID '{$row['guard_id']}' and Date '{$formattedDate}' already exists in Guard Roaster.",
                'row'     => $row,
            ];
            return null;
        }

        GuardRoaster::updateOrCreate(
            [
                'guard_id' =>  $row['guard_id'],
                'date'     =>  $formattedDate,
            ],
            [
                'client_id'      => $row['client_id'],
                'client_site_id' => $row['client_site_id'],
                'start_time'     => $row['start_time'] ?? null,
                'end_time'       => $row['end_time'] ?? null  
            ]
        );

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
