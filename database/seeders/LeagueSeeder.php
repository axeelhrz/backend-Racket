<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\League;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $leagues = [
            [
                'name' => 'Liga Nacional de Tenis de Mesa',
                'province' => 'Nacional',
                'region' => 'Nacional',
                'admin_email' => 'liga.nacional@raquetpower.com',
                'admin_name' => 'Liga Nacional de Tenis de Mesa',
                'admin_phone' => '+593 99 111 1111',
            ],
            [
                'name' => 'Liga Provincial de Pichincha',
                'province' => 'Pichincha',
                'region' => 'Sierra',
                'admin_email' => 'liga.pichincha@raquetpower.com',
                'admin_name' => 'Liga Provincial de Pichincha',
                'admin_phone' => '+593 99 222 2222',
            ],
            [
                'name' => 'Liga Regional del Guayas',
                'province' => 'Guayas',
                'region' => 'Costa',
                'admin_email' => 'liga.guayas@raquetpower.com',
                'admin_name' => 'Liga Regional del Guayas',
                'admin_phone' => '+593 99 333 3333',
            ],
            [
                'name' => 'Liga Provincial del Azuay',
                'province' => 'Azuay',
                'region' => 'Sierra',
                'admin_email' => 'liga.azuay@raquetpower.com',
                'admin_name' => 'Liga Provincial del Azuay',
                'admin_phone' => '+593 99 444 4444',
            ],
        ];

        foreach ($leagues as $leagueData) {
            // Check if league already exists
            $existingLeague = League::where('name', $leagueData['name'])->first();
            
            if (!$existingLeague) {
                // Create league admin user
                $adminUser = User::create([
                    'name' => $leagueData['admin_name'],
                    'email' => $leagueData['admin_email'],
                    'password' => Hash::make('liga123456'),
                    'role' => 'liga',
                    'phone' => $leagueData['admin_phone'],
                    'country' => 'Ecuador',
                    'league_name' => $leagueData['name'],
                    'province' => $leagueData['province'],
                    'email_verified_at' => now(),
                ]);

                // Create league entity
                $league = League::create([
                    'user_id' => $adminUser->id,
                    'name' => $leagueData['name'],
                    'region' => $leagueData['region'],
                    'province' => $leagueData['province'],
                    'status' => 'active',
                ]);

                // Update user with polymorphic relation
                $adminUser->update([
                    'roleable_id' => $league->id,
                    'roleable_type' => League::class,
                ]);

                $this->command->info("Liga '{$leagueData['name']}' created successfully!");
            } else {
                $this->command->info("Liga '{$leagueData['name']}' already exists.");
            }
        }

        $this->command->info('League seeding completed!');
        $this->command->info('Default password for all league admins: liga123456');
    }
}