<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuardRoaster;
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

class GuardRoasterController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view guard roaster')) {
            abort(403);
        }
        $guardRoasters = GuardRoaster::with('user', 'client')->latest()->get();

        return view('admin.guard-roaster.index', compact('guardRoasters'));
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

        return view('admin.guard-roaster.create', compact('securityGuards', 'clients'));
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
        
        $guardRoaster = GuardRoaster::updateOrCreate(
            [
                'guard_id' => $request->guard_id,
                'date'     => $request->date,  // We use these two attributes to search for the existing record
            ],
            [
                'client_id'      => $request->client_id,
                'client_site_id' => $request->client_site_id,
                'start_time'     => $start_time,
                'end_time'       => $end_time
            ]
        );

        return redirect()->route('guard-roasters.index')->with('success', 'Guard Roaster created successfully.');
    }

    public function show($id) {
        //
    }

    public function edit(GuardRoaster $guardRoaster) 
    {
        if(!Gate::allows('edit guard roaster')) {
            abort(403);
        }
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

        return view('admin.guard-roaster.edit', compact('securityGuards', 'clients', 'guardRoaster', 'clientSites'));
    }

    public function update(Request $request, GuardRoaster $guardRoaster)
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

        $existingGuardRoaster = GuardRoaster::where('guard_id', $request->guard_id)->where('date', $request->date)
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
            'end_time'       => $end_time
        ]);

        return redirect()->route('guard-roasters.index')->with('success', 'Guard Roaster updated successfully.');
    }

    public function getClientSites($clientId)
    {
        $clientSites = ClientSite::where('client_id', $clientId)->where('status', 'Active')->get();

        return response()->json($clientSites);
    }

    public function getAssignedDate($guardId)
    {
        $assignedDates = GuardRoaster::where('guard_id', $guardId)->pluck('date')
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

    public function destroy(GuardRoaster $guardRoaster)
    {
        if(!Gate::allows('delete guard roaster')) {
            abort(403);
        }
        $guardRoaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guard Roaster deleted successfully.'
        ]);
    }

    public function getGuardRoasterDetails(Request $request)
    {
        $guardId = $request->input('guard_id');
        $date = $request->input('date');

        if (!$guardId || !$date) {
            return response()->json(['error' => 'Date and Guard ID are required'], 400);
        }

        $guardRoaster = GuardRoaster::where('guard_id', $guardId)
                                    ->where('date', $date)
                                    ->first();

        if (!$guardRoaster) {
            return response()->json(['error' => 'No roaster found for this guard and date'], 404);
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
        session()->flash('success', 'Guard roaster imported successfully.');
        $downloadUrl = route('guard-roasters.download');

        return redirect()->route('guard-roasters.index')->with('downloadUrl', $downloadUrl); 
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
        $fileName = 'Guard_Roaster_configuration' . '.xlsx';
    
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
        $sheet = $spreadsheet->getSheet(1);
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

        $spreadsheet->createSheet();
    }

    protected function addClientSitesSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheet(2);
        $sheet->setTitle('Client-Sites');

        $headers = ['ID', 'Client Id', 'Client Name', 'Client Location', 'Parish', 'Billing Address', 'vanguard Manager', 'Contact Operation', 'Telephone Number', 'Email', 'Invoice Recipient Main', 'Invoice Recipient Copy', 'Account Payable Contact Name', 'Email', 'Number', 'Number 2', 'Account Payable Contact Email', 'Email', 'Telephone Number', 'Latitude', 'longitude', 'radius', 'status'];
        $sheet->fromArray($headers, NULL, 'A1');

        $clientSites = ClientSite::with('client')->get();

        foreach ($clientSites as $key => $clientSite) {
            $sheet->fromArray(
                [$clientSite->id, $clientSite->client_id, $clientSite->client->client_name, $clientSite->location_code, $clientSite->parish, $clientSite->billing_address, $clientSite->vanguard_manager, $clientSite->contact_operation, $clientSite->telephone_number, $clientSite->email, $clientSite->invoice_recipient_main, $clientSite->invoice_recipient_copy, $clientSite->account_payable_contact_name, $clientSite->email_2, $clientSite->number, $clientSite->number_2, $clientSite->account_payable_contact_email, $clientSite->email_3, $clientSite->telephone_number_2, $clientSite->latitude, $clientSite->longitude, $clientSite->radius, $clientSite->status],
                NULL,
                'A' . ($key + 2)
            );
        }
    }

    public function getLeaves($guardId)
    {
        $leaves = Leave::where('guard_id', $guardId)->whereIn('status', ['Approved', 'Pending'])->latest()->get();

        return response()->json($leaves);
    }
}
