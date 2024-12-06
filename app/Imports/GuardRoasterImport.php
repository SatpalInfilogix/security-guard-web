<?php

namespace App\Imports;

use App\Models\GuardRoster;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\User;
use App\Models\Leave;
use App\Models\ClientSite;
use Spatie\Permission\Models\Role;

class GuardRoasterImport implements ToModel, WithHeadingRow
{
    protected $errors = [];
    protected $importResults = [];
    protected $rowNumber = 1;

    /**
     * Handle the import of a single row.
     *
     * @param array $row
     * @return GuardRoaster|null
     */
    public function model(array $row)
    {
        if ($this->rowNumber == 1) {
            if (empty($row['guard_id']) || empty($row['client_site_id'])) {
                return null;
            }
        }

        foreach ($row as $column => $value) {
            if (empty($row['guard_id'])) {
                $this->addImportResult('Guard id is required.');
                return null;
            }
            
            if (empty($row['client_site_id'])) {
                $this->addImportResult('Client Site id is required.');
                return null;
            }

            if (in_array($column, ['guard_id', 'client_site_id'])) {
                continue;
            }

            $userRole = Role::where('name', 'Security Guard')->first();

            $guard = User::whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })->where('status', 'Active')->find($row['guard_id']);

            if (!$guard) {
                $this->addImportResult('Guard ID ' . $row['guard_id'] . ' does not exist.');
                return null;
            }

            $query = ClientSite::where('id', $row['client_site_id'])->where('status', 'Active');
            if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
                $userId = Auth::id();
                $query->where('manager_id', $userId);
            }
            
            $clientSite = $query->first();

            if (!$clientSite) {
                $this->addImportResult('Client site ID ' . $row['client_site_id'] . ' does not exist.');
                return null;
            }

            if (preg_match('/^[a-z]{3}_\d{1,2}_[a-z]{3}_\d{4}$/', $column)) {
                $dateStr = preg_replace('/^[a-z]{3}_/', '', $column);
                $dateStr = str_replace('_', '-', $dateStr);

                try {
                    $formattedDate = Carbon::createFromFormat('d-M-Y', $dateStr)->format('Y-m-d');
                } catch (\Exception $e) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Invalid date format for ' . $column,
                    ];
                    continue;
                }

                if (Carbon::parse($formattedDate)->isBefore(Carbon::today())) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Date ' . $formattedDate . ' cannot be in the past.',
                    ];
                    continue;
                }

                $time_in = $row[$column];
                $time_out = $row[array_keys($row)[array_search($column, array_keys($row)) + 1]] ?? null;

                if (empty($time_in) || empty($time_out)) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Time in and/or Time out are missing for ' . $column,
                    ];
                    continue;
                }

                if (!is_numeric($time_in)) {
                    $start_time = Carbon::createFromFormat('Y-m-d h:i A', $formattedDate . ' ' . $time_in);
                    $time_in = Carbon::createFromFormat('h:iA', $time_in)->format('H:i');
                }

                if (!is_numeric($time_out)) {
                    $end_time =  Carbon::createFromFormat('Y-m-d h:i A', $formattedDate . ' ' . $time_out);
                    $time_out = Carbon::createFromFormat('h:iA',$time_out)->format('H:i');
                }
                
                $end_date = $end_time;
                if ($end_time->lessThan($start_time)) {
                    $end_date = $end_time->addDay();
                }

                $leave = Leave::where('guard_id', $row['guard_id'])->whereDate('date', $formattedDate)->where('status', 'Approved')->first();
                $existingAssignment = GuardRoster::where('guard_id', $row['guard_id'])->whereDate('date', $formattedDate)->where('is_publish', 1)->first();

                if ($existingAssignment) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Guard ' . $row['guard_id'] . ' id is already assigned for this date (' . $formattedDate . ') and time (' . $time_in . ' to ' . $time_out . ')',
                    ];
                } else if($leave) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Guard ' . $row['guard_id'] . ' id is in leave for this date (' . $formattedDate . ')',
                    ];
                } else {
                    $existingRoster = GuardRoster::where('guard_id', $row['guard_id'])->whereDate('date', $formattedDate)->where('is_publish', 0)->first();
                    if ($existingRoster) {
                        $existingRoster->update([
                            'client_id'      => $clientSite->client_id ?? Null,
                            'client_site_id' => $row['client_site_id'],
                            'start_time'     => $time_in ?? '',
                            'end_time'       => $time_out ?? '',
                            'end_date'       => $end_date,
                        ]);
                
                        $this->importResults[] = [
                            'Row' => $this->rowNumber,
                            'Status' => 'Success',
                            'Failure Reason' => 'Updated successfully for date ' . $formattedDate,
                        ];
                    } else {
                        GuardRoster::create([
                            'guard_id'       => $row['guard_id'],
                            'client_id'      => $clientSite->client_id ?? Null,
                            'client_site_id' => $row['client_site_id'],
                            'date'           => $formattedDate,
                            'start_time'     => $time_in ?? '',
                            'end_time'       => $time_out ?? '',
                            'end_date'       => $end_date
                        ]);

                        $this->importResults[] = [
                            'Row' => $this->rowNumber,
                            'Status' => 'Success',
                            'Failure Reason' => 'Created sucessfully for date'. $formattedDate,
                        ];
                    }
                }
            }
        }

        $this->rowNumber++;
        return null;
    }

    public function getImportResults()
    {
        return collect($this->importResults);
    }

    private function addImportResult($reason)
    {
        $this->importResults[] = [
            'Row' => $this->rowNumber,
            'Status' => 'Failed',
            'Failure Reason' => $reason,
        ];
        $this->rowNumber++;
    }
}
