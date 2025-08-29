<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            LeagueSeeder::class,
            ClubSeeder::class,
            SportSeeder::class,
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📧 Default credentials:');
        $this->command->info('Super Admin: admin@raquetpower.com / admin123456');
        $this->command->info('League Admins: liga123456');
        $this->command->info('Club Admins: club123456');
        $this->command->info('');
        $this->command->info('🚀 You can now test the authentication system!');
    }
}