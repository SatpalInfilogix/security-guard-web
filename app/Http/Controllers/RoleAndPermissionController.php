<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Module;
use Illuminate\Support\Facades\Gate;


class RoleAndPermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('view roles & permissions')) {
            abort(403);
        }

        $roles = Role::where('name', '!=', 'Admin')->get();
        $modules = Module::get();
        return view('roles-and-permissions.index', compact('roles', 'modules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $role = Role::find($request->role_id);
        
        if($request->permissions) {
            foreach($request->permissions as $permission){
                $existingPermission = Permission::where('name', $permission)->first();
                if(!$existingPermission){
                    Permission::create(['name' => $permission]);
                    
                    $admin_role = Role::where('name', 'Admin')->first();
                    $admin_role->givePermissionTo($permission);
                }
            }
        }
        $role->syncPermissions($request->permissions);

        return redirect()->to(route('roles-and-permissions.index'))->with('success', 'Permissions updated successfully');
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
