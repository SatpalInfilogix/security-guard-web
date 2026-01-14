<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UsersDocuments;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view user')) {
            abort(403);
        }

        $excludedRoles = [3, 9, 14];

        $users = User::whereHas('roles', function ($query) use ($excludedRoles) {
            $query->whereNotIn('role_id', $excludedRoles);
        })->latest()->get();
        logActivity('View Users', 'Viewed users list.');
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        if (!Gate::allows('create user')) {
            abort(403);
        }
        $roles = Role::latest()->get();
        logActivity('Create User', 'Opened create user form.');

        return view('admin.users.create', compact('roles'));
    }


    // public function store(Request $request)
    // {
    //     if (!Gate::allows('create user')) {
    //         abort(403);
    //     }

    //     $existingUser = User::where('email', $request->email)
    //         ->orWhere('phone_number', $request->phone_no)
    //         ->first();

    //     $userId = optional($existingUser)->id;

    //     $request->validate([
    //         'first_name' => 'required',
    //         'last_name'  => 'required',
    //         'email'      => [
    //             'required',
    //             'email',
    //             Rule::unique('users', 'email')->ignore($userId),
    //         ],
    //         'phone_no'   => [
    //             'required',
    //             Rule::unique('users', 'phone_number')->ignore($userId),
    //         ],
    //         'password'   => $existingUser ? 'nullable' : 'required',
    //         'role'       => 'required|string|exists:roles,name',
    //     ]);

    //     if ($existingUser) {
    //         $existingUser->update([
    //             'first_name'   => $request->first_name,
    //             'last_name'    => $request->last_name,
    //             'email'        => $request->email,
    //             'phone_number' => $request->phone_no,
    //             'password'     => $request->password ? Hash::make($request->password) : $existingUser->password,
    //         ]);

    //         if (!$existingUser->hasRole($request->role) || $existingUser->hasRole('Employee')) {
    //             $existingUser->assignRole($request->role);
    //             logActivity('Update User', 'Updated existing user: ' . $existingUser->id);
    //         }
    //     } else {
    //         $newUser = User::create([
    //             'first_name'   => $request->first_name,
    //             'last_name'    => $request->last_name,
    //             'email'        => $request->email,
    //             'phone_number' => $request->phone_no,
    //             'password'     => Hash::make($request->password),
    //         ]);

    //         $newUser->assignRole($request->role);
    //         logActivity('Create User', 'Created new user: ' . $newUser->id);
    //     }

    //     return redirect()->route('users.index')->with('success', 'User created or updated successfully.');
    // }

     public function store(Request $request)
    {
        if (!Gate::allows('create user')) {
            abort(403);
        }

        $existingUser = User::where('email', $request->email)
            ->orWhere('phone_number', $request->phone_no)
            ->first();

        $userId = optional($existingUser)->id;
        
        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone_no'   => [
                'required',
                Rule::unique('users', 'phone_number')->ignore($userId),
            ],
            'password'   => $existingUser ? 'nullable' : 'required',
            'role'       => 'required|string|exists:roles,name',
            'promotion_date' => 'nullable|date',
            'promotion_remarks' => 'nullable|string',
        ]);

        if ($existingUser) {
            $currentRole = $existingUser->roles->first();
            $newRole = Role::where('name', $request->role)->first();

            $userData = [
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_no,
                'password'     => $request->password ? Hash::make($request->password) : $existingUser->password,
            ];

            // Track promotion if role is changing
            if ($currentRole && $newRole && $currentRole->id != $newRole->id) {
                $userData['previous_role_id'] = $currentRole->id;
                $userData['current_role_id'] = $newRole->id;
                $userData['promotion_date'] = $request->promotion_date ? Carbon::parse($request->promotion_date) : Carbon::now();
                $userData['promotion_remarks'] = $request->promotion_remarks ?: 'Role updated via user update';
            }

            $existingUser->update($userData);

            if (!$existingUser->hasRole($request->role) || $existingUser->hasRole('Employee')) {
                $existingUser->assignRole($request->role);
                logActivity('Update User', 'Updated existing user: ' . $existingUser->id);
            }
        } else {
            $newRole = Role::where('name', $request->role)->first();

            $userData = [
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_no,
                'password'     => Hash::make($request->password),
                'current_role_id' => $newRole->id,
            ];

            if ($request->promotion_date) {
                $userData['promotion_date'] = Carbon::parse($request->promotion_date);
                $userData['promotion_remarks'] = $request->promotion_remarks ?: 'Initial role assignment';
            }

            $newUser = User::create($userData);
            $newUser->assignRole($request->role);
            logActivity('Create User', 'Created new user: ' . $newUser->id);
        }

        return redirect()->route('users.index')->with('success', 'User created or updated successfully.');
    }

    public function updateStatus(Request $request)
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
        $user->save();
        logActivity('Update User Status', 'Changed status for user ID: ' . $user->id . ' to ' . $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        if (!Gate::allows('edit user')) {
            abort(403);
        }
        $user = User::where('id', $id)->first();
        $roles = Role::latest()->get();
        logActivity('Edit User', 'Opened edit form for user: ' . $id);

        return view('admin.users.edit', compact('user', 'roles'));
    }

    // public function update(Request $request, string $id)
    // {
    //     if (!Gate::allows('edit user')) {
    //         abort(403);
    //     }

    //     $request->validate([
    //         'first_name' => 'required',
    //         'last_name'  => 'required',
    //         'phone_no'   => 'required|unique:users,phone_number,' . $id,
    //     ]);

    //     $user = User::findOrFail($id);
    //     $oldValues = $user->toArray();

    //     $user->first_name   = $request->first_name;
    //     $user->last_name    = $request->last_name;
    //     $user->phone_number = $request->phone_no;

    //     if ($request->filled('password')) {
    //         $user->password = Hash::make($request->password);
    //     }

    //     $user->save();

    //     if (
    //         Auth::user()->hasAnyRole(['Super Admin', 'Admin']) &&
    //         Auth::id() != $user->id &&
    //         $request->filled('role')
    //     ) {
    //         $role = Role::find($request->role);

    //         if ($role && $role->name !== 'Super Admin') {
    //             $user->syncRoles([$role->name]);
    //         } else {
    //             return redirect()->back()->with('error', 'Invalid role assignment.');
    //         }
    //     }
    //     $newValues = $user->toArray();

    //     logActivity(
    //         'Update User',
    //         'Updated user ID: ' . $id . ' | Changes: ' . json_encode(array_diff_assoc($newValues, $oldValues))
    //     );
    //     return redirect()->route('users.index')->with('success', 'User updated successfully.');
    // }

       public function update(Request $request, string $id)
    {
        if (!Gate::allows('edit user')) {
            abort(403);
        }

        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'phone_no'   => 'required|unique:users,phone_number,' . $id,
            'promotion_date' => 'nullable|date',
            'promotion_remarks' => 'nullable|string',
        ]);

        $user = User::findOrFail($id);
        $oldValues = $user->toArray();

        $userData = [
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'phone_number' => $request->phone_no,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if (
            Auth::user()->hasAnyRole(['Super Admin', 'Admin']) &&
            Auth::id() != $user->id &&
            $request->filled('role')
        ) {
            $newRole = Role::find($request->role);
            $currentRole = $user->roles->first();

            if ($newRole && $newRole->name !== 'Super Admin') {
                if (!$currentRole || $currentRole->id != $newRole->id) {
                    $userData['previous_role_id'] = $currentRole ? $currentRole->id : null;
                    $userData['current_role_id'] = $newRole->id;
                    $userData['promotion_date'] = $request->promotion_date ? Carbon::parse($request->promotion_date) : Carbon::now();
                    $userData['promotion_remarks'] = $request->promotion_remarks ?: 'Role updated via user edit';
                    
                    $user->syncRoles([$newRole->name]);
                }
            } else {
                return redirect()->back()->with('error', 'Invalid role assignment.');
            }
        }

        $user->update($userData);
        $newValues = $user->toArray();

        logActivity(
            'Update User',
            'Updated user ID: ' . $id . ' | Changes: ' . json_encode(array_diff_assoc($newValues, $oldValues))
        );
        
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }


    public function destroy(string $id)
    {
        if (!Gate::allows('delete user')) {
            abort(403);
        }

        User::where('id', $id)->delete();
        logActivity('Delete User', 'Deleted user ID: ' . $id);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }

      /**
     * Method to manually track promotion (can be called from other places)
     */
    public function trackPromotion(Request $request, $userId)
    {
        if (!Gate::allows('edit user')) {
            abort(403);
        }

        $request->validate([
            'new_role_id' => 'required|exists:roles,id',
            'promotion_date' => 'required|date',
            'promotion_remarks' => 'nullable|string',
        ]);

        $user = User::findOrFail($userId);
        $currentRole = $user->roles->first();
        $newRole = Role::find($request->new_role_id);

        if ($currentRole && $currentRole->id != $newRole->id) {
            $user->update([
                'previous_role_id' => $currentRole->id,
                'current_role_id' => $newRole->id,
                'promotion_date' => Carbon::parse($request->promotion_date),
                'promotion_remarks' => $request->promotion_remarks,
            ]);

            $user->syncRoles([$newRole->name]);
            
            logActivity('Promotion Tracked', "User {$user->id} promoted from {$currentRole->name} to {$newRole->name}");
            return redirect()->back()->with('success', 'Promotion tracked successfully.');
        }

        return redirect()->back()->with('error', 'No role change detected.');
    }
}
