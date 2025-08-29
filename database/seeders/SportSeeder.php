<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            [
                'name' => 'Tenis',
                'code' => 'TEN',
            ],
            [
                'name' => 'Tenis de Mesa',
                'code' => 'TM',
            ],
            [
                'name' => 'Padel',
                'code' => 'PAD',
            ],
            [
                'name' => 'Pickerball',
                'code' => 'PB',
            ],
            [
                'name' => 'Badminton',
                'code' => 'BAD',
            ],
            [
                'name' => 'Handball',
                'code' => 'HB',
            ],
            [
                'name' => 'Raquetball',
                'code' => 'RB',
            ],
        ];

        foreach ($sports as $sportData) {
            Sport::firstOrCreate(
                ['code' => $sportData['code']],
                $sportData
            );
        }

        $this->command->info('✅ Sports seeded successfully!');
        $this->command->info('📊 Created sports: Tenis, Tenis de Mesa, Padel, Pickerball, Badminton, Handball, Raquetball');
    }
}