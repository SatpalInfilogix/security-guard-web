<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuardRoster;
use App\Models\FortnightDates;
use App\Models\User;
use App\Models\Leave;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\PublicHoliday;
use App\Models\RateMaster;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use App\Imports\GuardRoasterImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GuardRoasterExport;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Gate;

class GuardRosterController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view guard roster')) {
            abort(403);
        }

        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        if (!$fortnight) {
            $fortnight = null;
        }

        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        $clients = Client::latest()->get();
        $query = ClientSite::where('status', 'Active')->latest();
        if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
            $userId = Auth::id();
            $query->where('manager_id', $userId);
        }

        $clientSites = $query->get();

        // return view('admin.guard-roster.index', compact('fortnight', 'securityGuards', 'clients', 'clientSites'));
        return view('admin.guard-roster.index', [
            'fortnight'      => $fortnight,
            'securityGuards' => $securityGuards,
            'clients'        => $clients,
            'clientSites'    => $clientSites,
            'selectedDate'   => null,   // or Carbon::today()->toDateString() if you want default
            'selectedField'  => null,   // or any default value
        ]);
    }

    public function getGuardRosterList(Request $request)
    {
        $guardRoasterData = GuardRoster::with('user', 'client', 'clientSite', 'guardType');

        $userId = Auth::id();
        if (Auth::user()->hasRole('Manager Operations')) {
            $guardRoasterData->whereHas('clientSite', function ($query) use ($userId) {
                $query->where('manager_id', $userId);
            });
        }

        if ($request->has('guard_id') && !empty($request->guard_id)) {
            $guardRoasterData->where('guard_id', $request->guard_id);
        }

        if ($request->has('client_id') && !empty($request->client_id)) {
            $guardRoasterData->where('client_id', $request->client_id);
        }

        if ($request->has('client_site_id') && !empty($request->client_site_id)) {
            $guardRoasterData->where('client_site_id', $request->client_site_id);
        }

        if ($request->has('date') && !empty($request->date)) {
            // Check if the date parameter contains " to " for range
            if (strpos($request->date, ' to ') !== false) {
                $dates = explode(' to ', $request->date);

                if (count($dates) === 2) {
                    $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
                    $endDate = Carbon::parse(trim($dates[1]))->endOfDay();

                    $guardRoasterData->whereBetween('date', [$startDate, $endDate]);
                }
            } else {
                $guardRoasterData->where('date', Carbon::parse($request->date));
            }
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];

            $guardRoasterData->where(function ($query) use ($searchValue) {
                $query->whereHas('user', function ($q) use ($searchValue) {
                    $q->where('first_name', 'like', '%' . $searchValue . '%')
                        ->orWhere('surname', 'like', '%' . $searchValue . '%');
                })
                    ->orWhereHas('client', function ($q) use ($searchValue) {
                        $q->where('client_name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhere('date', 'like', '%' . $searchValue . '%');
            });
        }

        $totalRecords = GuardRoster::count();
        $filteredRecords = $guardRoasterData->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $guardRoasters = $guardRoasterData->orderBy('id', 'desc')->skip($start)->take($length)->get();

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $guardRoasters->map(function ($guardRoster) {
                return [
                    'id' => $guardRoster->id,
                    'user' => $guardRoster->user,
                    'client' => $guardRoster->client,
                    'clientSite' => $guardRoster->clientSite,
                    'guardType' => $guardRoster->guardType ? $guardRoster->guardType->guard_type : 'N/A',
                    'date' => $guardRoster->date,
                    'start_time' => $guardRoster->start_time,
                    'end_time' => $guardRoster->end_time,
                ];
            }),
        ];

        return response()->json($data);
    }

    public function create()
    {
        if (!Gate::allows('create guard roster')) {
            abort(403);
        }

        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        $clients = Client::latest()->get();
        $guardTypes = RateMaster::latest()->get();

        return view('admin.guard-roster.create', compact('securityGuards', 'clients', 'guardTypes'));
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create guard roster')) {
            abort(403);
        }

        $request->validate([
            'guard_id'       => 'required',
            'client_id'      => 'required',
            'client_site_id' => 'required',
            'guard_type_id'  => 'required',
            'date'           => 'required|date',
            'start_time'     => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
            'end_time'       => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
        ]);

        $startTime = trim($request->start_time);
        $endTime = trim($request->end_time);

        $start_time = Carbon::createFromFormat('h:iA', $startTime)->format('H:i');
        $end_time = Carbon::createFromFormat('h:iA', $endTime)->format('H:i');

        $start_date = Carbon::parse($request->date);
        $end_date = Carbon::parse($request->end_date);

        $existingRoster = GuardRoster::where('guard_id', $request->guard_id)
            ->where(function ($query) use ($start_time, $end_time, $start_date, $end_date) {
                if ($start_date == $end_date) {
                    $query->where('date', '=', $start_date)
                        ->where(function ($query) use ($start_time, $end_time) {
                            $query->where(function ($query) use ($start_time, $end_time) {
                                $query->where('start_time', '<', $end_time)
                                    ->where('end_time', '>', $start_time);
                            });
                        });
                } else {
                    $query->where(function ($query) use ($start_time, $end_time, $start_date, $end_date) {
                        $query->where('date', '=', $start_date)
                            ->where('end_date', '=', $end_date)
                            ->where(function ($query) use ($start_time, $end_time) {
                                $query->where('start_time', '>', $end_time)
                                    ->where('end_time', '<', $start_time);
                            });
                    });
                }
            })
            ->first();

        if ($existingRoster) {
            return back()->with('error', 'There is already an overlapping guard roster for this client site at this time.');
        }

        GuardRoster::create([
            'guard_id'       => $request->guard_id,
            'date'           => $request->date,
            'client_id'      => $request->client_id,
            'client_site_id' => $request->client_site_id,
            'guard_type_id'  => $request->guard_type_id,
            'start_time'     => $start_time,
            'end_time'       => $end_time,
            'end_date'       => $request->end_date,
        ]);

        logActivity('Create Guard Roster', 'Created Guard Roster ID: ' . $request->guard_id);


        return redirect()->route('guard-rosters.index')->with('success', 'Guard Roster created successfully.');
    }

    public function show($id)
    {
        //
    }

    // public function edit($id)
    // {
    //     if (!Gate::allows('edit guard roster')) {
    //         abort(403);
    //     }
    //     $guardRoaster = GuardRoster::where('id', $id)->first();
    //     $userRole = Role::where('id', 3)->first();
    //     $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
    //         $query->where('role_id', $userRole->id);
    //     })->where('status', 'Active')->latest()->get();

    //     $clients = Client::latest()->get();
    //     $query = ClientSite::where('status', 'Active')->latest();
    //     if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
    //         $userId = Auth::id();
    //         $query->where('manager_id', $userId);
    //     }

    //     $clientSites = $query->get();

    //     $start_time = Carbon::createFromFormat('H:i:s', $guardRoaster->start_time)->format('h:iA');
    //     $end_time = Carbon::createFromFormat('H:i:s', $guardRoaster->end_time)->format('h:iA');

    //     $guardRoaster['start_time'] = $start_time;
    //     $guardRoaster['end_time']   = $end_time;
    //     $guardTypes = RateMaster::latest()->get();

    //     return view('admin.guard-roster.edit', compact('securityGuards', 'clients', 'guardRoaster', 'clientSites', 'guardTypes'));
    // }

    public function edit($id, Request $request)
    {
        if (!Gate::allows('edit guard roster')) {
            abort(403);
        }

        $guardRoaster = GuardRoster::findOrFail($id);
        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        $clients = Client::latest()->get();
        $query = ClientSite::where('status', 'Active')->latest();

        if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
            $query->where('manager_id', Auth::id());
        }

        $clientSites = $query->get();

        $guardRoaster->start_time = $guardRoaster->start_time
            ? Carbon::createFromFormat('H:i:s', $guardRoaster->start_time)->format('h:i A')
            : null;

        $guardRoaster->end_time = $guardRoaster->end_time
            ? Carbon::createFromFormat('H:i:s', $guardRoaster->end_time)->format('h:i A')
            : null;

        $guardTypes = RateMaster::latest()->get();

        $selectedDate = $request->query('date');
        $selectedField = $request->query('field');

        return view('admin.guard-roster.edit', compact(
            'securityGuards',
            'clients',
            'guardRoaster',
            'clientSites',
            'guardTypes',
            'selectedDate',
            'selectedField'
        ));
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit guard roster')) {
            abort(403);
        }
        $request->validate([
            'guard_id'    => 'required',
            'client_id'    => 'required',
            'client_site_id' => 'required',
            'guard_type_id'  => 'required',
            'start_time'     => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
            'end_time'       => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
        ]);

        $guardRoaster = GuardRoster::where('id', $id)->first();
        $oldValues = $guardRoaster->toArray();

        $existingGuardRoaster = GuardRoster::where('guard_id', $request->guard_id)->where('date', $request->date)
            ->where('id', '!=', $guardRoaster->id)->first();

        if ($existingGuardRoaster) {
            return redirect()->back()->withErrors(['date' => 'Date already assigned to this guard.'])->withInput();
        }

        $start_time = trim($request->start_time);
        $end_time = trim($request->end_time);

        $start_time = Carbon::createFromFormat('h:iA', $start_time)->format('H:i');
        $end_time = Carbon::createFromFormat('h:iA', $end_time)->format('H:i');

        $guardRoaster->update([
            'guard_id'       => $request->guard_id,
            'client_id'      => $request->client_id,
            'client_site_id' => $request->client_site_id,
            'guard_type_id'  => $request->guard_type_id,
            'date'           => $request->date,
            'start_time'     => $start_time,
            'end_time'       => $end_time,
            'end_date'       => $request->end_date
        ]);
        $newValues = $guardRoaster->toArray();

        logActivity(
            'Update Guard Roster',
            'Updated Guard Roster ID: ' . $guardRoaster->id .
                ' | Changes: ' . json_encode(array_diff_assoc($newValues, $oldValues))
        );

        return redirect()->route('guard-rosters.index')->with('success', 'Guard Roster updated successfully.');
    }

    public function getClientSites($clientId)
    {
        $query = ClientSite::where('client_id', $clientId)->where('status', 'Active');

        if (Auth::check() && Auth::user()->hasRole('Manager Operations')) {
            $userId = Auth::id();
            $query->where('manager_id', $userId);
        }

        $clientSites = $query->get();

        return response()->json($clientSites);
    }

    public function getAssignedDate($guardId)
    {
        $assignedDates = GuardRoster::where('guard_id', $guardId)->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        return response()->json($assignedDates);
    }

    public function getPublicHolidays()
    {
        $publicHolidays = PublicHoliday::latest()->get();
        return response()->json($publicHolidays);
    }

    public function destroy($id)
    {
        if (!Gate::allows('delete guard roster')) {
            abort(403);
        }
        GuardRoster::where('id', $id)->delete();
        logActivity('Delete Guard Roster', 'Deleted Guard Roster ID: ' . $id);

        return response()->json([
            'success' => true,
            'message' => 'Guard Roster deleted successfully.'
        ]);
    }

    public function getGuardRosterDetails(Request $request)
    {
        $guardId = $request->input('guard_id');
        $date = $request->input('date');

        if (!$guardId || !$date) {
            return response()->json(['error' => 'Date and Guard ID are required'], 400);
        }

        $guardRoaster = GuardRoster::where('guard_id', $guardId)
            ->where('date', $date)
            ->first();

        if (!$guardRoaster) {
            return response()->json(['error' => 'No roster found for this guard and date'], 404);
        }

        $client = $guardRoaster->client_id;
        $clientSites = ClientSite::where('client_id', $client)->get();

        return response()->json([
            'client_id' => $client,
            'client_site_id' => $guardRoaster->client_site_id,
            'client_sites' => $clientSites, // Pass all available client sites for the selected client
            'start_time' => $guardRoaster->start_time,
            'end_time' => $guardRoaster->end_time,
        ]);
    }

    public function importGuardRoster(Request $request)
    {
        $import = new GuardRoasterImport;
        $fileName = $request->file('file')->getClientOriginalName();
        Excel::import($import, $request->file('file'));
        logActivity('File Import', "Imported Guard Roster file: {$fileName}");

        session(['importData' => $import]);
        session()->flash('success', 'Guard roster imported successfully.');
        $downloadUrl = route('guard-rosters.download');

        return redirect()->route('guard-rosters.index')->with('downloadUrl', $downloadUrl);
    }

    public function download()
    {
        $import = session('importData');
        $export = new GuardRoasterExport($import);
        logActivity('File Export', 'Exported Guard Roster Imported Result CSV');
        return Excel::download($export, 'guard_import_results.csv');
    }

    public function downloadExcel()
    {
        $spreadsheet = new Spreadsheet();

        $this->addGuardsSheet($spreadsheet);
        $this->addClientsSheet($spreadsheet);
        $this->addClientSitesSheet($spreadsheet);
        $this->addGuardTypesSheet($spreadsheet);
        logActivity('File Export', 'Exported Guard Roster CSV');
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Guard_Roster_configuration.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addGuardTypesSheet($spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Guard-Types');

        $headers = [
            'ID',
            'Guard Type',
            'Regular Rate',
            'Status',
            'Created At',
            'Updated At'
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        $guardTypes = RateMaster::get();

        foreach ($guardTypes as $key => $guardType) {
            $data = [
                $guardType->id,
                $guardType->guard_type ?? '',
                $guardType->regular_rate ?? '',
                $guardType->status ?? 'Active',
                $guardType->created_at ? $guardType->created_at->format('Y-m-d H:i:s') : '',
                $guardType->updated_at ? $guardType->updated_at->format('Y-m-d H:i:s') : ''
            ];

            $sheet->fromArray($data, NULL, 'A' . ($key + 2));
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    protected function addGuardsSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guards');

        $headers = ['ID', 'Guard Code', 'First Name', 'Last Name', 'Email', 'Phone Number', 'TRN', 'NIS', 'PSRA', 'Date Of Joining', 'Date Of Birth', 'Employer Company Name', 'Guard Current Rate', 'Location Code', 'Location Name', 'Client Code', 'Client Name', 'Guard Type Id', 'Employed As', 'Date of Seperation', 'Bank Name', 'Bank Branch Address', 'Account no', 'Account Type', 'Routing Number', 'Surname', 'First Name', 'Middle Name', 'Appartment No', 'Building Name', 'Street Name', 'Parish', 'City', 'Postal Code', 'Email', 'Phone Number', 'Trn Doc', 'NIS Doc', 'PSRA Doc', 'Birth Certificate Doc', 'Appratment No', 'Building Name', 'Street Name', 'Parish', 'City', 'Postal Code'];
        $sheet->fromArray($headers, NULL, 'A1');

        $users = User::whereHas('roles', function ($query) {
            $query->where('id', 3);
        })->with('guardAdditionalInformation', 'usersBankDetail', 'usersKinDetail', 'userDocuments', 'contactDetail')->get();

        foreach ($users as $key => $user) {
            $sheet->fromArray(
                [$user->id,$user->user_code, $user->first_name, $user->surname, $user->email, $user->phone_number, $user->guardAdditionalInformation->trn, $user->guardAdditionalInformation->nis, $user->guardAdditionalInformation->psra, $user->guardAdditionalInformation->date_of_joining, $user->guardAdditionalInformation->date_of_birth, $user->guardAdditionalInformation->employer_company_name, $user->guardAdditionalInformation->guards_Current_rate, $user->guardAdditionalInformation->location_code, $user->guardAdditionalInformation->location_name, $user->guardAdditionalInformation->client_code, $user->guardAdditionalInformation->client_name, $user->guardAdditionalInformation->guard_type_id, $user->guardAdditionalInformation->employed_as, $user->guardAdditionalInformation->date_of_seperation, $user->usersBankDetail->bank_name, $user->usersBankDetail->bank_branch_address, $user->usersBankDetail->account_no, $user->usersBankDetail->account_type, $user->usersBankDetail->routing_number, $user->usersKinDetail->surname, $user->usersKinDetail->first_name, $user->usersKinDetail->middle_name, $user->usersKinDetail->apartment_no, $user->usersKinDetail->building_name, $user->usersKinDetail->street_name, $user->usersKinDetail->parish, $user->usersKinDetail->city, $user->usersKinDetail->postal_code, $user->usersKinDetail->email, $user->usersKinDetail->phone_number,  $user->userDocuments->trn ? url($user->userDocuments->trn) : '', $user->userDocuments->nis ? url($user->userDocuments->nis) : '', $user->userDocuments->psra ? url($user->userDocuments->psra) : '', $user->userDocuments->birth_certificate ? url($user->userDocuments->birth_certificate) : '', $user->contactDetail->apartment_no, $user->contactDetail->building_name, $user->contactDetail->street_name, $user->contactDetail->parish, $user->contactDetail->city, $user->contactDetail->postal_code],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    protected function addClientsSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheet(1);  // Get the second sheet (index 1)
        $sheet->setTitle('Clients');

        $headers = ['ID', 'Client Code', 'Client Name'];
        $sheet->fromArray($headers, NULL, 'A1');

        $clients = Client::all();

        foreach ($clients as $key => $client) {
            $sheet->fromArray(
                [$client->id, $client->client_code, $client->client_name],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();  // Move to next sheet
    }

    protected function addClientSitesSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheet(2);  // Get the third sheet (index 2)
        $sheet->setTitle('Client-Sites');

        $headers = [
            'ID',
            'Client Id',
            'Client Name',
            'Client Location',
            'Location Name',
            'Parish',
            'Billing Address',
            'Vanguard Manager',
            'Contact Operation',
            'Telephone Number',
            'Email',
            'Invoice Recipient Main',
            'Invoice Recipient Copy',
            'Account Payable Contact Name',
            'Account Payable Contact Email',
            'Telephone Number',
            'Latitude',
            'Longitude',
            'Radius',
            'Status'
        ];
        $sheet->fromArray($headers, NULL, 'A1');

        $clientSites = ClientSite::with('client')->get();

        foreach ($clientSites as $key => $clientSite) {
            $data = [
                $clientSite->id,
                $clientSite->client_id,
                $clientSite->client->client_name ?? '',
                $clientSite->location_code ?? '',
                $clientSite->location ?? '',
                $clientSite->parish ?? '',
                $clientSite->billing_address ?? '',
                $clientSite->vanguard_manager ?? '',
                $clientSite->contact_operation ?? '',
                $clientSite->telephone_number ?? '',
                $clientSite->email ?? '',
                $clientSite->invoice_recipient_main ?? '',
                $clientSite->invoice_recipient_copy ?? '',
                $clientSite->account_payable_contact_name ?? '',
                $clientSite->account_payable_contact_email ?? '',
                $clientSite->telephone_number ?? '',
                $clientSite->latitude ?? '',
                $clientSite->longitude ?? '',
                $clientSite->radius ?? '',
                $clientSite->status ?? ''
            ];

            $sheet->fromArray($data, NULL, 'A' . ($key + 2));
        }
    }

    public function getLeaves($guardId)
    {
        $leaves = Leave::where('guard_id', $guardId)->whereIn('status', ['Approved', 'Pending'])->latest()->get();

        return response()->json($leaves);
    }

    // public function getGuardRosters(Request $request)
    // {
    //     $today = Carbon::now();
    //     $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

    //     if (!$fortnight) {
    //         return response()->json([
    //             'data' => [],
    //             'recordsTotal' => 0,
    //             'recordsFiltered' => 0
    //         ]);
    //     }

    //     $query = GuardRoster::with('user', 'client', 'clientSite', 'guardType')
    //         ->whereBetween('date', [$fortnight->start_date, $fortnight->end_date]);

    //     $userId = Auth::id();
    //     if (Auth::user()->hasRole('Manager Operations')) {
    //         $query->whereHas('clientSite', function ($query) use ($userId) {
    //             $query->where('manager_id', $userId);
    //         });
    //     }

    //     if ($request->has('search') && !empty($request->search['value'])) {
    //         $searchValue = $request->search['value'];
    //         $query->where(function ($query) use ($searchValue) {
    //             $query->whereHas('user', function ($query) use ($searchValue) {
    //                 $query->where('first_name', 'like', '%' . $searchValue . '%');
    //             })
    //                 ->orWhereHas('client', function ($query) use ($searchValue) {
    //                     $query->where('client_name', 'like', '%' . $searchValue . '%');
    //                 })
    //                 ->orWhereHas('clientSite', function ($query) use ($searchValue) {
    //                     $query->where('location_Code', 'like', '%' . $searchValue . '%');
    //                 });
    //         });
    //     }

    //     $guardRoasters = $query->get();
    //     $formattedGuardRoasters = [];

    //     foreach ($guardRoasters as $item) {
    //         $date = $item->date;
    //         $guard_id = $item->user->id;
    //         $client_site_id = $item->client_site_id;

    //         $guardTypes = $item->guardType->guard_type ?? '';
    //         $formattedGuardRoasters[$guard_id][$client_site_id][$date][] = [
    //             'guard_name' => $item->user->first_name . ' ' . optional($item->user)->surname,
    //             'client_name' => $item->client->client_name,
    //             'location_code' => $item->clientSite->location_code,
    //             'time_in' => \Carbon\Carbon::parse($item->start_time)->format('h:iA'),
    //             'time_out' => \Carbon\Carbon::parse($item->end_time)->format('h:iA'),
    //             'guard_types' => $guardTypes,
    //         ];
    //     }

    //     $flattenedData = [];
    //     foreach ($formattedGuardRoasters as $guardId => $clientSites) {
    //         foreach ($clientSites as $clientSiteId => $dates) {
    //             $row = [
    //                 'guard_name' => $dates[array_key_first($dates)][0]['guard_name'],
    //                 'client_name' => $dates[array_key_first($dates)][0]['client_name'],
    //                 'location_code' => $dates[array_key_first($dates)][0]['location_code'],
    //                 'guardType' => implode(', ', array_column($dates[array_key_first($dates)], 'guard_types')),
    //             ];

    //             foreach ($dates as $date => $timeEntries) {
    //                 $row[$date . '_time_in'] = implode(', ', array_column($timeEntries, 'time_in'));
    //                 $row[$date . '_time_out'] = implode(', ', array_column($timeEntries, 'time_out'));
    //             }

    //             $flattenedData[] = $row;
    //         }
    //     }

    //     return response()->json([
    //         'draw' => intval($request->input('draw')),
    //         'recordsTotal' => count($flattenedData),
    //         'recordsFiltered' => count($flattenedData),
    //         'data' => $flattenedData
    //     ]);
    // }

    public function getGuardRosters(Request $request)
    {
        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if (!$fortnight) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        }

        $query = GuardRoster::with('user', 'client', 'clientSite', 'guardType')
            ->whereBetween('date', [$fortnight->start_date, $fortnight->end_date]);

        $userId = Auth::id();
        if (Auth::user()->hasRole('Manager Operations')) {
            $query->whereHas('clientSite', function ($query) use ($userId) {
                $query->where('manager_id', $userId);
            });
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($query) use ($searchValue) {
                $query->whereHas('user', function ($query) use ($searchValue) {
                    $query->where('first_name', 'like', '%' . $searchValue . '%');
                })
                    ->orWhereHas('client', function ($query) use ($searchValue) {
                        $query->where('client_name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhereHas('clientSite', function ($query) use ($searchValue) {
                        $query->where('location_code', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $guardRoasters = $query->get();

        $formattedGuardRoasters = [];
        foreach ($guardRoasters as $item) {
            $date = $item->date;
            $guard_id = $item->user->id;
            $client_site_id = $item->client_site_id;

            $guardTypes = $item->guardType->guard_type ?? '';
            $formattedGuardRoasters[$guard_id][$client_site_id][$date][] = [
                'id'          => $item->id,
                'guard_name' => $item->user->first_name . ' ' . optional($item->user)->surname,
                'client_name' => $item->client->client_name,
                'location_code' => $item->clientSite->location_code,
                'time_in' => \Carbon\Carbon::parse($item->start_time)->format('h:iA'),
                'time_out' => \Carbon\Carbon::parse($item->end_time)->format('h:iA'),
                'guard_types' => $guardTypes,
            ];
        }

        $flattenedData = [];
        foreach ($formattedGuardRoasters as $guardId => $clientSites) {
            foreach ($clientSites as $clientSiteId => $dates) {
                $row = [
                    'guard_name' => $dates[array_key_first($dates)][0]['guard_name'],
                    'client_name' => $dates[array_key_first($dates)][0]['client_name'],
                    'location_code' => $dates[array_key_first($dates)][0]['location_code'],
                    'guardType' => implode(', ', array_column($dates[array_key_first($dates)], 'guard_types')),
                ];

                // foreach ($dates as $date => $timeEntries) {
                //     $row[$date . '_time_in'] = implode(', ', array_column($timeEntries, 'time_in'));
                //     $row[$date . '_time_out'] = implode(', ', array_column($timeEntries, 'time_out'));
                // }

                foreach ($dates as $date => $timeEntries) {
                    $firstEntry = $timeEntries[0];

                    $row[$date . '_time_in']  = [
                        'id'   => $firstEntry['id'],
                        'time' => implode(', ', array_column($timeEntries, 'time_in')),
                    ];
                    $row[$date . '_time_out'] = [
                        'id'   => $firstEntry['id'],
                        'time' => implode(', ', array_column($timeEntries, 'time_out')),
                    ];
                }

                $flattenedData[] = $row;
            }
        }

        $totalRecords = count($flattenedData);
        $start  = $request->input('start', 0);
        $length = $request->input('length', 50);

        $paginatedData = array_slice($flattenedData, $start, $length);

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $paginatedData
        ]);
    }

    public function getGuardTypeByGuardId($guardId)
    {
        $guardInfo = User::with('guardAdditionalInformation')->where('id', $guardId)->first();

        if ($guardInfo && $guardInfo->guardAdditionalInformation) {
            return response()->json([
                'guard_type_id' => $guardInfo->guardAdditionalInformation->guard_type_id ?? null,
            ]);
        }

        return response()->json(['guard_type_id' => null]);
    }
}
