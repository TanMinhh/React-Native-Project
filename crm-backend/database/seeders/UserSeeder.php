<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123456'),
                'role' => 'admin',
            ]
        );

        // Specific Owner
        User::firstOrCreate(
            ['email' => 'owner@crm.test'],
            [
                'name' => 'Owner User',
                'password' => Hash::make('password123456'),
                'role' => 'owner',
            ]
        );

        // Specific Staff
        User::firstOrCreate(
            ['email' => 'staff@crm.test'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password123456'),
                'role' => 'staff',
            ]
        );

        // Owners
        User::factory()->count(3)->create([
            'role' => 'owner',
        ]);

        // Staffs
        User::factory()->count(6)->create([
            'role' => 'staff',
        ]);
    }
}
