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
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;
use App\Exports\EmployeeImportExport;
use App\Exports\EmployeesExport;
use App\Models\EmployeeLeave;
use App\Models\PublicHoliday;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view employee')) {
            abort(403);
        }
        $page = $request->input('page', 1);
        $searchEmpCode = $request->input('search_emp_code');
        $searchName = $request->input('search_name');
        $searchEmail = $request->input('search_email');
        $searchPhone = $request->input('search_phone');
        $status = $request->input('status');

        $userRole = Role::where('id', 9)->first();

        $query = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        });

        $employees = $query->with('userDocuments')->latest()->get();

        return view('admin.employees.index', compact(
            'employees',
            'page',
            'searchEmpCode',
            'searchName',
            'searchEmail',
            'searchPhone',
            'status'
        ));
    }

    public function getEmployee(Request $request)
    {
        $userRole = Role::where('id', 9)->first();

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

        $securityGuards = $securityGuards->orderBy('id', 'desc')->skip($start)->take($length)->get();

        $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
        $currentYear = now()->year;
        foreach ($securityGuards as $employee) {
            $approvedLeaves = EmployeeLeave::where('employee_id', $employee->id)->where('status', 'Approved')->whereYear('date', $currentYear)->get()
                ->sum(function ($leave) {
                    return ($leave->type == 'Half Day') ? 0.5 : 1;
                });
            $employee['pendingLeaveBalance'] =  max(0, $paidLeaveBalanceLimit - $approvedLeaves);

            // $VacationpaidLeaveBalanceLimit = (int) setting('vacation_leaves') ?: 10;
            // $approvedVacationLeaves = EmployeeLeave::where('employee_id', $employee->id)
            //     ->where('status', 'Approved')
            //     ->where('leave_type', 'Vacation Leave')
            //     ->whereYear('date', $currentYear)
            //     ->get()
            //     ->sum(function ($leave) {
            //         return ($leave->type == 'Half Day') ? 0.5 : 1;
            //     });

            // $employee['vacationLeaveBalance'] = max(0, $VacationpaidLeaveBalanceLimit - $approvedVacationLeaves);

            $yearlyVacationLimit = $this->getYearlyVacationLimitForListing($employee, $currentYear);
            $proratedVacationLeave = $this->calculateProratedVacationLeaveForListing($employee, $currentYear);
            $totalVacationLimit = $yearlyVacationLimit + $proratedVacationLeave;

            $usedVacationLeaves = EmployeeLeave::where('employee_id', $employee->id)
                ->where('status', 'Approved')
                ->where('leave_type', 'Vacation Leave')
                ->whereYear('date', $currentYear)
                ->get()
                ->sum(function ($leave) {
                    $leaveDate = Carbon::parse($leave->date);
                    if ($leaveDate->isWeekend() || $this->isPublicHoliday($leaveDate)) {
                        return 0;
                    }
                    return ($leave->type == 'Half Day') ? 0.5 : 1;
                });

            $employee['vacationLeaveBalance'] = max(0, $totalVacationLimit - $usedVacationLeaves);
            $employee['yearlyVacationLimit'] = $yearlyVacationLimit;
            $employee['proratedLeave'] = $proratedVacationLeave;
            $employee['totalVacationLimit'] = $totalVacationLimit;
            $employee['usedVacationLeaves'] = $usedVacationLeaves;
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

    protected function getYearlyVacationLimitForListing($employee, $year)
    {
        $isManagerOrAbove = $this->isManagerOrAboveForListing($employee, $year);

        if (!$isManagerOrAbove) {
            return 10;
        }

        $joiningDate = Carbon::parse($employee->guardAdditionalInformation->date_of_joining);
        $yearsOfService = $joiningDate->diffInYears(Carbon::create($year, 12, 31));

        if ($yearsOfService <= 10) {
            return 15;
        } else {
            return 20;
        }
    }

    protected function isManagerOrAboveForListing($employee, $year)
    {
        $managerRoles = Role::where('is_manager_level', 1)->pluck('id');

        $hasManagerRole = $employee->roles()->whereIn('id', $managerRoles)->exists();

        if ($hasManagerRole) {
            return true;
        }

        if ($employee->current_role_id) {
            $currentRole = Role::find($employee->current_role_id);
            if ($currentRole && $currentRole->is_manager_level == 1) {
                return true;
            }
        }

        $checkDate = Carbon::create($year, 12, 31);

        if ($employee->promotion_date && $employee->promotion_date <= $checkDate) {
            if ($employee->current_role_id) {
                $currentRole = Role::find($employee->current_role_id);
                return $currentRole && $currentRole->is_manager_level == 1;
            }
        }

        return false;
    }

    protected function calculateProratedVacationLeaveForListing($employee, $year)
    {
        if (!$employee->promotion_date || $employee->promotion_date->year != $year) {
            return 0;
        }

        $promotionDate = Carbon::parse($employee->promotion_date);
        $joiningDate = Carbon::parse($employee->guardAdditionalInformation->date_of_joining);

        $isManagerAfter = $this->isManagerOrAboveForListing($employee, $year);

        if ($isManagerAfter) {
            $startOfYear = Carbon::create($year, 1, 1);
            $endOfYear = Carbon::create($year, 12, 31);

            $nonManagerDays = $startOfYear->diffInDays($promotionDate->copy()->subDay());
            $nonManagerLeave = (10 / 365) * $nonManagerDays;

            $managerDays = $promotionDate->diffInDays($endOfYear) + 1;
            $yearsAtPromotion = $joiningDate->diffInYears($promotionDate);
            $managerYearlyLimit = $yearsAtPromotion <= 10 ? 15 : 20;
            $managerLeave = ($managerYearlyLimit / 365) * $managerDays;

            $totalProratedLeave = $nonManagerLeave + $managerLeave;

            return $totalProratedLeave;
        }

        return 0;
    }

    protected function isPublicHoliday($date)
    {
        return PublicHoliday::whereDate('date', $date)->exists();
    }
    

    public function create()
    {
        if (!Gate::allows('create employee')) {
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
        if (!Gate::allows('create employee')) {
            abort(403);
        }

        $existingUser = User::where('phone_number', $request->phone_number)
            // ->where('email', $request->email)
            ->first();

        $validationRules = [
            'first_name'    => 'required',
            'email'         => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore(optional($existingUser)->id),
            ],
            'phone_number'  => [
                'required',
                'numeric',
                Rule::unique('users', 'phone_number')->ignore(optional($existingUser)->id),
            ],
            'password'      => $existingUser ? 'nullable' : 'required',
            'recipient_id'  => 'nullable|string|max:15',
            'trn'           => 'nullable|unique:guard_additional_information,trn',
            'nis'           => 'nullable|unique:guard_additional_information,nis',
            'account_number' => 'nullable|unique:users_bank_details,account_no',
            'date_of_birth' => [
                'required',
                'date',
                'date_format:d-m-Y',
                function ($attribute, $value, $fail) use ($request) {
                    $dateOfJoining = $request->input('date_of_joining');
                    if ($dateOfJoining && !empty($dateOfJoining)) {
                        $dob = Carbon::createFromFormat('d-m-Y', $value);
                        $joiningDate = Carbon::createFromFormat('d-m-Y', $dateOfJoining);
                        if ($dob >= $joiningDate) {
                            $fail('The date of birth must be before the date of joining.');
                        }
                    }
                },
            ],
            'date_of_joining' => 'required|date|date_format:d-m-Y',
        ];

        if (!$existingUser) {
            $validationRules['email'][] = 'unique:users,email';
            $validationRules['phone_number'][] = 'unique:users,phone_number';
        }

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

            if ($existingUser) {
                $existingUser->update([
                    'user_code'    => $existingUser->user_code ?? ($user_code ?? NULL),
                    'surname'      => $request->surname,
                    'first_name'   => $request->first_name,
                    'middle_name'  => $request->middle_name,
                    'email'        => $request->email,
                    'phone_number' => $request->phone_number,
                    'status'       => $request->user_status ?? 'Inactive',
                    'is_statutory' => $request->is_statutory,
                    'password'     => $request->password ? Hash::make($request->password) : $existingUser->password,
                ]);

                $existingUser->assignRole('Employee');

                $user = $existingUser;
            } else {
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
                ]);

                $user->assignRole('Employee');
            }

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
                    'personal_email' => $request->personal_email,
                    'work_phone_number' => $request->work_phone_number,
                    'personal_phone_number' => $request->personal_phone_number
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
        if (!Gate::allows('edit employee')) {
            abort(403);
        }

        $user = User::with(['guardAdditionalInformation', 'contactDetail', 'usersBankDetail', 'usersKinDetail', 'userDocuments'])->where('id', $id)->first();

        return view('admin.employees.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!Gate::allows('edit employee')) {
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
        $user->status       = $request->user_status ?? $user->status;
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
            ]
        );

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
                'personal_phone_number' => $request->personal_phone_number
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
        if (!Gate::allows('delete employee')) {
            abort(403);
        }

        $user = User::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully.'
        ]);
    }

    public function employeeStatus(Request $request)
    {
        $userDocs = UsersDocuments::where('user_id', $request->user_id)->first();
        if ($userDocs) {
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
        $lastEmployee = User::where('user_code', 'LIKE', 'E%')->orderBy('user_code', 'desc')->first();

        if ($lastEmployee) {
            $lastCodeNumber = (int) substr($lastEmployee->user_code, 1);
            $newCodeNumber = $lastCodeNumber + 1;
            return 'E' . str_pad($newCodeNumber, 6, '0', STR_PAD_LEFT);
        } else {
            return 'E' . str_pad(1, 6, '0', STR_PAD_LEFT);
        }
    }

    public function downloadPDF(Request $request)
    {
        $userRole = Role::where('id', 9)->first();
        $query = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with(['guardAdditionalInformation', 'contactDetail', 'usersBankDetail', 'usersKinDetail', 'userDocuments'])->latest();

        if ($request->has('search_emp_code') && !empty($request->search_emp_code)) {
            $query->where('id', $request->search_emp_code);
        }

        if ($request->has('search_name') && !empty($request->search_name)) {
            $query->where('first_name', 'like', '%' . $request->search_name . '%');
        }

        if ($request->has('search_email') && !empty($request->search_email)) {
            $query->where('email', 'like', '%' . $request->search_email . '%');
        }

        if ($request->has('search_phone') && !empty($request->search_phone)) {
            $query->where('phone_number', 'like', '%' . $request->search_phone . '%');
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $securityGuards = $query->get();

        $pdf = PDF::loadView('admin.employees.pdf.employee-pdf', compact('securityGuards'));
        return $pdf->download('employees_list.pdf');
    }

    public function exportEmployees()
    {
        return Excel::download(new EmployeesExport, 'guards.xlsx');

        /*     $userRole = Role::where('id', 9)->first();

         $employees = User::whereHas('roles', function ($query) use ($userRole) {
             $query->where('role_id', $userRole->id);
         })->with(['guardAdditionalInformation', 'contactDetail', 'usersBankDetail', 'usersKinDetail', 'userDocuments'])->latest()->get();

         $employeeArray = $employees->map(function ($employee) {
             return [
                 "First Name"            => $employee->first_name,
                 "Middle Name"           => $employee->middle_name,
                 "Surname"               => $employee->surname,
                 "Phone Number"          => $employee->phone_number ?? '',
                 // Additional Detail
                 "Guard's TRN"           => $this->trnFormat($employee->guardAdditionalInformation->trn) ?? '',
                 "NIS/NHT Number"        => $employee->guardAdditionalInformation->nis ?? '',
                 "Guard's Date of Joining" => $employee->guardAdditionalInformation->date_of_joining ? Carbon::parse($employee->guardAdditionalInformation->date_of_joining)->format('d-m-Y') : '',
                 "Date of Birth"         => $employee->guardAdditionalInformation->date_of_birth ? Carbon::parse($employee->guardAdditionalInformation->date_of_birth)->format('d-m-Y') : '',
                 "Position"              => $employee->guardAdditionalInformation->position ?? '',
                 "Department"            => $employee->guardAdditionalInformation->department ?? '',
                 "Location"              => $employee->guardAdditionalInformation->location ?? '',
                 "Date of Separation"    => $employee->guardAdditionalInformation->date_of_seperation ? Carbon::parse($employee->guardAdditionalInformation->date_of_seperation)->format('d-m-Y') : '',
                 // Contact details
                 "Apartment No"          => $employee->contactDetail->apartment_no ?? '',
                 "Building Name"         => $employee->contactDetail->building_name ?? '',
                 "Street Name"           => $employee->contactDetail->street_name ?? '',
                 "Parish"                => $employee->contactDetail->parish ?? '',
                 "City"                  => $employee->contactDetail->city ?? '',
                 "Postal Code"           => $employee->contactDetail->postal_code ?? '',
                 "Email"                 => $employee->email ?? '',
                 'Personal Email'        => $employee->contactDetail->personal_email ?? '',
                 'Work Phone Number'     => $employee->contactDetail->work_phone_number ?? '',
                 'Personal Phone Number'     => $employee->contactDetail->personal_phone_number ?? '',
                 // Bank details
                 "Bank Name"             => $employee->usersBankDetail->bank_name ?? '',
                 "Bank Branch Address"   => $employee->usersBankDetail->bank_branch_address ?? '',
                 "Account Number"        => $employee->usersBankDetail->account_no ?? '',
                 "Account Type"          => $employee->usersBankDetail->account_type ?? '',
                 "Routing Number"        => $employee->usersBankDetail->routing_number ?? '',
                 // Next of Kin details
                 "Kin Surname"           => $employee->usersKinDetail->surname ?? '',
                 "Kin First Name"        => $employee->usersKinDetail->first_name ?? '',
                 "Kin Middle Name"       => $employee->usersKinDetail->middle_name ?? '',
                 "Kin Apartment No"      => $employee->usersKinDetail->apartment_no ?? '',
                 "Kin Building Name"     => $employee->usersKinDetail->building_name ?? '',
                 "Kin Street Name"       => $employee->usersKinDetail->street_name ?? '',
                 "Kin Parish"            => $employee->usersKinDetail->parish ?? '',
                 "Kin City"              => $employee->usersKinDetail->city ?? '',
                 "Kin Postal Code"       => $employee->usersKinDetail->postal_code ?? '',
                 "Kin Email"             => $employee->usersKinDetail->email ?? '',
                 "Kin Phone Number"      => $employee->usersKinDetail->phone_number ?? '',
             ];
         })->toArray();

         $headers = [
             "First Name",
             "Middle Name",
             "Surname",
             "Phone Number",
             "TRN",
             "NIS",
             "Date Of Joining",
             "Date Of Birth",
             "Position",
             "Department",
             "Location",
             "Date Of Separation",
             "Apartment No",
             "Building Name",
             "Street Name",
             "Parish",
             "City",
             "Postal Code",
             "Email",
             "Personal Email",
             "Work Phone Number",
             "Personal Phone Number",
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
         ];

         array_unshift($employeeArray, $headers);

         $callback = function () use ($employeeArray) {
             $file = fopen('php://output', 'w');
             foreach ($employeeArray as $row) {
                 fputcsv($file, $row);
             }
             fclose($file);
         };

         // Return CSV as a response with appropriate headers
         return response()->stream($callback, Response::HTTP_OK, [
             'Content-Type' => 'text/csv',
             'Content-Disposition' => 'attachment; filename="guards.csv"',
         ]);*/
    }

    public function importEmployee(Request $request)
    {
        $import = new EmployeeImport;
        Excel::import($import, $request->file('file'));

        $importedData = $import->getErrors();
        session()->put('imported_data', $importedData);
        session()->flash('success', 'Employee imported successfully.');
        $downloadUrl = route('employees.download');

        return redirect()->route('employees.index')->with('downloadUrl', $downloadUrl);
    }

    public function exportResultCsv()
    {
        $importedData = session()->get('imported_data', []);
        $export = new EmployeeImportExport($importedData);
        return Excel::download($export, 'employee_import_results.csv');
    }

    public function trnFormat($trn)
    {
        $new =  str_replace('-', '', $trn);
        $formatted = chunk_split($new, 3, '-');
        return $formatted = rtrim($formatted, '-');
    }
}
