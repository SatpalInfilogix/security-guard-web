<?php

namespace App\Http\Controllers;

use App\Exports\ClientSiteExport;
use App\Imports\ClientSiteImport;
use Illuminate\Http\Request;
use App\Models\ClientSite;
use App\Models\Client;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Spatie\Permission\Traits\HasRoles;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Facades\Excel;

class ClientSiteController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view client site')) {
            abort(403);
        }
        $clientSites = ClientSite::with('client')->latest()->get();

        return view('admin.client-sites.index', compact('clientSites'));
    }

    public function getClientSite(Request $request)
    {
        $clientSites = ClientSite::with('client');

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $clientSites->where(function($query) use ($searchValue) {
                $query->where('location_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('parish', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('client', function($q) use ($searchValue) {
                        $q->where('client_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = ClientSite::count();

        $filteredRecords = $clientSites->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $clientSites = $clientSites->skip($start)->take($length)->get();

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $clientSites,
        ];

        return response()->json($data);
    }

    public function create()
    {
        if(!Gate::allows('create client site')) {
            abort(403);
        }
        $clients = Client::latest()->get();
        $userRole = Role::where('name', 'General Manager')->first();
        $users = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->latest()->get();

        return view('admin.client-sites.create', compact('clients', 'users'));
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create client site')) {
            abort(403);
        }


        $request->validate([
            'client'  => 'required',
            'location_code' => 'required|unique:client_sites,location_code',
            'location'      => 'required',
            'latitude'      => 'required',
            'longitude'     => 'required',
            'radius'        => 'required',
            'manager'    => 'required'
        ]);

        dd($request->all());

        ClientSite::create([
            'client_id'         => $request->client,
            'location_code'     => $request->location_code,
            'parish'            => $request->parish,
            'billing_address'   => $request->billing_address,
            'manager_id'        => $request->manager,
            'contact_operation' => $request->contact_operation,
            'telephone_number'  => $request->telephone_number,
            'email'             => $request->email,
            'invoice_recipient_main' => $request->invoice_recipient_main,
            'invoice_recipient_copy' => $request->invoice_recipient_copy,
            'account_payable_contact_name' => $request->account_payable_contact_name,
            'email_2'           => $request->email_2,
            'number'            => $request->number,
            'number_2'          => $request->number_2,
            'account_payable_contact_email' => $request->account_payable_contact_email,
            'email_3'           => $request->email_3,
            'telephone_number_2'=> $request->telephone_number_2,
            'status'            => $request->status,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'radius'            => $request->radius
        ]);

        return redirect()->route('client-sites.index')->with('success', 'Client Site created successfully.');
    }

    public function show()
    {
        //
    }

    public function edit(ClientSite $clientSite)
    {
        if(!Gate::allows('edit client site')) {
            abort(403);
        }
        $clients = Client::latest()->get();
        $userRole = Role::where('name', 'General Manager')->first();
        $users = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->latest()->get();

        return view('admin.client-sites.edit', compact('clients', 'clientSite', 'users'));
    }

    public function update(Request $request, ClientSite $clientSite)
    {
        if(!Gate::allows('edit client site')) {
            abort(403);
        }
        $request->validate([
            'client_id'  => 'required',
            'location_code' => 'required|unique:client_sites,location_code,' . $clientSite->id,
            'latitude'      => 'required',
            'longitude'     => 'required',
            'radius'        => 'required',
            'manager_id'    => 'required'
        ]);

        $clientSite->update([
            'client_id'         => $request->client_id,
            'location_code'     => $request->location_code,
            'parish'            => $request->parish,
            'billing_address'   => $request->billing_address,
            'manager_id'        => $request->manager_id,
            'contact_operation' => $request->contact_operation,
            'telephone_number'  => $request->telephone_number,
            'email'             => $request->email,
            'invoice_recipient_main' => $request->invoice_recipient_main,
            'invoice_recipient_copy' => $request->invoice_recipient_copy,
            'account_payable_contact_name' => $request->account_payable_contact_name,
            'email_2'           => $request->email_2,
            'number'            => $request->number,
            'number_2'          => $request->number_2,
            'account_payable_contact_email' => $request->account_payable_contact_email,
            'email_3'          => $request->email_3,
            'telephone_number_2'=> $request->telephone_number_2,
            'status'            => $request->status,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'radius'            => $request->radius
        ]);

        return redirect()->route('client-sites.index')->with('success', 'Client Site updated successfully.');
    }

    public function destroy(ClientSite $clientSite)
    {
        if(!Gate::allows('delete client site')) {
            abort(403);
        }
        $clientSite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client Site deleted successfully.'
        ]);
    }

    public function exportClients()
    {
        $spreadsheet = new Spreadsheet();

        $this->addClientSheet($spreadsheet);
        $this->addManagerSheet($spreadsheet);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Client_configuration.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addClientSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clients');

        $headers = ['ID', 'Client Code', 'Client Name', 'Nis'];
        $sheet->fromArray($headers, NULL, 'A1');

        $clients = Client::all();

        foreach ($clients as $key => $client) {
            $sheet->fromArray(
                [$client->id, $client->client_code, $client->client_name, $client->nis],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    protected function addManagerSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheet(1);  // Get the second sheet (index 1)
        $sheet->setTitle('General Managers');

        $headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone Number'];
        $sheet->fromArray($headers, NULL, 'A1');

        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'General Manager');
        })->get();

        foreach ($users as $key => $user) {
            $sheet->fromArray(
                [$user->id, $user->first_name, $user->last_name, $user->email, $user->phone_number],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();  // Move to next sheet
    }

    public function importClientSite(Request $request)
    {
        $import = new ClientSiteImport;
        Excel::import($import, $request->file('file'));

        session(['importData' => $import]);
        session()->flash('success', 'Client Site imported successfully.');
        $downloadUrl = route('client-site.download');

        return redirect()->route('client-sites.index')->with('downloadUrl', $downloadUrl); 
    }

    public function download()
    {
        $import = session('importData'); 
        $export = new ClientSiteExport($import);
        return Excel::download($export, 'client_site_import_results.csv');
    }
}
