<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view user', 'create user', 'edit user', 'delete user',
            'view roles & permissions', 'create roles & permissions', 'edit roles & permissions', 'delete roles & permissions',
            'view security guards', 'create security guards', 'edit security guards', 'delete security guards',
            'view guard roaster', 'create guard roaster', 'edit guard roaster', 'delete guard roaster',
            'view attendance', 'create attendance', 'edit attendance', 'delete attendance',
            'view leaves', 'create leaves', 'edit leaves', 'delete leaves',
            'view rate master', 'create rate master', 'edit rate master', 'delete rate master',
            'view client', 'create client', 'edit client', 'delete client',
            'view client site', 'create client site', 'edit client site', 'delete client site',
            'view public holiday', 'create public holiday', 'edit public holiday', 'delete public holiday'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'Admin' => Permission::all(),
            'General Manager'       => ['view guard roaster', 'create guard roaster', 'edit guard roaster'],
            'Manager Operations'    => ['view guard roaster', 'create guard roaster', 'edit guard roaster'],
            'Asst Mgr Operations'   => ['view guard roaster', 'create guard roaster', 'edit guard roaster'],
            'Sr. Supervisor'        => ['view guard roaster'],
            'Supervisor'            => ['view guard roaster'],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if (is_array($permissions)) {
                $role->givePermissionTo($permissions);
            } else {
                $role->givePermissionTo($permissions);
            }
        }
    }
}
