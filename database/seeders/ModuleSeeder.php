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
            'name' => 'Guard Roaster',
            'slug' => 'guard roaster'
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
    }
}
