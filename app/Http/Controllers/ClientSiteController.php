<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientSite;
use App\Models\Client;

class ClientSiteController extends Controller
{
    public function index()
    {
        $clientSites = ClientSite::with('client')->latest()->get();

        return view('admin.client-sites.index', compact('clientSites'));
    }

    public function create()
    {
        $clients = Client::latest()->get();

        return view('admin.client-sites.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id'  => 'required',
            'location_code' => 'required|unique:client_sites,location_code',
        ]);

        ClientSite::create([
            'client_id'         => $request->client_id,
            'location_code'     => $request->location_code,
            'parish'            => $request->parish,
            'billing_address'   => $request->billing_address,
            'vanguard_manager'  => $request->vanguard_manager,
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
        $clients = Client::latest()->get();

        return view('admin.client-sites.edit', compact('clients', 'clientSite'));
    }

    public function update(Request $request, ClientSite $clientSite)
    {
        $request->validate([
            'client_id'  => 'required',
            'location_code' => 'required|unique:client_sites,location_code,' . $clientSite->id,
        ]);

        $clientSite->update([
            'client_id'         => $request->client_id,
            'location_code'     => $request->location_code,
            'parish'            => $request->parish,
            'billing_address'   => $request->billing_address,
            'vanguard_manager'  => $request->vanguard_manager,
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
        $clientSite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client Site deleted successfully.'
        ]);
    }
}
