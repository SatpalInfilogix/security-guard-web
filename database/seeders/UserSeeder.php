<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Client']);
        Role::create(['name' => 'Security Guard']);

        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => '123',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Admin@12345'),
        ]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->assignRole($adminRole);
    }
}
