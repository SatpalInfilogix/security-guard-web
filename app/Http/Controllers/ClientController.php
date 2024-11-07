<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::latest()->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_name'  => 'required',
            'client_code' => 'required|unique:clients,client_code',
        ]);

        Client::create([
            'client_name' => $request->client_name,
            'client_code' => $request->client_code,
        ]);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show($id)
    {
        //
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'client_name'  => 'required',
            'client_code' => 'required|unique:clients,client_code,' . $client->id,
        ]);

        $client->update([
            'client_name' => $request->client_name,
            'client_code' => $request->client_code,
        ]);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully.'
        ]);
    }

    public function generateClientCode(Request $request)
    {
        $clientName = $request->input('client_name');
        $clientName = $this->sanitizeClientName($clientName);
        $clientCode = $this->generateCode($clientName);

        $baseCode = substr($clientCode, 0, -3);
        $existingClient = Client::where('client_code', $clientCode)->first();
        if ($existingClient) {
            $counter = 2;
            
            while (Client::where('client_code', "{$clientCode}".str_pad($counter, 3, '0', STR_PAD_LEFT))->exists()) {
                $counter++;
            }

            $clientCode = $baseCode . str_pad($counter, 3, '0', STR_PAD_LEFT);
        }

        return response()->json(['client_code' => $clientCode]);
    }

    private function generateCode($clientName)
    {
        $words = preg_split('/\s+/', strtoupper(trim($clientName)));
        if (count($words) >= 3) {
            $code = $words[0][0] . $words[1][0] . $words[2][0];
        } elseif (count($words) == 2) {
            $code = $words[0][0] . $words[1][0] . substr($words[1], 1, 1);
        } elseif (count($words) == 1) {
            $code = substr($words[0], 0, 3);
        }
    
        return strtoupper($code) . '001';
    }
    private function sanitizeClientName($clientName)
    {
        return preg_replace('/[^A-Za-z\s]/', '', $clientName);
    }

}
