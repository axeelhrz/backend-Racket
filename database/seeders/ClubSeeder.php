<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Club;
use App\Models\League;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get existing leagues
        $ligaNacional = League::where('name', 'Liga Nacional de Tenis de Mesa')->first();
        $ligaPichincha = League::where('name', 'Liga Provincial de Pichincha')->first();
        $ligaGuayas = League::where('name', 'Liga Regional del Guayas')->first();

        if (!$ligaNacional || !$ligaPichincha || !$ligaGuayas) {
            $this->command->error('Please run LeagueSeeder first!');
            return;
        }

        $clubs = [
            [
                'name' => 'Club Deportivo Los Campeones',
                'city' => 'Quito',
                'address' => 'Av. 6 de Diciembre N24-253 y Wilson',
                'league_id' => $ligaNacional->id,
                'admin_email' => 'club.campeones@raquetpower.com',
                'admin_name' => 'Club Deportivo Los Campeones',
                'admin_phone' => '+593 99 555 5555',
            ],
            [
                'name' => 'Club Raqueta de Oro',
                'city' => 'Guayaquil',
                'address' => 'Av. Francisco de Orellana y 9 de Octubre',
                'league_id' => $ligaGuayas->id,
                'admin_email' => 'club.raquetadeoro@raquetpower.com',
                'admin_name' => 'Club Raqueta de Oro',
                'admin_phone' => '+593 99 666 6666',
            ],
            [
                'name' => 'Club Tenis de Mesa Quito',
                'city' => 'Quito',
                'address' => 'Av. Amazonas N21-217 y Roca',
                'league_id' => $ligaPichincha->id,
                'admin_email' => 'club.tenismesaquito@raquetpower.com',
                'admin_name' => 'Club Tenis de Mesa Quito',
                'admin_phone' => '+593 99 777 7777',
            ],
            [
                'name' => 'Club Deportivo Machala',
                'city' => 'Machala',
                'address' => 'Av. Las Palmeras y Circunvalación Sur',
                'league_id' => $ligaGuayas->id,
                'admin_email' => 'club.machala@raquetpower.com',
                'admin_name' => 'Club Deportivo Machala',
                'admin_phone' => '+593 99 888 8888',
            ],
            [
                'name' => 'Club Ping Pong Elite',
                'city' => 'Cuenca',
                'address' => 'Av. Solano 1-38 y Av. 12 de Abril',
                'league_id' => $ligaNacional->id,
                'admin_email' => 'club.pingpongelite@raquetpower.com',
                'admin_name' => 'Club Ping Pong Elite',
                'admin_phone' => '+593 99 999 9999',
            ],
        ];

        foreach ($clubs as $clubData) {
            // Check if club already exists
            $existingClub = Club::where('name', $clubData['name'])->first();
            
            if (!$existingClub) {
                // Create club admin user
                $adminUser = User::create([
                    'name' => $clubData['admin_name'],
                    'email' => $clubData['admin_email'],
                    'password' => Hash::make('club123456'),
                    'role' => 'club',
                    'phone' => $clubData['admin_phone'],
                    'country' => 'Ecuador',
                    'club_name' => $clubData['name'],
                    'parent_league_id' => $clubData['league_id'],
                    'city' => $clubData['city'],
                    'address' => $clubData['address'],
                    'email_verified_at' => now(),
                ]);

                // Create club entity
                $club = Club::create([
                    'user_id' => $adminUser->id,
                    'league_id' => $clubData['league_id'],
                    'name' => $clubData['name'],
                    'city' => $clubData['city'],
                    'address' => $clubData['address'],
                    'status' => 'active',
                ]);

                // Update user with polymorphic relation
                $adminUser->update([
                    'roleable_id' => $club->id,
                    'roleable_type' => Club::class,
                ]);

                $this->command->info("Club '{$clubData['name']}' created successfully!");
            } else {
                $this->command->info("Club '{$clubData['name']}' already exists.");
            }
        }

        $this->command->info('Club seeding completed!');
        $this->command->info('Default password for all club admins: club123456');
    }
}