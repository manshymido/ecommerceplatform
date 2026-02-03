<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // Create customer user
        $customer = User::firstOrCreate(
            ['email' => 'customer@test.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
            ]
        );
        $customer->assignRole('customer');

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@test.com'],
            [
                'name' => 'Super Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('super_admin');

        $this->command->info('Test users created:');
        $this->command->info('Admin: admin@test.com / password');
        $this->command->info('Customer: customer@test.com / password');
        $this->command->info('Super Admin: superadmin@test.com / password');
    }
}
