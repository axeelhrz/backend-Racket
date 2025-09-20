<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentReferenceSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Seed racket brands
        $racketBrands = [
            ['name' => 'Butterfly', 'country' => 'Japan', 'is_active' => true],
            ['name' => 'DHS', 'country' => 'China', 'is_active' => true],
            ['name' => 'Sanwei', 'country' => 'China', 'is_active' => true],
            ['name' => 'Nittaku', 'country' => 'Japan', 'is_active' => true],
            ['name' => 'Yasaka', 'country' => 'Sweden', 'is_active' => true],
            ['name' => 'Stiga', 'country' => 'Sweden', 'is_active' => true],
            ['name' => 'Victas', 'country' => 'Japan', 'is_active' => true],
            ['name' => 'Joola', 'country' => 'Germany', 'is_active' => true],
            ['name' => 'Xiom', 'country' => 'South Korea', 'is_active' => true],
            ['name' => 'Saviga', 'country' => 'China', 'is_active' => true],
            ['name' => 'Friendship', 'country' => 'China', 'is_active' => true],
            ['name' => 'Dr. Neubauer', 'country' => 'Germany', 'is_active' => true],
        ];

        foreach ($racketBrands as $brand) {
            DB::table('racket_brands')->updateOrInsert(
                ['name' => $brand['name']],
                $brand + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed rubber brands (same as racket brands for table tennis)
        foreach ($racketBrands as $brand) {
            DB::table('rubber_brands')->updateOrInsert(
                ['name' => $brand['name']],
                $brand + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed Ecuador locations
        $ecuadorLocations = [
            ['province' => 'Guayas', 'city' => 'Guayaquil', 'is_active' => true],
            ['province' => 'Guayas', 'city' => 'Milagro', 'is_active' => true],
            ['province' => 'Guayas', 'city' => 'Buena Fe', 'is_active' => true],
            ['province' => 'Pichincha', 'city' => 'Quito', 'is_active' => true],
            ['province' => 'Manabí', 'city' => 'Manta', 'is_active' => true],
            ['province' => 'Manabí', 'city' => 'Portoviejo', 'is_active' => true],
            ['province' => 'Azuay', 'city' => 'Cuenca', 'is_active' => true],
            ['province' => 'Tungurahua', 'city' => 'Ambato', 'is_active' => true],
            ['province' => 'Los Ríos', 'city' => 'Quevedo', 'is_active' => true],
            ['province' => 'Los Ríos', 'city' => 'Urdaneta', 'is_active' => true],
            ['province' => 'Santa Elena', 'city' => 'La Libertad', 'is_active' => true],
            ['province' => 'Galápagos', 'city' => 'Puerto Ayora', 'is_active' => true],
        ];

        foreach ($ecuadorLocations as $location) {
            DB::table('ecuador_locations')->updateOrInsert(
                ['province' => $location['province'], 'city' => $location['city']],
                $location + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed table tennis clubs in Ecuador
        $ttClubs = [
            ['name' => 'PPH Cuenca', 'city' => 'Cuenca', 'province' => 'Azuay', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Ping Pro', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Billy Team', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Independiente', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'BackSpin', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Spin Factor', 'city' => 'Portoviejo', 'province' => 'Manabí', 'federation' => 'Fede - Manabí', 'is_active' => true],
            ['name' => 'Spin Zone', 'city' => 'Ambato', 'province' => 'Tungurahua', 'federation' => 'Fede Tungurahua', 'is_active' => true],
            ['name' => 'TM - Manta', 'city' => 'Manta', 'province' => 'Manabí', 'federation' => 'Fede - Manabí', 'is_active' => true],
            ['name' => 'Primorac', 'city' => 'Quito', 'province' => 'Pichincha', 'federation' => 'Fede Pichincha', 'is_active' => true],
            ['name' => 'TT Quevedo', 'city' => 'Quevedo', 'province' => 'Los Ríos', 'federation' => 'Fede Los Ríos', 'is_active' => true],
            ['name' => 'Fede Santa Elena', 'city' => 'La Libertad', 'province' => 'Santa Elena', 'federation' => 'Fede Santa Elena', 'is_active' => true],
            ['name' => 'Ranking Uartes', 'city' => 'Puerto Ayora', 'province' => 'Galápagos', 'federation' => 'Fede Galápagos', 'is_active' => true],
            ['name' => 'Guayaquil City', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Buena Fe', 'city' => 'Buena Fe', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Milagro', 'city' => 'Milagro', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Ping Pong Rick', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'Fede Guayas', 'is_active' => true],
            ['name' => 'Ranking Liga 593', 'city' => 'Guayaquil', 'province' => 'Guayas', 'federation' => 'LATEM', 'is_active' => true],
        ];

        foreach ($ttClubs as $club) {
            DB::table('tt_clubs_reference')->updateOrInsert(
                ['name' => $club['name'], 'city' => $club['city']],
                $club + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed popular racket models
        $racketModels = [
            // Sanwei models
            ['brand_name' => 'Sanwei', 'name' => '5L carbono+', 'type' => 'Offensive', 'speed' => 9, 'control' => 7, 'weight' => 85],
            ['brand_name' => 'Sanwei', 'name' => 'Fextra 7', 'type' => 'All-round+', 'speed' => 8, 'control' => 8, 'weight' => 87],
            ['brand_name' => 'Sanwei', 'name' => 'Target National', 'type' => 'Offensive', 'speed' => 9, 'control' => 6, 'weight' => 83],
            
            // Butterfly models
            ['brand_name' => 'Butterfly', 'name' => 'Timo Boll ALC', 'type' => 'Offensive', 'speed' => 9, 'control' => 8, 'weight' => 86],
            ['brand_name' => 'Butterfly', 'name' => 'Viscaria', 'type' => 'Offensive', 'speed' => 10, 'control' => 7, 'weight' => 85],
            ['brand_name' => 'Butterfly', 'name' => 'Primorac Carbon', 'type' => 'All-round+', 'speed' => 8, 'control' => 9, 'weight' => 88],
            
            // DHS models
            ['brand_name' => 'DHS', 'name' => 'Hurricane Long 5', 'type' => 'Offensive', 'speed' => 9, 'control' => 7, 'weight' => 84],
            ['brand_name' => 'DHS', 'name' => 'Power G7', 'type' => 'All-round+', 'speed' => 8, 'control' => 8, 'weight' => 86],
        ];

        foreach ($racketModels as $model) {
            $brandId = DB::table('racket_brands')->where('name', $model['brand_name'])->value('id');
            if ($brandId) {
                DB::table('racket_models')->updateOrInsert(
                    ['brand_id' => $brandId, 'name' => $model['name']],
                    [
                        'brand_id' => $brandId,
                        'name' => $model['name'],
                        'type' => $model['type'],
                        'speed' => $model['speed'],
                        'control' => $model['control'],
                        'weight' => $model['weight'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

        // Seed popular rubber models
        $rubberModels = [
            // Friendship models
            ['brand_name' => 'Friendship', 'name' => 'Cross 729', 'type' => 'liso', 'speed' => 8, 'spin' => 9, 'control' => 7],
            ['brand_name' => 'Friendship', 'name' => 'Battle II', 'type' => 'liso', 'speed' => 9, 'spin' => 8, 'control' => 6],
            
            // Saviga models
            ['brand_name' => 'Saviga', 'name' => 'Vpupo', 'type' => 'pupo_largo', 'speed' => 6, 'spin' => 10, 'control' => 8],
            ['brand_name' => 'Saviga', 'name' => 'Anti-Top', 'type' => 'antitopspin', 'speed' => 4, 'spin' => 2, 'control' => 9],
            
            // DHS models
            ['brand_name' => 'DHS', 'name' => 'Hurricane 3', 'type' => 'liso', 'speed' => 9, 'spin' => 10, 'control' => 6],
            ['brand_name' => 'DHS', 'name' => 'Skyline 3', 'type' => 'liso', 'speed' => 8, 'spin' => 8, 'control' => 8],
            
            // Butterfly models
            ['brand_name' => 'Butterfly', 'name' => 'Tenergy 05', 'type' => 'liso', 'speed' => 9, 'spin' => 10, 'control' => 7],
            ['brand_name' => 'Butterfly', 'name' => 'Sriver', 'type' => 'liso', 'speed' => 7, 'spin' => 7, 'control' => 9],
        ];

        foreach ($rubberModels as $model) {
            $brandId = DB::table('rubber_brands')->where('name', $model['brand_name'])->value('id');
            if ($brandId) {
                DB::table('rubber_models')->updateOrInsert(
                    ['brand_id' => $brandId, 'name' => $model['name']],
                    [
                        'brand_id' => $brandId,
                        'name' => $model['name'],
                        'type' => $model['type'],
                        'speed' => $model['speed'],
                        'spin' => $model['spin'],
                        'control' => $model['control'],
                        'available_colors' => json_encode(['negro', 'rojo']),
                        'available_sponges' => json_encode(['1.8', '2.0', '2.1', '2.2']),
                        'available_hardness' => json_encode(['h42', 'h44', 'h46']),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
    }
}