<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $excludedRoles = Role::whereIn('name', ['Security Guard'])->pluck('id');

        $users = User::whereDoesntHave('roles', function ($query) use ($excludedRoles) {
            $query->whereIn('role_id', $excludedRoles);
        })->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::latest()->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => ['required', 'email', 'unique:users,email'],
            'phone_no'   => 'required',
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
        $user = User::where('id', $id)->first();
        $roles = Role::latest()->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'first_name'    => 'required',
            'last_name'     => 'required',
            'phone_no'      => 'required',
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
        $user = User::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}
