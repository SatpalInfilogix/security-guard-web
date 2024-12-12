<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientSite;
use App\Models\Client;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use App\Models\User;

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
            'client_id'  => 'required',
            'location_code' => 'required|unique:client_sites,location_code',
            'latitude'      => 'required',
            'longitude'     => 'required',
            'radius'        => 'required',
            'manager_id'    => 'required'
        ]);

        ClientSite::create([
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
}
