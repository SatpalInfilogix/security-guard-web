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
            'view guard roster', 'create guard roster', 'edit guard roster', 'delete guard roster',
            'view attendance', 'create attendance', 'edit attendance', 'delete attendance',
            'view leaves', 'create leaves', 'edit leaves', 'delete leaves',
            'view rate master', 'create rate master', 'edit rate master', 'delete rate master',
            'view client', 'create client', 'edit client', 'delete client',
            'view client site', 'create client site', 'edit client site', 'delete client site',
            'view public holiday', 'create public holiday', 'edit public holiday', 'delete public holiday',
            'view payroll', 'create payroll', 'edit payroll', 'delete payroll',
            'view nst deduction', 'create nst deduction', 'edit nst deduction', 'delete nst deduction',
            'view invoice', 'create invoice', 'edit invoice', 'delete invoice',
            'view faq', 'create faq', 'edit faq', 'delete faq',
            'view site setting', 'create site setting', 'edit site setting', 'delete site setting',
            'view gerenal setting', 'create gerenal setting', 'edit gerenal setting', 'delete gerenal setting',
            'view payment setting', 'create payment setting', 'edit payment setting', 'delete payment setting',
            'view help request', 'create help request', 'edit help request', 'delete help request',
            'view employee', 'create employee', 'edit employee', 'delete employee',
            'view employee rate master', 'create employee rate master', 'edit employee rate master', 'delete employee rate master',
            'view employee leaves', 'create employee leaves', 'edit employee leaves', 'delete employee leaves',
            'view employee payroll', 'create employee payroll', 'edit employee payroll', 'delete employee payroll',
            'view employee deduction','create employee deduction','edit employee deduction','delete employee deduction',
            'view employee encashment','create employee encashment','edit employee encashment','delete employee encashment',
            'view employee overtime','create employee overtime','edit employee overtime','delete employee overtime',
            'view guard encashment','create guard encashment','edit guard encashment','delete guard encashment',
            'view user activity',
            'view employee tax threshold','create employee tax threshold','edit employee tax threshold','delete employee tax threshold',
            'view guard tax threshold','create guard tax threshold','edit guard tax threshold','delete guard tax threshold',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'Super Admin'=> Permission::all(),
            'Admin' => Permission::all(),
            'General Manager'       => ['view guard roster', 'create guard roster', 'edit guard roster'],
            'Manager Operations'    => ['view guard roster', 'create guard roster', 'edit guard roster'],
            'Asst Mgr Operations'   => ['view guard roster', 'create guard roster', 'edit guard roster'],
            'Sr. Supervisor'        => ['view guard roster'],
            'Supervisor'            => ['view guard roster'],
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
