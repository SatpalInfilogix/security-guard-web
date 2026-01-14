<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Module;
use App\Models\User;
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
        return view('admin.permissions.index', compact('roles', 'modules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.roles.create');
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
    public function storeRole(Request $request){

        $exist = Role::where('name',$request->role)->first();
        if($exist){
            return redirect()->to(route('roles-and-permissions.create'))->with('message', 'Role already exist');
        }else{    
            Role::create([
                'name' => $request->role,
                'is_manager_level' => $request->has('is_manager_level'),
            ]);
            return redirect()->to(route('roles-and-permissions.role-list'))->with('success', 'Role created successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $roles = Role::where('name', '!=', 'Admin')->get();

        $assignedRoleIds = Role::whereHas('users')->pluck('id');
        $specificRoles = Role::whereIn('name', ['Employee', 'Security Guard', 'Manager Operations', 'General Manager'])->pluck('id');
        $mergedRoleIds = $assignedRoleIds->merge($specificRoles);
        $uniqueRoleIds = $mergedRoleIds->unique()->values();

        return view('admin.roles.index', compact('roles', 'uniqueRoleIds'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::where('id', $id)->first();
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Role::where('id',$id)->update([
            'name' => $request->role,
            'is_manager_level' => $request->has('is_manager_level'),
        ]);
        return redirect()->to(route('roles-and-permissions.role-list'))->with('success', 'Role updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::where('id',$id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.'
        ]);
    }
}
