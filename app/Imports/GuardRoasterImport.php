<?php

namespace App\Imports;

use App\Models\GuardRoster;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\User;
use App\Models\Leave;
use App\Models\ClientSite;
use App\Models\RateMaster;
use Spatie\Permission\Models\Role;

class GuardRoasterImport implements ToModel, WithHeadingRow
{
    protected $errors = [];
    protected $importResults = [];
    protected $rowNumber = 1;

    /**
     * Find guard_id from user_code
     */
    private function findGuardIdFromUserCode($userCode)
    {
        $userCode = trim($userCode);
        $userRole = Role::where('id', 3)->first();

        $guard = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })
            ->where('status', 'Active')
            ->where('user_code', $userCode)
            ->first();

        return $guard ? $guard->id : null;
    }

    /**
     * Find client_site_id from location_code
     */
    private function findClientSiteIdFromLocationCode($locationCode)
    {
        $locationCode = trim($locationCode);
        $query = ClientSite::where('location_code', $locationCode)->where('status', 'Active');

        if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
            $userId = Auth::id();
            $query->where('manager_id', $userId);
        }

        $clientSite = $query->first();
        return $clientSite ? $clientSite->id : null;
    }

    /**
     * Handle the import of a single row.
     */
    public function model(array $row)
    {
        if ($this->rowNumber == 1) {
            if (empty($row['guard_id']) || empty($row['guard_type_id']) || empty($row['client_site_id'])) {
                return null;
            }
        }

        // Convert user_code to guard_id if needed
        $guardId = $row['guard_id'];
        if (!is_numeric($guardId)) {
            $guardId = $this->findGuardIdFromUserCode($guardId);
            if (!$guardId) {
                $userCode = trim($row['guard_id']);
                $user = User::where('user_code', $userCode)->first();

                if (!$user) {
                    $this->addImportResult('Guard with user code "' . $userCode . '" does not exist.');
                } elseif ($user->status !== 'Active') {
                    $this->addImportResult('Guard with user code "' . $userCode . '" exists but is not active (status: ' . $user->status . ').');
                } else {
                    $this->addImportResult('Guard with user code "' . $userCode . '" exists and is active but does not have Security Guard role assigned.');
                }
                return null;
            }
        }

        // Convert location_code to client_site_id if needed
        $clientSiteId = $row['client_site_id'];
        if (!is_numeric($clientSiteId)) {
            $clientSiteId = $this->findClientSiteIdFromLocationCode($clientSiteId);
            if (!$clientSiteId) {
                $locationCode = trim($row['client_site_id']);
                $clientSite = ClientSite::where('location_code', $locationCode)->first();

                if (!$clientSite) {
                    $this->addImportResult('Client site with location code "' . $locationCode . '" does not exist.');
                } else {
                    $this->addImportResult('Client site with location code "' . $locationCode . '" exists but is not active (status: ' . $clientSite->status . ').');
                }
                return null;
            }
        }

        foreach ($row as $column => $value) {
            if (empty($guardId)) {
                $this->addImportResult('Guard id is required.');
                return null;
            }

            if (empty($row['guard_type_id'])) {
                $this->addImportResult('Guard type id is required.');
                return null;
            }

            if (empty($clientSiteId)) {
                $this->addImportResult('Client Site id is required.');
                return null;
            }

            if (in_array($column, ['guard_id', 'guard_type_id', 'client_site_id'])) {
                continue;
            }

            $userRole = Role::where('id', 3)->first();
            $guard = User::whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })->where('status', 'Active')->find($guardId);

            if (!$guard) {
                $this->addImportResult('Guard ID ' . $guardId . ' does not exist.');
                return null;
            }

            $query = ClientSite::where('id', $clientSiteId)->where('status', 'Active');
            if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
                $userId = Auth::id();
                $query->where('manager_id', $userId);
            }
            $clientSite = $query->first();

            if (!$clientSite) {
                $this->addImportResult('Client site ID ' . $clientSiteId . ' does not exist.');
                return null;
            }

            $guardTypeId = RateMaster::where('id', $row['guard_type_id'])->first();
            if (!$guardTypeId) {
                $this->addImportResult('Guard Type ID ' . $row['guard_type_id'] . ' does not exist.');
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

                // Normalize
                $time_in = trim($time_in);
                $time_in = preg_replace('/:\s+/', ':', $time_in);
                $time_in = preg_replace('/([0-9])([AP]M)/i', '$1 $2', $time_in);

                $time_out = trim($time_out);
                $time_out = preg_replace('/:\s+/', ':', $time_out);
                $time_out = preg_replace('/([0-9])([AP]M)/i', '$1 $2', $time_out);

                if (!is_numeric($time_in)) {
                    $start_time = Carbon::createFromFormat('Y-m-d h:i A', $formattedDate . ' ' . $time_in);
                    $time_in = Carbon::createFromFormat('h:iA', $time_in)->format('H:i');
                }

                if (!is_numeric($time_out)) {
                    $end_time = Carbon::createFromFormat('Y-m-d h:i A', $formattedDate . ' ' . $time_out);
                    $time_out = Carbon::createFromFormat('h:iA', $time_out)->format('H:i');
                }

                $end_date = $end_time;
                if ($end_time->lessThan($start_time)) {
                    $end_date = $end_time->addDay();
                }

                $leave = Leave::where('guard_id', $guardId)->whereDate('date', $formattedDate)->where('status', 'Approved')->first();
                $existingAssignment = GuardRoster::where('guard_id', $guardId)->whereDate('date', $formattedDate)->where('is_publish', 1)->first();

                if ($existingAssignment) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Guard ' . $guardId . ' id is already assigned for this date (' . $formattedDate . ') and time (' . $time_in . ' to ' . $time_out . ')',
                    ];
                } else if ($leave) {
                    $this->importResults[] = [
                        'Row' => $this->rowNumber,
                        'Status' => 'Failed',
                        'Failure Reason' => 'Guard ' . $guardId . ' id is in leave for this date (' . $formattedDate . ')',
                    ];
                } else {
                    // ✅ New check: Prevent exact duplicates
                    $duplicateRoster = GuardRoster::where('guard_id', $guardId)
                        ->where('client_site_id', $clientSiteId)
                        ->where('guard_type_id', $row['guard_type_id'])
                        ->whereDate('date', $formattedDate)
                        ->where('start_time', $time_in)
                        ->where('end_time', $time_out)
                        ->first();

                    if ($duplicateRoster) {
                        $this->importResults[] = [
                            'Row' => $this->rowNumber,
                            'Status' => 'Failed',
                            'Failure Reason' => 'Duplicate roster already exists for Guard ' . $guardId . ' on ' . $formattedDate . ' (' . $time_in . ' to ' . $time_out . ')',
                        ];
                    } else {
                        // Overlap check
                        $existingRoster = GuardRoster::where('guard_id', $guardId)
                            ->where(function ($query) use ($time_in, $time_out, $formattedDate, $end_date) {
                                if ($formattedDate == $end_date) {
                                    echo "<pre>";
                                    print_r('sdsd');
                                    $query->where('date', '=', $formattedDate)
                                        ->where(function ($query) use ($time_in, $time_out) {
                                            $query->where(function ($query) use ($time_in, $time_out) {
                                                $query->where('start_time', '<', $time_out)
                                                    ->where('end_time', '>', $time_in);
                                            });
                                        });
                                } else {
                                    $query->where(function ($query) use ($time_in, $time_out, $formattedDate, $end_date) {
                                        $query->where('date', '=', $formattedDate)
                                            ->whereDate('end_date', '=', $end_date)
                                            ->where(function ($query) use ($time_in, $time_out) {
                                                $query->where('start_time', '>', $time_out)
                                                    ->where('end_time', '<', $time_in);
                                            });
                                    });
                                }
                            })
                            ->first();

                        // $existingRoster = GuardRoster::where('guard_id', $row['guard_id'])
                        //                         ->where('date', $formattedDate)
                        //                         ->where(function($query) use ($time_in, $time_out) {
                        //                             $query->whereBetween('start_time', [$time_in, $time_out])
                        //                                 ->orWhereBetween('end_time', [$time_in, $time_out])
                        //                                 ->orWhere(function($query) use ($time_in, $time_out) {
                        //                                     $query->where('start_time', '<=', $time_in)
                        //                                         ->where('end_time', '>=', $time_out);
                        //                                 });
                        //                         })
                        //                         ->first();
                        if ($existingRoster) {
                            //     $existingRoster->update([
                            //         'guard_type_id'  => $row['guard_type_id'],
                            //         'client_id'      => $clientSite->client_id ?? Null,
                            //         'client_site_id' => $row['client_site_id'],
                            //         'start_time'     => $time_in ?? '',
                            //         'end_time'       => $time_out ?? '',
                            //         'end_date'       => $end_date,
                            //     ]);
                            $this->importResults[] = [
                                'Row' => $this->rowNumber,
                                'Status' => 'Failed',
                                'Failure Reason' => 'There is already an overlapping guard roster for this client site at this time.',
                            ];
                        } else {
                            GuardRoster::create([
                                'guard_id' => $guardId,
                                'guard_type_id' => $row['guard_type_id'],
                                'client_id' => $clientSite->client_id ?? null,
                                'client_site_id' => $clientSiteId,
                                'date' => $formattedDate,
                                'start_time' => $time_in ?? '',
                                'end_time' => $time_out ?? '',
                                'end_date' => $end_date
                            ]);

                            $this->importResults[] = [
                                'Row' => $this->rowNumber,
                                'Status' => 'Success',
                                'Failure Reason' => 'Created sucessfully for date' . $formattedDate,
                            ];
                        }
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
