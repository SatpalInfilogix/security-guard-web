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
use App\Models\Leave;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SecurityGuardImport;
use App\Exports\GuardImportExport;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SecurityGuardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!Gate::allows('view security guards')) {
            abort(403);
        }
        $searchName = $request->input('search_name');
        $searchEmail = $request->input('search_email');
        $searchPhone = $request->input('search_phone');
        $searchEmpCode = $request->input('search_emp_code');
        $status = $request->input('status');
        $page = $request->input('page', 1);
        $userRole = Role::where('id', 3)->first();

        $query = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });

        $securityGuards = $query->with('userDocuments')->latest()->get();

        return view('admin.security-guards.index', compact(
            'securityGuards',
            'searchName',
            'searchEmail',
            'searchPhone',
            'searchEmpCode',
            'status',
            'page'
        ));
    }

    public function getSecurityGuard(Request $request)
    {
        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::with('userDocuments')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });

        if ($request->has('search_emp_code') && !empty($request->search_emp_code)) {
            $securityGuards->where('id', $request->search_emp_code);
        }
        if ($request->has('search_name') && !empty($request->search_name)) {
            $securityGuards->where(function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->search_name . '%')
                    ->orWhere('middle_name', 'like', '%' . $request->search_name . '%')
                    ->orWhere('surname', 'like', '%' . $request->search_name . '%');
            });
        }

        if ($request->has('search_email') && !empty($request->search_email)) {
            $securityGuards->where('email', 'like', '%' . $request->search_email . '%');
        }

        if ($request->has('search_phone') && !empty($request->search_phone)) {
            $securityGuards->where('phone_number', 'like', '%' . $request->search_phone . '%');
        }

        if ($request->has('status') && !empty($request->status)) {
            $securityGuards->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $securityGuards->where(function ($query) use ($searchValue) {
                $query->where('user_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('first_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('middle_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('surname', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('phone_number', 'like', '%' . $searchValue . '%');
            });
        }

        $filteredRecords = $securityGuards->count();
        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $securityGuards = $securityGuards->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
        $currentYear = now()->year;
        foreach ($securityGuards as $guard) {
            $approvedLeaves = Leave::where('guard_id', $guard->id)->where('status', 'Approved')->whereYear('date', $currentYear)->get()
                ->sum(function ($leave) {
                    return ($leave->type == 'Half Day') ? 0.5 : 1;
                });
            $guard['pendingLeaveBalance'] =  max(0, $paidLeaveBalanceLimit - $approvedLeaves);

            $VacationpaidLeaveBalanceLimit = (int) setting('vacation_leaves') ?: 10;
            $approvedVacationLeaves = Leave::where('guard_id', $guard->id)
                ->where('status', 'Approved')
                ->where('leave_type', 'Vacation Leave')
                ->whereYear('date', $currentYear)
                ->get()
                ->sum(function ($leave) {
                    return ($leave->type == 'Half Day') ? 0.5 : 1;
                });

            $guard['vacationLeaveBalance'] = max(0, $VacationpaidLeaveBalanceLimit - $approvedVacationLeaves);
        }

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => User::whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })->count(),
            'recordsFiltered' => $filteredRecords,
            'data' => $securityGuards,
        ];

        return response()->json($data);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Gate::allows('create security guards')) {
            abort(403);
        }

        $rateMasters = RateMaster::latest()->get();

        return view('admin.security-guards.create', compact('rateMasters'));
    }

    /**
     * Store a newly created resource in storage.
     */

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create security guards')) {
            abort(403);
        }

        $validationRules = [
            'first_name'    => 'required',
            'email'         => 'nullable|email|unique:users,email',
            'phone_number'  => 'required|numeric|unique:users,phone_number',
            'password'      => 'required',
            'recipient_id'  => 'nullable|string|max:15',
            'trn'           => 'nullable|unique:guard_additional_information,trn',
            'nis'           => 'nullable|unique:guard_additional_information,nis',
            'psra'          => 'nullable|unique:guard_additional_information,psra',
            'account_number' => 'nullable|unique:users_bank_details,account_no',
            'date_of_birth' => [
                'required',
                'date',
                'date_format:d-m-Y',
                function ($attribute, $value, $fail) {
                    $dateOfJoining = request()->input('date_of_joining');
                    if ($dateOfJoining && !empty($dateOfJoining)) {
                        $dob = \Carbon\Carbon::createFromFormat('d-m-Y', $value);
                        $joiningDate = \Carbon\Carbon::createFromFormat('d-m-Y', $dateOfJoining);

                        if ($dob >= $joiningDate) {
                            $fail('The date of birth must be before the date of joining.');
                        }
                    }
                },
            ],
            'date_of_joining' => 'nullable|date|date_format:d-m-Y',
            'guard_type_id' => 'required',
            'guard_employee_as_id' => 'required'
        ];

        if ($request->user_status === 'Active') {
            $validationRules['trn_doc'] = 'required';
            $validationRules['nis_doc'] = 'required';
        }

        $request->validate($validationRules);

        DB::beginTransaction();
        try {
            $user = User::create([
                'surname'      => $request->surname,
                'first_name'   => $request->first_name,
                'middle_name'  => $request->middle_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_number,
                'status'       => $request->user_status ?? 'Inactive',
                'is_statutory' => $request->is_statutory,
                'password'     => Hash::make($request->password),
            ])->assignRole('Security Guard');

            if ($user) {
                GuardAdditionalInformation::create([
                    'user_id'               => $user->id,
                    'trn'                   => $request->trn,
                    'nis'                   => $request->nis,
                    'psra'                  => $request->psra,
                    'date_of_joining'       => $this->parseDate($request->date_of_joining),
                    'date_of_birth'         => $this->parseDate($request->date_of_birth),
                    /* 'employer_company_name' => $request->employer_company_name,
                    'guards_current_rate'   => $request->current_rate,
                    'location_code'         => $request->location_code,
                    'location_name'         => $request->location_name,
                    'client_code'           => $request->client_code,
                    'client_name'           => $request->client_name, */
                    'guard_type_id'         => $request->guard_type_id,
                    'guard_employee_as_id'  => $request->guard_employee_as_id,
                    'date_of_seperation'    => $this->parseDate($request->date_of_seperation)
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
                    'recipient_id'          => $request->recipient_id,
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
                    'psra'      => uploadFile($request->file('psra_doc'), 'uploads/user-documents/psra/'),
                    'birth_certificate' => uploadFile($request->file('birth_certificate'), 'uploads/user-documents/birth_certificate/'),
                ]);
            }

            DB::commit();
            return redirect()->route('security-guards.index')->with('success', 'Security Guard created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('security-guards.index')->with('error', 'An error occurred while creating the security guard. Please try again.');
        }
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
        if (!Gate::allows('edit security guards')) {
            abort(403);
        }

        $rateMasters = RateMaster::latest()->get();
        $user = User::with(['guardAdditionalInformation', 'contactDetail', 'usersBankDetail', 'usersKinDetail', 'userDocuments'])->where('id', $id)->first();

        return view('admin.security-guards.edit', compact('user', 'rateMasters'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!Gate::allows('edit security guards')) {
            abort(403);
        }

        $guardInfo = GuardAdditionalInformation::where('user_id', $id)->first();
        $usersBankDetail = UsersBankDetail::where('user_id', $id)->first();
        $usersDocuments = usersDocuments::where('user_id', $id)->first();

        $validationRules = [
            'first_name'    => 'required',
            'email'         => 'nullable|email|unique:users,email,' . $id,
            'phone_number'  => 'required|numeric|unique:users,phone_number,' . $id,
            'password'      => 'nullable',
            'recipient_id'  => 'nullable|string|max:15',
            'trn'           => 'nullable|unique:guard_additional_information,trn,' . optional($guardInfo)->id,
            'nis'           => 'nullable|unique:guard_additional_information,nis,' . optional($guardInfo)->id,
            'psra'          => 'nullable|unique:guard_additional_information,psra,' . optional($guardInfo)->id,
            'account_no'    => 'nullable|unique:users_bank_details,account_no,' . optional($usersBankDetail)->id,
            'date_of_birth' => [
                'required',
                'date',
                'date_format:d-m-Y',
                function ($attribute, $value, $fail) {
                    $dateOfJoining = request()->input('date_of_joining');
                    if ($dateOfJoining && !empty($dateOfJoining)) {
                        $dob = \Carbon\Carbon::createFromFormat('d-m-Y', $value);
                        $joiningDate = \Carbon\Carbon::createFromFormat('d-m-Y', $dateOfJoining);

                        if ($dob >= $joiningDate) {
                            $fail('The date of birth must be before the date of joining.');
                        }
                    }
                },
            ],
            'date_of_joining' => 'nullable|date|date_format:d-m-Y',
            'guard_type_id' => 'required',
            'guard_employee_as_id' => 'required'
        ];

        if ($request->user_status === 'Active') {
            $validationRules['trn_doc'] = ($usersDocuments->trn ?? null || $request->hasFile('trn_doc')) ? 'nullable' : 'required';
            $validationRules['nis_doc'] = ($usersDocuments->nis ?? null || $request->hasFile('nis_doc')) ? 'nullable' : 'required';
        }

        $request->validate($validationRules);

        $user = User::findOrFail($id);
        $user->surname      = $request->surname;
        $user->first_name   = $request->first_name;
        $user->middle_name  = $request->middle_name;
        $user->email        = $request->email;
        $user->phone_number = $request->phone_number;
        $user->status       = $request->user_status ?? $user->status;
        $user->is_statutory = $request->is_statutory;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        GuardAdditionalInformation::updateOrCreate(
            ['user_id' => $id],
            [
                'trn'                   => $request->trn,
                'nis'                   => $request->nis,
                'psra'                  => $request->psra,
                'date_of_joining'       => $this->parseDate($request->date_of_joining),
                'date_of_birth'         => $this->parseDate($request->date_of_birth),
                /* 'employer_company_name' => $request->employer_company_name,
                'guards_current_rate'   => $request->current_rate,
                'location_code'         => $request->location_code,
                'location_name'         => $request->location_name,
                'client_code'           => $request->client_code,
                'client_name'           => $request->client_name, */
                'guard_type_id'         => $request->guard_type_id,
                'guard_employee_as_id'  => $request->guard_employee_as_id,
                'date_of_seperation'    => $this->parseDate($request->date_of_seperation),
            ]
        );

        ContactDetail::updateOrCreate(
            ['user_id' => $id],
            [
                'apartment_no'  => $request->apartment_no,
                'building_name' => $request->building_name,
                'street_name'   => $request->street_name,
                'parish'        => $request->parish,
                'city'          => $request->city,
                'postal_code'   => $request->postal_code,
            ]
        );

        UsersBankDetail::updateOrCreate(
            ['user_id' => $id],
            [
                'bank_name'             => $request->bank_name,
                'bank_branch_address'   => $request->branch,
                'account_no'            => $request->account_number,
                'account_type'          => $request->account_type,
                'routing_number'        => $request->routing_number,
                'recipient_id'          => $request->recipient_id,
            ]
        );

        UsersKinDetail::updateOrCreate(
            ['user_id' => $id],
            [
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
            ]
        );

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

        // $usersDocuments->update($documents);
        usersDocuments::updateOrCreate(
            ['user_id' => $id],
            $documents
        );
        return redirect()->route('security-guards.index')->with('success', 'Security Guard updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!Gate::allows('delete security guards')) {
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
        $userRole = Role::where('id', 3)->first();

        $guards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with(['guardAdditionalInformation', 'contactDetail', 'usersBankDetail', 'usersKinDetail', 'userDocuments'])->latest()->get();

        $guardArray = $guards->map(function ($guard) {
            return [
                "Guard Code"            => $guard->user_code,
                "First Name"            => $guard->first_name,
                "Middle Name"           => $guard->middle_name,
                "Surname"               => $guard->surname,
                // Additional Detail
                "Guard's TRN"           => isset($guard->guardAdditionalInformation->trn)
                    ? $this->trnFormat($guard->guardAdditionalInformation->trn)
                    : '',
                "NIS/NHT Number"        => $guard->guardAdditionalInformation->nis ?? '',
                "PSRA Registration No"  => $guard->guardAdditionalInformation->psra ?? '',
                "Guard's Date of Joining" => $guard->guardAdditionalInformation->date_of_joining ? Carbon::parse($guard->guardAdditionalInformation->date_of_joining)->format('d-m-Y') : '',
                "Date of Birth"         => $guard->guardAdditionalInformation->date_of_birth ? Carbon::parse($guard->guardAdditionalInformation->date_of_birth)->format('d-m-Y') : '',
                // "Employer Company Name" => $guard->guardAdditionalInformation->employer_company_name ?? '',
                // "Guard's Current Rate"  => $guard->guardAdditionalInformation->guards_current_rate ?? '',
                // "Location Code"         => $guard->guardAdditionalInformation->location_code ?? '',
                // "Location Name"         => $guard->guardAdditionalInformation->location_name ?? '',
                // "Client Code"           => $guard->guardAdditionalInformation->client_code ?? '',
                // "Client Name"           => $guard->guardAdditionalInformation->client_name ?? '',
                "Guard Type"            => $guard->guardAdditionalInformation->guard_type_id ?? '',
                "Guard Employed As"     => $guard->guardAdditionalInformation->guard_employee_as_id ?? '',
                "Date of Separation"    => $guard->guardAdditionalInformation->date_of_seperation ? Carbon::parse($guard->guardAdditionalInformation->date_of_seperation)->format('d-m-Y') : '',
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
                // "TRN Document"          => $guard->userDocuments->trn ? url($guard->userDocuments->trn) : '',
                // "NIS Document"          => $guard->userDocuments->nis ? url($guard->userDocuments->nis) : '',
                // "PSRA Document"         => $guard->userDocuments->psra ? url($guard->userDocuments->psra) : '',
                // "Birth Certificate"     => $guard->userDocuments->birth_certificate ? url($guard->userDocuments->birth_certificate) : '',

            ];
        })->toArray();

        $headers = [
            "Guard Code",
            "First Name",
            "Middle Name",
            "Surname",
            "TRN",
            "NIS",
            "PSRA",
            "Date Of Joining",
            "Date Of Birth",
            // "Employer Company Name","Current Rate","Location Code","Location Name","Client Code","Client Name",
            "Guard Type",
            "Guard Employed As",
            "Date Of Separation",
            "Apartment No",
            "Building Name",
            "Street Name",
            "Parish",
            "City",
            "Postal Code",
            "Email",
            "Phone Number",
            "Bank Name",
            "Bank Branch Address",
            "Account Number",
            "Account Type",
            "Routing Number",
            "Kin Surname",
            "Kin First Name",
            "Kin Middle Name",
            "Kin Apartment No",
            "Kin Building Name",
            "Kin Street Name",
            "Kin Parish",
            "Kin City",
            "Kin Postal Code",
            "Kin Email",
            "Kin Phone Number",
            // "Trn Document","Nis Document","Psra Document","Birth Certificate",
        ];

        array_unshift($guardArray, $headers);

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

    public function trnFormat($trn)
    {
        $new = str_replace('-', '', $trn);
        return rtrim(chunk_split($new, 3, '-'), '-');
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
        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with(['guardAdditionalInformation', 'contactDetail', 'usersBankDetail', 'usersKinDetail', 'userDocuments'])->latest()->get();

        $pdf = PDF::loadView('admin.security-guards.pdf.security-guard-pdf', compact('securityGuards'));

        return $pdf->download('security_guard_list.pdf');
    }
}
