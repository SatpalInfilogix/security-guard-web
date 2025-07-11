<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'Super Admin']);
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => '123',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Admin@12345'),
        ]);
        $adminRole = Role::where('name', 'Super Admin')->first();
        $admin->assignRole($adminRole);     
    }
}
