<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@siril-motors.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch_id' => 1,
            'is_active' => true,
            'can_override_gold_rate' => false,
            'can_delete_transactions' => true,
        ]);

        // Create a default manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@siril-motors.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'branch_id' => 1,
            'is_active' => true,
            'can_override_gold_rate' => false,
            'can_delete_transactions' => false,
        ]);

        // Create a default staff user
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@siril-motors.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'branch_id' => 1,
            'is_active' => true,
            'can_override_gold_rate' => false,
            'can_delete_transactions' => false,
        ]);
    }
}
