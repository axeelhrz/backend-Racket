<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Only add columns if they don't already exist
            if (!Schema::hasColumn('clubs', 'club_code')) {
                $table->string('club_code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('clubs', 'ruc')) {
                $table->string('ruc')->nullable()->after('name');
            }
            if (!Schema::hasColumn('clubs', 'country')) {
                $table->string('country')->default('Ecuador')->after('city');
            }
            if (!Schema::hasColumn('clubs', 'province')) {
                $table->string('province')->nullable()->after('country');
            }
            if (!Schema::hasColumn('clubs', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            }
            if (!Schema::hasColumn('clubs', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('clubs', 'google_maps_url')) {
                $table->string('google_maps_url')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('clubs', 'total_members')) {
                $table->integer('total_members')->default(0)->after('google_maps_url');
            }
            if (!Schema::hasColumn('clubs', 'number_of_tables')) {
                $table->integer('number_of_tables')->default(0)->after('total_members');
            }
            if (!Schema::hasColumn('clubs', 'average_ranking')) {
                $table->decimal('average_ranking', 8, 2)->nullable()->after('number_of_tables');
            }
            
            // Category counts
            if (!Schema::hasColumn('clubs', 'u800_count')) {
                $table->integer('u800_count')->default(0)->after('average_ranking');
            }
            if (!Schema::hasColumn('clubs', 'u900_count')) {
                $table->integer('u900_count')->default(0)->after('u800_count');
            }
            if (!Schema::hasColumn('clubs', 'u901_u1000_count')) {
                $table->integer('u901_u1000_count')->default(0)->after('u900_count');
            }
            if (!Schema::hasColumn('clubs', 'u1001_u1100_count')) {
                $table->integer('u1001_u1100_count')->default(0)->after('u901_u1000_count');
            }
            if (!Schema::hasColumn('clubs', 'u1101_u1200_count')) {
                $table->integer('u1101_u1200_count')->default(0)->after('u1001_u1100_count');
            }
            if (!Schema::hasColumn('clubs', 'u1201_u1300_count')) {
                $table->integer('u1201_u1300_count')->default(0)->after('u1101_u1200_count');
            }
            if (!Schema::hasColumn('clubs', 'u1301_u1400_count')) {
                $table->integer('u1301_u1400_count')->default(0)->after('u1201_u1300_count');
            }
            if (!Schema::hasColumn('clubs', 'u1401_u1500_count')) {
                $table->integer('u1401_u1500_count')->default(0)->after('u1301_u1400_count');
            }
            if (!Schema::hasColumn('clubs', 'u1501_u1600_count')) {
                $table->integer('u1501_u1600_count')->default(0)->after('u1401_u1500_count');
            }
            if (!Schema::hasColumn('clubs', 'u1601_u1700_count')) {
                $table->integer('u1601_u1700_count')->default(0)->after('u1501_u1600_count');
            }
            if (!Schema::hasColumn('clubs', 'u1701_u1800_count')) {
                $table->integer('u1701_u1800_count')->default(0)->after('u1601_u1700_count');
            }
            if (!Schema::hasColumn('clubs', 'u1801_u1900_count')) {
                $table->integer('u1801_u1900_count')->default(0)->after('u1701_u1800_count');
            }
            if (!Schema::hasColumn('clubs', 'u1901_u2000_count')) {
                $table->integer('u1901_u2000_count')->default(0)->after('u1801_u1900_count');
            }
            if (!Schema::hasColumn('clubs', 'u2001_u2100_count')) {
                $table->integer('u2001_u2100_count')->default(0)->after('u1901_u2000_count');
            }
            if (!Schema::hasColumn('clubs', 'u2101_u2200_count')) {
                $table->integer('u2101_u2200_count')->default(0)->after('u2001_u2100_count');
            }
            if (!Schema::hasColumn('clubs', 'over_u2200_count')) {
                $table->integer('over_u2200_count')->default(0)->after('u2101_u2200_count');
            }
            
            // Representative information
            if (!Schema::hasColumn('clubs', 'representative_name')) {
                $table->string('representative_name')->nullable()->after('over_u2200_count');
            }
            if (!Schema::hasColumn('clubs', 'representative_phone')) {
                $table->string('representative_phone')->nullable()->after('representative_name');
            }
            if (!Schema::hasColumn('clubs', 'representative_email')) {
                $table->string('representative_email')->nullable()->after('representative_phone');
            }
            
            // Administrator 1
            if (!Schema::hasColumn('clubs', 'admin1_name')) {
                $table->string('admin1_name')->nullable()->after('representative_email');
            }
            if (!Schema::hasColumn('clubs', 'admin1_phone')) {
                $table->string('admin1_phone')->nullable()->after('admin1_name');
            }
            if (!Schema::hasColumn('clubs', 'admin1_email')) {
                $table->string('admin1_email')->nullable()->after('admin1_phone');
            }
            
            // Administrator 2
            if (!Schema::hasColumn('clubs', 'admin2_name')) {
                $table->string('admin2_name')->nullable()->after('admin1_email');
            }
            if (!Schema::hasColumn('clubs', 'admin2_phone')) {
                $table->string('admin2_phone')->nullable()->after('admin2_name');
            }
            if (!Schema::hasColumn('clubs', 'admin2_email')) {
                $table->string('admin2_email')->nullable()->after('admin2_phone');
            }
            
            // Administrator 3
            if (!Schema::hasColumn('clubs', 'admin3_name')) {
                $table->string('admin3_name')->nullable()->after('admin2_email');
            }
            if (!Schema::hasColumn('clubs', 'admin3_phone')) {
                $table->string('admin3_phone')->nullable()->after('admin3_name');
            }
            if (!Schema::hasColumn('clubs', 'admin3_email')) {
                $table->string('admin3_email')->nullable()->after('admin3_phone');
            }
            
            // Additional metadata
            if (!Schema::hasColumn('clubs', 'ranking_history')) {
                $table->json('ranking_history')->nullable()->after('admin3_email');
            }
            if (!Schema::hasColumn('clubs', 'monthly_stats')) {
                $table->json('monthly_stats')->nullable()->after('ranking_history');
            }
            if (!Schema::hasColumn('clubs', 'can_create_tournaments')) {
                $table->boolean('can_create_tournaments')->default(false)->after('monthly_stats');
            }
            if (!Schema::hasColumn('clubs', 'description')) {
                $table->text('description')->nullable()->after('can_create_tournaments');
            }
            if (!Schema::hasColumn('clubs', 'founded_date')) {
                $table->date('founded_date')->nullable()->after('description');
            }
        });

        // Generate codes for existing clubs that don't have one
        if (Schema::hasColumn('clubs', 'club_code')) {
            $clubs = \App\Models\Club::whereNull('club_code')->get();
            foreach ($clubs as $club) {
                do {
                    $code = 'CLUB' . strtoupper(\Illuminate\Support\Str::random(6));
                } while (\App\Models\Club::where('club_code', $code)->exists());
                
                $club->update(['club_code' => $code]);
            }
        }

        // Skip index creation for now since they already exist in local environment
        // In production, these will be created when the columns are first added
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Drop columns if they exist
            $columnsToCheck = [
                'club_code', 'ruc', 'country', 'province', 'latitude', 'longitude', 'google_maps_url',
                'total_members', 'number_of_tables', 'average_ranking',
                'u800_count', 'u900_count', 'u901_u1000_count', 'u1001_u1100_count', 'u1101_u1200_count',
                'u1201_u1300_count', 'u1301_u1400_count', 'u1401_u1500_count', 'u1501_u1600_count',
                'u1601_u1700_count', 'u1701_u1800_count', 'u1801_u1900_count', 'u1901_u2000_count',
                'u2001_u2100_count', 'u2101_u2200_count', 'over_u2200_count',
                'representative_name', 'representative_phone', 'representative_email',
                'admin1_name', 'admin1_phone', 'admin1_email',
                'admin2_name', 'admin2_phone', 'admin2_email',
                'admin3_name', 'admin3_phone', 'admin3_email',
                'ranking_history', 'monthly_stats', 'can_create_tournaments', 'description', 'founded_date'
            ];
            
            $columnsToRemove = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('clubs', $column)) {
                    $columnsToRemove[] = $column;
                }
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};