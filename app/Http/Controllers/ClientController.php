<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view client')) {
            abort(403);
        }

        $clients = Client::latest()->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function getClient(Request $request)
    {
        $clients = Client::query();

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $clients->where(function($query) use ($searchValue) {
                $query->where('client_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('client_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('nis', 'like', '%' . $searchValue . '%');
            });
        }

        $totalRecords = Client::count();

        $filteredRecords = $clients->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $clients = $clients->skip($start)->take($length)->get();

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $clients,
        ];

        return response()->json($data);
    }


    public function create()
    {
        if(!Gate::allows('create client')) {
            abort(403);
        }

        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create client')) {
            abort(403);
        }

        $request->validate([
            'client_name'  => 'required',
            'client_code' => 'required|unique:clients,client_code',
        ]);

        Client::create([
            'client_name' => $request->client_name,
            'client_code' => $request->client_code,
            'nis'         => $request->nis,
            'gct'         => $request->gct
        ]);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show($id)
    {
        //
    }

    public function edit(Client $client)
    {
        if(!Gate::allows('edit client')) {
            abort(403);
        }

        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        if(!Gate::allows('edit client')) {
            abort(403);
        }

        $request->validate([
            'client_name'  => 'required',
            'client_code' => 'required|unique:clients,client_code,' . $client->id,
        ]);

        $client->update([
            'client_name' => $request->client_name,
            'client_code' => $request->client_code,
            'nis'         => $request->nis,
            'gct'         => $request->gct
        ]);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        if(!Gate::allows('delete client')) {
            abort(403);
        }

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
        $clientId = $request->input('client_id');

        if ($clientId) {
            $existingClient = Client::where('client_code', $clientCode)->where('id', '!=', $clientId)->first();
        } else {
            $existingClient = Client::where('client_name', $clientName)->orderBy('created_at', 'desc') ->first();
        }

        if ($existingClient) {
            $lastNumericPart = (int) substr($existingClient->client_code, -3);
            $counter = $lastNumericPart + 1; // Increment by 1
    
            while (Client::where('client_code', "{$baseCode}" . str_pad($counter, 3, '0', STR_PAD_LEFT))->exists()) {
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
