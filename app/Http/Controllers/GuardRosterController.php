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
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use App\Imports\GuardRoasterImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GuardRoasterExport;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Gate;

class GuardRosterController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view guard roaster')) {
            abort(403);
        }

        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first(); 
        if (!$fortnight) {
            $fortnight = null;
        }

        return view('admin.guard-roster.index', compact('fortnight'));
    }

    public function getGuardRoasterList(Request $request)
    {
        $guardRoasterData = GuardRoster::with('user', 'client');
        
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
    
            $guardRoasterData->where(function($query) use ($searchValue) {
                $query->whereHas('user', function($q) use ($searchValue) {
                    $q->where('first_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('surname', 'like', '%' . $searchValue . '%');
                })
                ->orWhereHas('client', function($q) use ($searchValue) {
                    $q->where('client_name', 'like', '%' . $searchValue . '%');
                })
                ->orWhere('date', 'like', '%' . $searchValue . '%');
            });
        }
    
        $totalRecords = GuardRoster::count();
        
        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
    
        $guardRoasters = $guardRoasterData->orderBy('id', 'desc')
                                          ->skip($start)  // Start offset
                                          ->take($length) // Limit records
                                          ->get();  // Get the records as an array (not paginated yet)
        
        // Prepare the response
        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords, // Total records without filtering
            'recordsFiltered' => $guardRoasterData->count(), // Filtered records count
            'data' => $guardRoasters, // The actual data (items on the current page)
        ];
    
        // Return the response as JSON
        return response()->json($data);
    }

    public function create()
    {
        if(!Gate::allows('create guard roaster')) {
            abort(403);
        }

        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        $clients = Client::latest()->get();

        return view('admin.guard-roster.create', compact('securityGuards', 'clients'));
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create guard roaster')) {
            abort(403);
        }

        $request->validate([
            'guard_id'       => 'required',
            'client_id'      => 'required',
            'client_site_id' => 'required',
            'date'           => 'required|date',
            'start_time'     => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
            'end_time'       => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
        ]);
        $start_time = trim($request->start_time);
        $end_time = trim($request->end_time);
        
        $start_time = Carbon::createFromFormat('h:iA', $start_time)->format('H:i');
        $end_time = Carbon::createFromFormat('h:iA', $end_time)->format('H:i');
        
        $guardRoaster = GuardRoster::updateOrCreate(
            [
                'guard_id' => $request->guard_id,
                'date'     => $request->date,  // We use these two attributes to search for the existing record
            ],
            [
                'client_id'      => $request->client_id,
                'client_site_id' => $request->client_site_id,
                'start_time'     => $start_time,
                'end_time'       => $end_time,
                'end_date'       => $request->end_date
            ]
        );

        return redirect()->route('guard-rosters.index')->with('success', 'Guard Roster created successfully.');
    }

    public function show($id) {
        //
    }

    public function edit($id) 
    {
        if(!Gate::allows('edit guard roaster')) {
            abort(403);
        }
        $guardRoaster = GuardRoster::where('id', $id)->first();
        $userRole = Role::where('name', 'Security Guard')->first();
        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        $clients = Client::latest()->get();
        $clientSites = ClientSite::where('status', 'Active')->latest()->get();
        
        $start_time = Carbon::createFromFormat('H:i:s', $guardRoaster->start_time)->format('h:iA');
        $end_time = Carbon::createFromFormat('H:i:s', $guardRoaster->end_time)->format('h:iA');

        $guardRoaster['start_time'] = $start_time;
        $guardRoaster['end_time']   = $end_time;

        return view('admin.guard-roster.edit', compact('securityGuards', 'clients', 'guardRoaster', 'clientSites'));
    }

    public function update(Request $request, $id)
    {
        if(!Gate::allows('edit guard roaster')) {
            abort(403);
        }
        $request->validate([
            'guard_id'    => 'required',
            'client_id'    => 'required',
            'client_site_id' => 'required',
            'start_time'     => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
            'end_time'       => ['required', 'regex:/^(0[1-9]|1[0-2]):([0-5][0-9])( ?[APap][Mm])$/'],
        ]);

        $guardRoaster = GuardRoster::where('id', $id)->first();

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
            'date'           => $request->date,
            'start_time'     => $start_time,
            'end_time'       => $end_time,
            'end_date'       => $request->end_date
        ]);

        return redirect()->route('guard-rosters.index')->with('success', 'Guard Roster updated successfully.');
    }

    public function getClientSites($clientId)
    {
        $clientSites = ClientSite::where('client_id', $clientId)->where('status', 'Active')->get();

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
        if(!Gate::allows('delete guard roaster')) {
            abort(403);
        }
        GuardRoster::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guard Roster deleted successfully.'
        ]);
    }

    public function getGuardRoasterDetails(Request $request)
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

    public function importGuardRoaster(Request $request)
    {
        $import = new GuardRoasterImport;
        Excel::import($import, $request->file('file'));

        session(['importData' => $import]);
        session()->flash('success', 'Guard roster imported successfully.');
        $downloadUrl = route('guard-roasters.download');

        return redirect()->route('guard-rosters.index')->with('downloadUrl', $downloadUrl); 
    }

    public function download()
    {
        $import = session('importData'); 
        $export = new GuardRoasterExport($import);
        return Excel::download($export, 'guard_import_results.csv');
    }

    public function downloadExcel()
    {
        $spreadsheet = new Spreadsheet();

        $this->addGuardsSheet($spreadsheet);
        $this->addClientsSheet($spreadsheet);
        $this->addClientSitesSheet($spreadsheet);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Guard_Roster_configuration.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addGuardsSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guards');

        $headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone Number', 'TRN', 'NIS', 'PSRA', 'Date Of Joining', 'Date Of Birth', 'Employer Company Name', 'Guard Current Rate', 'Location Code', 'Location Name', 'Client Code', 'Client Name', 'Guard Type Id', 'Employed As', 'Date of Seperation', 'Bank Name', 'Bank Branch Address', 'Account no', 'Account Type', 'Routing Number', 'Surname', 'First Name', 'Middle Name', 'Appartment No', 'Building Name', 'Street Name', 'Parish', 'City', 'Postal Code', 'Email', 'Phone Number', 'Trn Doc', 'NIS Doc', 'PSRA Doc', 'Birth Certificate Doc', 'Appratment No', 'Building Name', 'Street Name', 'Parish', 'City', 'Postal Code'];
        $sheet->fromArray($headers, NULL, 'A1');

        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'Security Guard');
        })->with('guardAdditionalInformation', 'usersBankDetail', 'usersKinDetail', 'userDocuments', 'contactDetail')->get();

        foreach ($users as $key => $user) {
            $sheet->fromArray(
                [$user->id, $user->first_name, $user->last_name, $user->email, $user->phone_number, $user->guardAdditionalInformation->trn, $user->guardAdditionalInformation->nis, $user->guardAdditionalInformation->psra, $user->guardAdditionalInformation->date_of_joining, $user->guardAdditionalInformation->date_of_birth, $user->guardAdditionalInformation->employer_company_name, $user->guardAdditionalInformation->guards_Current_rate, $user->guardAdditionalInformation->location_code, $user->guardAdditionalInformation->location_name, $user->guardAdditionalInformation->client_code, $user->guardAdditionalInformation->client_name, $user->guardAdditionalInformation->guard_type_id, $user->guardAdditionalInformation->employed_as, $user->guardAdditionalInformation->date_of_seperation, $user->usersBankDetail->bank_name, $user->usersBankDetail->bank_branch_address, $user->usersBankDetail->account_no, $user->usersBankDetail->account_type, $user->usersBankDetail->routing_number, $user->usersKinDetail->surname, $user->usersKinDetail->first_name, $user->usersKinDetail->middle_name, $user->usersKinDetail->apartment_no, $user->usersKinDetail->building_name, $user->usersKinDetail->street_name, $user->usersKinDetail->parish, $user->usersKinDetail->city, $user->usersKinDetail->postal_code, $user->usersKinDetail->email, $user->usersKinDetail->phone_number,  url($user->userDocuments->trn), url($user->userDocuments->nis), url($user->userDocuments->psra), url($user->userDocuments->birth_certificate), $user->contactDetail->apartment_no, $user->contactDetail->building_name, $user->contactDetail->street_name, $user->contactDetail->parish, $user->contactDetail->city, $user->contactDetail->postal_code],
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
            'ID', 'Client Id', 'Client Name', 'Client Location', 'Parish', 'Billing Address', 'Vanguard Manager', 'Contact Operation', 
            'Telephone Number', 'Email', 'Invoice Recipient Main', 'Invoice Recipient Copy', 'Account Payable Contact Name', 
            'Account Payable Contact Email', 'Telephone Number', 'Latitude', 'Longitude', 'Radius', 'Status'
        ];
        $sheet->fromArray($headers, NULL, 'A1');

        $clientSites = ClientSite::with('client')->get();

        foreach ($clientSites as $key => $clientSite) {
            $data = [
                $clientSite->id,
                $clientSite->client_id,
                $clientSite->client->client_name ?? '',
                $clientSite->location_code ?? '',
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

    public function getGuardRoasters(Request $request)
    {
        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        if (!$fortnight) {
            return response()->json([
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        }

        $query = GuardRoster::with('user', 'client', 'clientSite')
            ->whereBetween('date', [$fortnight->start_date, $fortnight->end_date]);

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function($query) use ($searchValue) {
                $query->whereHas('user', function($query) use ($searchValue) {
                    $query->where('first_name', 'like', '%' . $searchValue . '%');
                })
                ->orWhereHas('client', function($query) use ($searchValue) {
                    $query->where('client_name', 'like', '%' . $searchValue . '%');
                })
                ->orWhereHas('clientSite', function($query) use ($searchValue) {
                    $query->where('location_Code', 'like', '%' . $searchValue . '%');
                });
            });
        }

        $totalRecords = $query->count();

        $perPage = $request->input('length', 10);
        $currentPage = (int)($request->input('start', 0) / $perPage);
        $guardRoasters = $query->skip($currentPage * $perPage)->take($perPage)->get()
            ->groupBy(function($item) {
                return $item->user->first_name .'-'. $item->client_site_id;
            });

        $formattedGuardRoasters = $guardRoasters->map(function ($items) {
            $firstItem = $items->first();
            $dates = $items->pluck('date')->implode(', ');
            
            $time_in_out = $items->map(function ($item) {
                return [
                    'date' => $item->date,
                    'time_in' => \Carbon\Carbon::parse($item->start_time)->format('h:iA'),  // 12-hour AM/PM format
                    'time_out' => \Carbon\Carbon::parse($item->end_time)->format('h:iA')   // 12-hour AM/PM format
                ];
            });

            return [
                'guard_name' => $firstItem->user->first_name . ' ' . optional($firstItem->user)->surname,
                'location_code' => $firstItem->clientSite->location_code,
                'client_name' => $firstItem->client->client_name,
                'time_in_out' => $time_in_out,  // Added time_in and time_out
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,  // Adjust filtered count if needed
            'data' => $formattedGuardRoasters
        ]);
    }
}