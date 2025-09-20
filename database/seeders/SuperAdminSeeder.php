<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create super admin user if it doesn't exist
        $superAdmin = User::where('email', 'admin@raquetpower.com')->first();
        
        if (!$superAdmin) {
            User::create([
                'name' => 'Super Administrador',
                'email' => 'admin@raquetpower.com',
                'password' => Hash::make('admin123456'),
                'role' => 'super_admin',
                'phone' => '+593 99 999 9999',
                'country' => 'Ecuador',
                'email_verified_at' => now(),
            ]);

            $this->command->info('Super Admin created successfully!');
            $this->command->info('Email: admin@raquetpower.com');
            $this->command->info('Password: admin123456');
        } else {
            $this->command->info('Super Admin already exists.');
        }

        // Create additional test admin user for development
        $testAdmin = User::where('email', 'admin@admin.com')->first();
        
        if (!$testAdmin) {
            User::create([
                'name' => 'Test Administrator',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin123456'),
                'role' => 'super_admin',
                'phone' => '+593 99 888 8888',
                'country' => 'Ecuador',
                'email_verified_at' => now(),
            ]);

            $this->command->info('Test Admin created successfully!');
            $this->command->info('Email: admin@admin.com');
            $this->command->info('Password: admin123456');
        } else {
            $this->command->info('Test Admin already exists.');
        }
    }
}