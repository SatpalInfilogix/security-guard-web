<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UsersDocuments;
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

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        if (!Gate::allows('create user')) {
            abort(403);
        }
        $roles = Role::latest()->get();

        return view('admin.users.create', compact('roles'));
    }


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
        ]);

        if ($existingUser) {
            $existingUser->update([
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_no,
                'password'     => $request->password ? Hash::make($request->password) : $existingUser->password,
            ]);

            if (!$existingUser->hasRole($request->role) || $existingUser->hasRole('Employee')) {
                $existingUser->assignRole($request->role);
            }
        } else {
            $newUser = User::create([
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'email'        => $request->email,
                'phone_number' => $request->phone_no,
                'password'     => Hash::make($request->password),
            ]);

            $newUser->assignRole($request->role);
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

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        if (!Gate::allows('edit user')) {
            abort(403);
        }

        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'phone_no'   => 'required|unique:users,phone_number,' . $id,
        ]);

        $user = User::findOrFail($id);

        $user->first_name   = $request->first_name;
        $user->last_name    = $request->last_name;
        $user->phone_number = $request->phone_no;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if (
            Auth::user()->hasAnyRole(['Super Admin', 'Admin']) && 
            Auth::id() != $user->id &&                             
            $request->filled('role')
        ) {
            $role = Role::find($request->role);

            if ($role && $role->name !== 'Super Admin') {
                $user->syncRoles([$role->name]);
            } else {
                return redirect()->back()->with('error', 'Invalid role assignment.');
            }
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }



    public function destroy(string $id)
    {
        if (!Gate::allows('delete user')) {
            abort(403);
        }

        User::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}
