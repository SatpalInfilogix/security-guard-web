<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\GuardAdditionalInformation;
use App\Models\ContactDetail;
use App\Models\UsersBankDetail;
use App\Models\UsersKinDetail;
use App\Models\UsersDocuments;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view employee')) {
            abort(403);
        }

        $userRole = Role::where('name', 'Employee')->first();

        $query = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });

        $employees = $query->with('userDocuments')->latest()->get();

        return view('admin.employees.index', compact('employees'));
    }

    public function getEmployee(Request $request)
    {
        $userRole = Role::where('name', 'Employee')->first();
    
        $securityGuards = User::with('userDocuments')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });

        if ($request->has('search_emp_code') && !empty($request->search_emp_code)) {
            $securityGuards->where('id', $request->search_emp_code);
        }
    
        if ($request->has('search_name') && !empty($request->search_name)) {
            $securityGuards->where('first_name', 'like', '%' . $request->search_name . '%');
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
            $securityGuards->where(function($query) use ($searchValue) {
                $query->where('user_code', 'like', '%' . $searchValue . '%')
                      ->orwhere('first_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('last_name', 'like', '%' . $searchValue . '%')
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

    public function create()
    {
        if(!Gate::allows('create employee')) {
            abort(403);
        }

        return view('admin.employees.create');
    }

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
        if(!Gate::allows('create employee')) {
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
            'account_number'=> 'nullable|unique:users_bank_details,account_no',
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
            'date_of_joining' => 'required|date|date_format:d-m-Y',
        ];

        if ($request->user_status === 'Active') {
            $validationRules['trn_doc'] = 'required';
            $validationRules['nis_doc'] = 'required';
        }

        $request->validate($validationRules);

        DB::beginTransaction();
        try {
            if ($request->user_status == 'Active') {
                $user_code = $this->generateEmployeeCode();
            }

            $user = User::create([
                'user_code'    => $user_code ?? NULL,
                'surname'      => $request->surname,
                'first_name'   => $request->first_name,
                'middle_name'  => $request->middle_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_number,
                'status'       => $request->user_status ?? 'Inactive',
                'is_statutory' => $request->is_statutory,
                'password'     => Hash::make($request->password),
            ])->assignRole('Employee');

           

            if ($user) {
                GuardAdditionalInformation::create([
                    'user_id'               => $user->id,
                    'trn'                   => $request->trn,
                    'nis'                   => $request->nis,
                    'date_of_joining'       => $this->parseDate($request->date_of_joining),
                    'date_of_birth'         => $this->parseDate($request->date_of_birth),
                    'position'              => $request->position,
                    'department'            => $request->department,
                    'location'              => $request->location,
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
                    'personal_email'=> $request->personal_email,
                    'work_phone_number' => $request->work_phone_number,
                    'personal_phone_number' => $request->personal_phone
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
                    'birth_certificate' => uploadFile($request->file('birth_certificate'), 'uploads/user-documents/birth_certificate/'),
                ]);
            }

            DB::commit();
            return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('employees.index')->with('error', 'An error occurred while creating the employee. Please try again.');
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
        if(!Gate::allows('edit employee')) {
            abort(403);
        }

        $user = User::with(['guardAdditionalInformation','contactDetail','usersBankDetail','usersKinDetail', 'userDocuments'])->where('id', $id)->first();

        return view('admin.employees.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if(!Gate::allows('edit employee')) {
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
            'trn'           => 'nullable|unique:guard_additional_information,trn,'. optional($guardInfo)->id,
            'nis'           => 'nullable|unique:guard_additional_information,nis,'. optional($guardInfo)->id,
            'psra'          => 'nullable|unique:guard_additional_information,psra,'. optional($guardInfo)->id,
            'account_no'    => 'nullable|unique:users_bank_details,account_no,'. optional($usersBankDetail)->id,
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
            'date_of_joining' => 'required|date|date_format:d-m-Y',
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
        $user->status       = $request->user_status ?? 'Inactive';
        $user->is_statutory = $request->is_statutory;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->user_status == 'Active' && is_null($user->user_code)) {
            $user->user_code = $this->generateEmployeeCode();
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
                'position'              => $request->position,
                'department'            => $request->department,
                'location'              => $request->location,
                'date_of_seperation'    => $this->parseDate($request->date_of_seperation),
            ]);

            ContactDetail::updateOrCreate(
                ['user_id' => $id],
                [
                'apartment_no'          => $request->apartment_no,
                'building_name'         => $request->building_name,
                'street_name'           => $request->street_name,
                'parish'                => $request->parish,
                'city'                  => $request->city,
                'postal_code'           => $request->postal_code,
                'personal_email'        => $request->personal_email,
                'work_phone_number'     => $request->work_phone_number,
                'personal_phone_number' => $request->personal_phone
            ]);

            UsersBankDetail::updateOrCreate(
            ['user_id' => $id],
            [
            'bank_name'             => $request->bank_name,
            'bank_branch_address'   => $request->branch,
            'account_no'            => $request->account_number,
            'account_type'          => $request->account_type,
            'routing_number'        => $request->routing_number,
            'recipient_id'          => $request->recipient_id,
        ]);

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
        ]);

        $documents = [];
        if ($request->hasFile('trn_doc')) {
            $documents['trn'] = uploadFile($request->file('trn_doc'), 'uploads/user-documents/trn/');
        }
        if ($request->hasFile('nis_doc')) {
            $documents['nis'] = uploadFile($request->file('nis_doc'), 'uploads/user-documents/nis/');
        }
        if ($request->hasFile('birth_certificate')) {
            $documents['birth_certificate'] = uploadFile($request->file('birth_certificate'), 'uploads/user-documents/birth_certificate/');
        }

        // $usersDocuments->update($documents);
        usersDocuments::updateOrCreate(
            ['user_id' => $id],
            $documents 
        );
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(string $id)
    {
        if(!Gate::allows('delete employee')) {
            abort(403);
        }

        $user = User::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully.'
        ]);
    }

    public function employeeStatus(Request $request){
        $userDocs = UsersDocuments::where('user_id', $request->user_id)->first();
        if($userDocs){
            if (
                empty($userDocs->trn) || 
                empty($userDocs->nis)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'User documents are missing or incomplete. Please upload all necessary documents.'
                ]);
            }
        }

        $user = User::find($request->user_id);
        $user->status = $request->status;
        if ($request->status == 'Active' && is_null($user->user_code)) {
            $user->user_code = $this->generateEmployeeCode();
        }
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }

    public function generateEmployeeCode()
    {
        $lastEmployee = User::where('user_code', 'LIKE', 'E%')
                            ->orderBy('user_code', 'desc')
                            ->first();

        if ($lastEmployee) {
            $lastCodeNumber = (int) substr($lastEmployee->user_code, 1);
            $newCodeNumber = $lastCodeNumber + 1;
            return 'E' . str_pad($newCodeNumber, 6, '0', STR_PAD_LEFT);
        } else {
            return 'E' . str_pad(1, 6, '0', STR_PAD_LEFT);
        }
    }


}
