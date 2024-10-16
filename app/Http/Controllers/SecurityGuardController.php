<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\GuardAdditionalInformation;
use App\Models\ContactDetail;
use App\Models\UsersBankDetail;
use App\Models\UsersKinDetail;
use App\Models\UsersDocuments;
use App\Models\RateMaster;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

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
        $rateMasters = RateMaster::latest()->get();

        return view('admin.security-guards.create', compact('rateMasters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'    => 'required',
            'email'         => 'nullable|email|unique:users,email',
            'phone_number'  => 'nullable|numeric|unique:users,phone_number',
            'password'      => 'required',
            'trn_doc'       => 'required',
            'nis_doc'       => 'required',
            'psra_doc'      => 'required',
            'birth_certificate' => 'required',
        ]);

        $user = User::create([
            'surname'      => $request->surname,
            'first_name'   => $request->first_name,
            'middle_name'  => $request->middle_name,
            'email'        => $request->email,
            'phone_number' => $request->phone_number,
            'status'       => $request->input('status') ?? 'Active',
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
                'guard_type_id'         => $request->guard_type_id,
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
                'trn'       => uploadFile($request->file('trn_doc'), 'uploads/user-documents/trn/'),
                'nis'       => uploadFile($request->file('nis_doc'), 'uploads/user-documents/nis/'),
                'psra'      => uploadFile($request->file('psra_doc'),'uploads/user-documents/psra/'),
                'birth_certificate' => uploadFile($request->file('birth_certificate'), 'uploads/user-documents/birth_certificate/'),
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
        $rateMasters = RateMaster::latest()->get();

        $user = User::with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail', 'userDocuments'])->where('id', $id)->first();
    
        return view('admin.security-guards.edit', compact('user', 'rateMasters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'first_name'    => 'required',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone_number' => 'nullable|numeric|unique:users,phone_number,' . $id,
            'password'      => 'nullable',
            'trn_doc'       => 'nullable',
            'nis_doc'       => 'nullable',
            'psra_doc'      => 'nullable',
            'birth_certificate' => 'nullable',
        ]);

        $user = User::findOrFail($id);
        $user->surname      = $request->surname;
        $user->first_name   = $request->first_name;
        $user->middle_name  = $request->middle_name;
        $user->email        = $request->email;
        $user->phone_number = $request->phone_number;
        $user->status       = $request->user_status ?? 'Active';

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
                'guard_type_id'         => $request->guard_type_id,
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
            $documents['trn'] = uploadFile($request->file('trn_doc'), 'uploads/user-documents/trn/');
        }
        if ($request->hasFile('nis_doc')) {
            $documents['nis'] = uploadFile($request->file('nis_doc'), 'uploads/user-documents/nis/');
        }
        if ($request->hasFile('psra_doc')) {
            $documents['psra'] = uploadFile($request->file('psra_doc'), 'uploads/user-documents/psra/');
        }
        if ($request->hasFile('birth_certificate')) {
            $documents['birth_certificate'] = uploadFile($request->file('birth_certificate'), 'uploads/user-documents/birth_certificate/');
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

        return response()->json([
            'success' => true,
            'message' => 'Security Guard deleted successfully.'
        ]);
    }

    public function exportGuards()
    {
        // Retrieve all users with their related data
        $guards = User::with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail','userDocuments'])->get();
        
        //dd($guards);
        // Map the users' data into a CSV-friendly format
        $guardArray = $guards->map(function ($guards) {
            return [
                "First Name"        => $guards->first_name,
                "Middle Name"       => $guards->last_name,
                "Surname"           => $guards->surname,
                //Addtitional Detail
                "Guard's TRN"       => $guards->guardAdditionalInformation->trn ?? '',
                "NIS/NHT Number"    => $guards->guardAdditionalInformation->nis ?? '',
                "PSRA Registration No" => $guards->guardAdditionalInformation->psra ?? '',
                "Guard's Date of Joining" => $guards->guardAdditionalInformation->date_of_joining ?? '',
                "Date of Birth"         => $guards->guardAdditionalInformation->date_of_birth ?? '',
                "Employer Company Name" => $guards->guardAdditionalInformation->employer_company_name ?? '',
                "Guard's Current Rate"  => $guards->guardAdditionalInformation->guards_current_rate ?? '',
                "Location Code"      => $guards->guardAdditionalInformation->location_code ?? '',
                "Location Name"      => $guards->guardAdditionalInformation->location_name ?? '',
                "Client Code"        => $guards->guardAdditionalInformation->client_code ?? '',
                "Client Name"        => $guards->guardAdditionalInformation->client_name ?? '',
                "Guard Type"         => $guards->guardAdditionalInformation->guard_type_id ?? '',
                "Employed As"        => $guards->guardAdditionalInformation->employed_as ?? '',
                "Date of Separation" => $guards->guardAdditionalInformation->date_of_seperation ?? '',
                //Contact details
                "Apartment No"      => $guards->contactDetail->apartment_no ?? '',
                "Building Name"     => $guards->contactDetail->building_name ?? '',
                "Street Name"       => $guards->contactDetail->street_name ?? '',
                "Parish"            => $guards->contactDetail->parish ?? '',
                "City"              => $guards->contactDetail->city ?? '',
                "Postal Code"       => $guards->contactDetail->postal_code ?? '',
                "Email"             => $guards->email ?? '',
                "Phone Number"      => $guards->phone_number ?? '',
                //Bank details
                "Bank Name"         => $guards->usersBankDetail->bank_name ?? '',
                "Bank Branch Address" => $guards->usersBankDetail->bank_branch_address ?? '',
                "Account Number"    => $guards->usersBankDetail->account_no ?? '',
                "Account Type"      => $guards->usersBankDetail->account_type ?? '',
                "Routing Number"    => $guards->usersBankDetail->routing_number ?? '',
                //Next of Kin details
                "Kin Surname"       => $guards->usersKinDetail->surname ?? '',
                "Kin First Name"    => $guards->usersKinDetail->first_name ?? '',
                "Kwin Middle Name"  => $guards->usersKinDetail->middle_name ?? '',
                "Kin Apartment No"  => $guards->usersKinDetail->apartment_no ?? '',
                "Kin Building Name" => $guards->usersKinDetail->building_name ?? '',
                "Kin Street Name"   => $guards->usersKinDetail->street_name ?? '',
                "Kin Parish"        => $guards->usersKinDetail->parish ?? '',
                "KinCity"           => $guards->usersKinDetail->city ?? '',
                "Kin Postal Code"   => $guards->usersKinDetail->postal_code ?? '',
                "Kin Email"         => $guards->usersKinDetail->email ?? '',
                "Kin Phone Number"  => $guards->usersKinDetail->phone_number ?? '',
                //User Documents
                "TRN Document"      => $guards->userDocuments->trn ?? '',
                "NIS Document"      => $guards->userDocuments->nis ?? '',
                "PSRA Document"     => $guards->userDocuments->psra ?? '',
                "Birth Certificate" => $guards->userDocuments->birth_certificate ?? '',
            ];
        })->toArray();

        // Define CSV column headers
        $headers = [
            "First Name","Middle Name","Surname",
            //Addtitional Detail
            "Guard's TRN","NIS/NHT Number","PSRA Registration No","Guard's Date of Joining","Date of Birth",
            "Employer Company Name","Guard's Current Rate","Location Code","Location Name","Client Code","Client Name","Guard Type","Employed As","Date of Separation",
            //Contact details
            "Apartment No","Building Name","Street Name","Parish","City","Postal Code","Email","Phone Number",
            //Bank details
            "Bank Name","Bank Branch Address","Account Number","Account Type","Routing Number",
            //Next of Kin details
            "Kin Surname","kin First Name","kin Middle Name","Kin Apartment No",
            "Kin Building Name","Kin Street Name","Kin Parish","KinCity","Kin Postal Code","Kin Email","Kin Phone Number",
            //User Documents
            "TRN Document","NIS Document","PSRA Document","Birth Certificate",
        ];

        // Add headers at the top of the array
        array_unshift($guardArray, $headers);

        // Create a callback to generate the CSV
        $callback = function () use ($guardArray) {
            $file = fopen('php://output', 'w');
            foreach ($guardArray as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        // Return CSV as a response
        return response()->stream($callback, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="guards.csv"',
        ]);
    }
    public function importGuards(Request $request) {
        $request->validate([
            'import_guard' => 'required|file|mimes:csv,txt',
        ]);
    
        $file = $request->file('import_guard');
        $fileData = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($fileData);
    
        foreach ($fileData as $row) {
            $rowData = array_combine($headers, $row);
    
            $user = User::where('email', $rowData["Email"])
                        ->orWhere('phone_number', $rowData["Phone Number"])
                        ->first();
    
            if (!$user) {
                // Create a new user if not found
                $user = User::create([
                    'user_code'     => null, // Adjust as needed
                    'first_name'    => $rowData["First Name"],
                    'middle_name'   => $rowData["Middle Name"] ?? null,
                    'last_name'     => $rowData["Last Name"] ?? null,
                    'surname'       => $rowData["Surname"] ?? null,
                    'email'         => $rowData["Email"],
                    'phone_number'  => $rowData["Phone Number"] ?? null,
                    'password'      => Hash::make('Guard@12345'),
                ]);
                $user->assignRole('Security Guard');
            }
    
            // Update or create related data
            $user->guardAdditionalInformation()->updateOrCreate([], [
                'trn'                 => $rowData["Guard's TRN"] ?? null,
                'nis'                 => $rowData["NIS/NHT Number"] ?? null,
                'psra'                => $rowData["PSRA Registration No"] ?? null,
                'date_of_joining'     => !empty($rowData["Guard's Date of Joining"]) ? $rowData["Guard's Date of Joining"] : null,
                'date_of_birth'       => !empty($rowData["Date of Birth"]) ? $rowData["Date of Birth"] : null,
                'employer_company_name' => $rowData["Employer Company Name"] ?? null,
                'guards_current_rate' => $rowData["Guard's Current Rate"] ?? null,
                'location_code'       => $rowData["Location Code"] ?? null,
                'location_name'       => $rowData["Location Name"] ?? null,
                'client_code'         => $rowData["Client Code"] ?? null,
                'client_name'         => $rowData["Client Name"] ?? null,
                'guard_type_id'       => $rowData["Guard Type"] ?? null,
                'employed_as'         => $rowData["Employed As"] ?? null,
                'date_of_seperation'  => !empty($rowData["Date of Separation"]) ? $rowData["Date of Separation"] : null,
            ]);
    
            // Update contact details
            $user->contactDetail()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'apartment_no'  => $rowData["Apartment No"] ?? null,
                    'building_name' => $rowData["Building Name"] ?? null,
                    'street_name'   => $rowData["Street Name"] ?? null,
                    'parish'        => $rowData["Parish"] ?? null,
                    'city'          => $rowData["City"] ?? null,
                    'postal_code'   => $rowData["Postal Code"] ?? null,
                ]
            );
    
            // Update bank details
            $user->usersBankDetail()->updateOrCreate([], [
                'user_id'             => $user->id,
                'bank_name'           => $rowData["Bank Name"] ?? null,
                'bank_branch_address' => $rowData["Bank Branch Address"] ?? null,
                'account_no'          => $rowData["Account Number"] ?? null,
                'account_type'        => $rowData["Account Type"] ?? null,
                'routing_number'      => $rowData["Routing Number"] ?? null,
            ]);
    
            // Update next of kin details
            $user->usersKinDetail()->updateOrCreate([], [
                'user_id'           => $user->id,
                'surname'           => $rowData["Kin Surname"] ?? null,
                'first_name'        => $rowData["Kin First Name"] ?? null,
                'middle_name'       => $rowData["Kin Middle Name"] ?? null,
                'apartment_no'      => $rowData["Kin Apartment No"] ?? null,
                'building_name'     => $rowData["Kin Building Name"] ?? null,
                'street_name'       => $rowData["Kin Street Name"] ?? null,
                'parish'            => $rowData["Kin Parish"] ?? null,
                'city'              => $rowData["Kin City"] ?? null,
                'postal_code'       => $rowData["Kin Postal Code"] ?? null,
                'email'             => $rowData["Kin Email"] ?? null,
                'phone_number'      => $rowData["Kin Phone Number"] ?? null,
            ]);
    
            // Update user documents
            $user->userDocuments()->updateOrCreate([], [
                'user_id'   => $user->id,
                'trn'       => $rowData["TRN Document"] ?? null,
                'nis'       => $rowData["NIS Document"] ?? null,
                'psra'      => $rowData["PSRA Document"] ?? null,
                'birth_certificate' => $rowData["Birth Certificate"] ?? null,
            ]);
        }
    
        return redirect()->back()->with('success', 'Guards imported successfully!');
    }
}
