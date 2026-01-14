<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Module::create([
            'name' => 'User',
            'slug' => 'user'
        ]);
        Module::create([
            'name' => 'Roles & Permissions',
            'slug' => 'roles & permissions'
        ]);
        Module::create([
            'name' => 'Security Guards',
            'slug' => 'security guards'
        ]);
        Module::create([
            'name' => 'Guard Roster',
            'slug' => 'guard roster'
        ]);
        Module::create([
            'name' => 'Guard Encashment',
            'slug' => 'guard encashment',
        ]);
        Module::create([
            'name' => 'Attendance',
            'slug' => 'attendance'
        ]);
        Module::create([
            'name' => 'Leaves',
            'slug' => 'leaves'
        ]);
        Module::create([
            'name' => 'Rate Master',
            'slug' => 'rate master'
        ]);
        Module::create([
            'name' => 'Client',
            'slug' => 'client'
        ]);
        Module::create([
            'name' => 'Client Site',
            'slug' => 'client site'
        ]);
        Module::create([
            'name' => 'Public Holiday',
            'slug' => 'public holiday'
        ]);
        Module::create([
            'name' => 'NST Deduction',
            'slug' => 'nst deduction',
        ]);
        Module::create([
            'name' => 'Payroll',
            'slug' => 'payroll',
        ]);
        Module::create([
            'name' => 'Invoice',
            'slug' => 'invoice',
        ]);
        Module::create([
            'name' => 'FAQ',
            'slug' => 'faq',
        ]);
        Module::create([
            'name' => 'Site Setting',
            'slug' => 'site setting',
        ]);
        Module::create([
            'name' => 'Gerenal Setting',
            'slug' => 'gerenal setting',
        ]);
        Module::create([
            'name' => 'Payment Setting',
            'slug' => 'payment setting',
        ]);
        Module::create([
            'name' => 'Help Request',
            'slug' => 'help request',
        ]);
        Module::create([
            'name' => 'Employee',
            'slug' => 'employee',
        ]);
        Module::create([
            'name' => 'Employee Rate Master',
            'slug' => 'employee rate master',
        ]);
        Module::create([
            'name' => 'Employee Leaves',
            'slug' => 'employee leaves',
        ]);
        Module::create([
            'name' => 'Employee Payroll',
            'slug' => 'employee payroll',
        ]);
        Module::create([
            'name' => 'Employee Deduction',
            'slug' => 'employee deduction',
        ]);
        Module::create([
            'name' => 'Employee Encashment',
            'slug' => 'employee encashment',
        ]);
        Module::create([
            'name' => 'Employee Overtime',
            'slug' => 'employee overtime',
        ]);
        Module::create([
            'name' => 'Employee Tax Threshold',
            'slug' => 'employee tax threshold',
        ]);
        Module::create([
            'name' => 'Guard Tax Threshold',
            'slug' => 'guard tax threshold',
        ]);
        Module::create([
            'name' => 'User Activity',
            'slug' => 'user activity',
        ]);
    }
}
