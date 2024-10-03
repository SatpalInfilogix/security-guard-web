<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\GuardAdditionalInformation;
use App\Models\ContactDetail;
use App\Models\usersBankDetail;
use App\Models\usersKinDetail;
use App\Models\usersDocuments;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SecurityGuardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->latest()->get();

        return view('admin.security-guards.index', compact('securityGuards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.security-guards.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'    => 'required',
            'email'         => 'nullable|email|unique:users,email|required_without:phone_number',
            'phone_number'  => 'nullable|numeric|unique:users,phone_number|required_without:email',
            'password'      => 'required',
            'trn_doc'       => 'required',
            'nis_doc'       => 'required',
            'psra_doc'      => 'required',
            'birth_certificate' => 'required',
        ]);

        $user = User::create([
            'surname'   => $request->surname,
            'first_name' => $request->first_name,
            'middle_name'  => $request->middle_name,
            'email'       => $request->email,
            'phone_number' => $request->phone_number,
            'password'     => Hash::make($request->password),
        ])->assignRole('Security Guard');

        if ($user) {
            GuardAdditionalInformation::create([
                'user_id'               => $user->id,
                'trn'                   => $request->trn,
                'nis'                   => $request->nis,
                'psra'                  => $request->psra,
                'date_of_joining'       => $request->date_of_joining,
                'date_of_birth'         => $request->date_of_birth,
                'employer_company_name' => $request->employer_company_name,
                'guards_current_rate'   => $request->current_rate,
                'location_code'         => $request->location_code,
                'location_name'         => $request->location_name,
                'client_code'           => $request->client_code,
                'client_name'           => $request->client_name,
                'guard_type'            => $request->guard_type,
                'employed_as'           => $request->employed_as,
                'date_of_seperation'    => $request->date_of_seperation,
            ]);

            ContactDetail::create([
                'user_id'       => $user->id,
                'apartment_no'  => $request->apartment_no,
                'building_name' => $request->building_name,
                'street_name'   => $request->street_name,
                'parish'        => $request->parish,
                'city'          => $request->city,
                'postal_code'   => $request->postal_code,
            ]);

            UsersBankDetail::create([
                'user_id'               => $user->id,
                'bank_name'             => $request->bank_name,
                'bank_branch_address'   => $request->branch,
                'account_no'            => $request->account_number,
                'account_type'          => $request->account_type,
                'routing_number'        => $request->routing_number,
            ]);

            UsersKinDetail::create([
                'user_id'        => $user->id,
                'surname'        => $request->kin_surname,
                'first_name'     => $request->kin_first_name,
                'middle_name'    => $request->kin_middle_name,
                'apartment_no'   => $request->kin_apartment_no,
                'building_name'  => $request->kin_building_name,
                'street_name'    => $request->kin_street_name,
                'parish'         => $request->kin_parish,
                'city'           => $request->kin_city,
                'postal_code'    => $request->kin_postal_code,
                'email'          => $request->kin_email,
                'phone_number'   => $request->kin_phone_number,
            ]);

            usersDocuments::create([
                'user_id'   => $user->id,
                'trn'       => $this->uploadFile($request->file('trn_doc')),
                'nis'       => $this->uploadFile($request->file('nis_doc')),
                'psra'      => $this->uploadFile($request->file('psra_doc')),
                'birth_certificate' => $this->uploadFile($request->file('birth_certificate')),
            ]);
        }
        return redirect()->route('security-guards.index')->with('success', 'Security Guard created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail', 'userDocuments'])->where('id', $id)->first();
    
        return view('admin.security-guards.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'first_name'    => 'required',
            'email'         => 'nullable|email|unique:users,email,' . $user->id . '|required_without:phone_number',
            'phone_number'  => 'nullable|numeric|unique:users,phone_number,' . $user->id . '|required_without:email',
            'password'      => 'nullable',
            'trn_doc'       => 'nullable',
            'nis_doc'       => 'nullable',
            'psra_doc'      => 'nullable',
            'birth_certificate' => 'nullable',
        ]);

        $user = User::findOrFail($id);
        $user->surname = $request->surname;
        $user->first_name = $request->first_name;
        $user->middle_name = $request->middle_name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Update related records
        $guardInfo = GuardAdditionalInformation::where('user_id', $id)->first();
        if ($guardInfo) {
            $guardInfo->update([
                'trn'                   => $request->trn,
                'nis'                   => $request->nis,
                'psra'                  => $request->psra,
                'date_of_joining'       => $request->date_of_joining,
                'date_of_birth'         => $request->date_of_birth,
                'employer_company_name' => $request->employer_company_name,
                'guards_current_rate'   => $request->current_rate,
                'location_code'         => $request->location_code,
                'location_name'         => $request->location_name,
                'client_code'           => $request->client_code,
                'client_name'           => $request->client_name,
                'guard_type'            => $request->guard_type,
                'employed_as'           => $request->employed_as,
                'date_of_seperation'    => $request->date_of_seperation,
            ]);
        }

        $contactDetail = ContactDetail::where('user_id', $id)->first();
        if ($contactDetail) {
            $contactDetail->update([
                'apartment_no'  => $request->apartment_no,
                'building_name' => $request->building_name,
                'street_name'   => $request->street_name,
                'parish'        => $request->parish,
                'city'          => $request->city,
                'postal_code'   => $request->postal_code,
            ]);
        }

        $usersBankDetail = UsersBankDetail::where('user_id', $id)->first();
        $usersBankDetail->update([
            'bank_name'             => $request->bank_name,
            'bank_branch_address'   => $request->branch,
            'account_no'            => $request->account_number,
            'account_type'          => $request->account_type,
            'routing_number'        => $request->routing_number,
        ]);

        $usersKinDetail = UsersKinDetail::where('user_id', $id)->first();
        $usersKinDetail->update([
            'surname'        => $request->kin_surname,
            'first_name'     => $request->kin_first_name,
            'middle_name'    => $request->kin_middle_name,
            'apartment_no'   => $request->kin_apartment_no,
            'building_name'  => $request->kin_building_name,
            'street_name'    => $request->kin_street_name,
            'parish'         => $request->kin_parish,
            'city'           => $request->kin_city,
            'postal_code'    => $request->kin_postal_code,
            'email'          => $request->kin_email,
            'phone_number'   => $request->kin_phone_number,
        ]);

        $usersDocuments = usersDocuments::where('user_id', $id)->first();
        $documents = [];
        if ($request->hasFile('trn_doc')) {
            $documents['trn'] = $this->uploadFile($request->file('trn_doc'));
        }
        if ($request->hasFile('nis_doc')) {
            $documents['nis'] = $this->uploadFile($request->file('nis_doc'));
        }
        if ($request->hasFile('psra_doc')) {
            $documents['psra'] = $this->uploadFile($request->file('psra_doc'));
        }
        if ($request->hasFile('birth_certificate')) {
            $documents['birth_certificate'] = $this->uploadFile($request->file('birth_certificate'));
        }

        $usersDocuments->update($documents);

        return redirect()->route('security-guards.index')->with('success', 'Security Guard updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::where('id', $id)->delete();

        return redirect()->route('security-guards.index')->with('success', 'Security Guard deleted successfully.');
    }

    private function uploadFile($file)
    {   
        if ($file) {
            $filename = uniqid('', true) . '.' . $file->getClientOriginalExtension();
            $finalPath = 'uploads/documents/' . $filename;
            $file->move(public_path('uploads/documents/'), $filename);
            return $finalPath; 
        }
        return null;
    }
}
