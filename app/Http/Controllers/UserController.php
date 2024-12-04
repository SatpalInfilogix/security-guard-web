<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view user')) {
            abort(403);
        }
        $excludedRoles = Role::whereIn('name', ['Security Guard'])->pluck('id');

        $users = User::whereDoesntHave('roles', function ($query) use ($excludedRoles) {
            $query->whereIn('role_id', $excludedRoles);
        })->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        if(!Gate::allows('create user')) {
            abort(403);
        }
        $roles = Role::latest()->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create user')) {
            abort(403);
        }
        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => ['required', 'email', 'unique:users,email'],
            'phone_no'   => 'required|unique:users,phone_number',
            'password'   => 'required',
            'role'       => 'required'
        ]);

        User::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_no,
            'password'      =>  Hash::make($request->password)
        ])->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        if(!Gate::allows('edit user')) {
            abort(403);
        }
        $user = User::where('id', $id)->first();
        $roles = Role::latest()->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        if(!Gate::allows('edit user')) {
            abort(403);
        }
        $request->validate([
            'first_name'    => 'required',
            'last_name'     => 'required',
            'phone_no'      => 'required|unique:users,phone_no,' . $id, 
        ]);

        $user = User::where('id', $id)->update([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'phone_number'  => $request->phone_no,
        ]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(string $id)
    {
        if(!Gate::allows('delete user')) {
            abort(403);
        }
        $user = User::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}
