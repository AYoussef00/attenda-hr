<?php

namespace Database\Seeders;

use App\Models\Admin\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $admin = User::where('email', 'admin@gmail.com')->first();

        if (!$admin) {
            User::create([
                'company_id' => null, // Super admin doesn't belong to any company
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password123'),
                'phone' => null,
                'role' => 'super_admin',
                'status' => 'active',
            ]);

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@gmail.com');
            $this->command->info('Password: password123');
        } else {
            $this->command->info('Admin user already exists!');
        }
    }
}
