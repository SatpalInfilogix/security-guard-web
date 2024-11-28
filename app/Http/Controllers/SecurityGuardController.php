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
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SecurityGuardImport;
use App\Exports\GuardImportExport;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class SecurityGuardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if(!Gate::allows('view security guards')) {
            abort(403);
        }

        $userRole = Role::where('name', 'Security Guard')->first();

        $query = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });

        if ($request->has('search_name') && $request->search_name) {
            $query->where(function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->search_name . '%');
            });
        }

        if ($request->has('search_email') && $request->search_email) {
            $query->where('email', 'like', '%' . $request->search_email . '%');
        }

        if ($request->has('search_phone') && $request->search_phone) {
            $query->where('phone_number', 'like', '%' . $request->search_phone . '%');
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $securityGuards = $query->latest()->get();

        if ($request->ajax()) {
            return response()->json([
                'view' => view('admin.security-guards.filter-table', compact('securityGuards'))->render()
            ]);
        }

        return view('admin.security-guards.index', compact('securityGuards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if(!Gate::allows('create security guards')) {
            abort(403);
        }

        $rateMasters = RateMaster::latest()->get();

        return view('admin.security-guards.create', compact('rateMasters'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(!Gate::allows('create security guards')) {
            abort(403);
        }

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
            'is_saturatory'=> $request->is_saturatory,
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
        if(!Gate::allows('edit security guards')) {
            abort(403);
        }

        $rateMasters = RateMaster::latest()->get();
        $user = User::with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail', 'userDocuments'])->where('id', $id)->first();

        return view('admin.security-guards.edit', compact('user', 'rateMasters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if(!Gate::allows('edit security guards')) {
            abort(403);
        }

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
        $user->is_saturatory= $request->is_saturatory;

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
        if(!Gate::allows('delete security guards')) {
            abort(403);
        }

        $user = User::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Security Guard deleted successfully.'
        ]);
    }

    public function exportGuards()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $guards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail','userDocuments'])->latest()->get();

        $guardArray = $guards->map(function ($guard) {
            return [
                "First Name"            => $guard->first_name,
                "Middle Name"           => $guard->middle_name,
                "Surname"               => $guard->surname,
                // Additional Detail
                "Guard's TRN"           => $guard->guardAdditionalInformation->trn ?? '',
                "NIS/NHT Number"        => $guard->guardAdditionalInformation->nis ?? '',
                "PSRA Registration No"  => $guard->guardAdditionalInformation->psra ?? '',
                "Guard's Date of Joining" => $guard->guardAdditionalInformation->date_of_joining ?? '',
                "Date of Birth"         => $guard->guardAdditionalInformation->date_of_birth ?? '',
                "Employer Company Name" => $guard->guardAdditionalInformation->employer_company_name ?? '',
                "Guard's Current Rate"  => $guard->guardAdditionalInformation->guards_current_rate ?? '',
                "Location Code"         => $guard->guardAdditionalInformation->location_code ?? '',
                "Location Name"         => $guard->guardAdditionalInformation->location_name ?? '',
                "Client Code"           => $guard->guardAdditionalInformation->client_code ?? '',
                "Client Name"           => $guard->guardAdditionalInformation->client_name ?? '',
                "Guard Type"            => $guard->guardAdditionalInformation->guard_type_id ?? '',
                "Employed As"           => $guard->guardAdditionalInformation->employed_as ?? '',
                "Date of Separation"    => $guard->guardAdditionalInformation->date_of_seperation ?? '',
                // Contact details
                "Apartment No"          => $guard->contactDetail->apartment_no ?? '',
                "Building Name"         => $guard->contactDetail->building_name ?? '',
                "Street Name"           => $guard->contactDetail->street_name ?? '',
                "Parish"                => $guard->contactDetail->parish ?? '',
                "City"                  => $guard->contactDetail->city ?? '',
                "Postal Code"           => $guard->contactDetail->postal_code ?? '',
                "Email"                 => $guard->email ?? '',
                "Phone Number"          => $guard->phone_number ?? '',
                // Bank details
                "Bank Name"             => $guard->usersBankDetail->bank_name ?? '',
                "Bank Branch Address"   => $guard->usersBankDetail->bank_branch_address ?? '',
                "Account Number"        => $guard->usersBankDetail->account_no ?? '',
                "Account Type"          => $guard->usersBankDetail->account_type ?? '',
                "Routing Number"        => $guard->usersBankDetail->routing_number ?? '',
                // Next of Kin details
                "Kin Surname"           => $guard->usersKinDetail->surname ?? '',
                "Kin First Name"        => $guard->usersKinDetail->first_name ?? '',
                "Kin Middle Name"       => $guard->usersKinDetail->middle_name ?? '',
                "Kin Apartment No"      => $guard->usersKinDetail->apartment_no ?? '',
                "Kin Building Name"     => $guard->usersKinDetail->building_name ?? '',
                "Kin Street Name"       => $guard->usersKinDetail->street_name ?? '',
                "Kin Parish"            => $guard->usersKinDetail->parish ?? '',
                "Kin City"              => $guard->usersKinDetail->city ?? '',
                "Kin Postal Code"       => $guard->usersKinDetail->postal_code ?? '',
                "Kin Email"             => $guard->usersKinDetail->email ?? '',
                "Kin Phone Number"      => $guard->usersKinDetail->phone_number ?? '',
                // User Documents
                "TRN Document"          => url($guard->userDocuments->trn ?? ''),
                "NIS Document"          => url($guard->userDocuments->nis ?? ''),
                "PSRA Document"         => url($guard->userDocuments->psra ?? ''),
                "Birth Certificate"     => url($guard->userDocuments->birth_certificate ?? ''),
            ];
        })->toArray();

        $headers = [
            "first_name","middle_name","surname","trn","nis","psra","date_of_joining","date_of_birth",
            "employer_company_name","current_rate","location_code","location_name","client_code","client_name","guard_type","employed_as","date_of_separation",
            "apartment_no","building_name","street_name","parish","city","postal_code","email","phone_number",
            "bank_name","bank_branch_address","account_number","account_type","routing_number","kin_surname","kin_first_name","kin_middle_name","kin_apartment_no",
            "kin_building_Name","kin_street_name","kin_parish","kin_city","kin_postal_code","kin_email","kin_phone_number",
            "trn_document","nis_document","psra_document","birth_certificate",
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

        // Return CSV as a response with appropriate headers
        return response()->stream($callback, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="guards.csv"',
        ]);
    }

    public function importSecurityGuard(Request $request)
    {
        $import = new SecurityGuardImport;
        Excel::import($import, $request->file('file'));

        $importedData = $import->getErrors();

        session()->put('imported_data', $importedData);

        session()->flash('success', 'Security Guard imported successfully.');
        $downloadUrl = route('security-guard.download');

        return redirect()->route('security-guards.index')->with('downloadUrl', $downloadUrl); 
    }

    public function exportResultCsv()
    {
        $importedData = session()->get('imported_data', []);
        $export = new GuardImportExport($importedData);
        return Excel::download($export, 'guard_import_results.csv');
    }

    public function downloadPDF()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail','userDocuments'])->latest()->get();

        $pdf = PDF::loadView('admin.security-guards.pdf.security-guard-pdf', compact('securityGuards'));
        
        return $pdf->download('security_guard_list.pdf');
    }
}