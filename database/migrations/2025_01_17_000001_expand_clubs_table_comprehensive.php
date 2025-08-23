<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Basic club information
            $table->string('club_code')->nullable()->after('id');
            $table->string('ruc')->nullable()->after('name');
            $table->string('country')->default('Ecuador')->after('city');
            $table->string('province')->nullable()->after('country');
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('google_maps_url')->nullable()->after('longitude');
            
            // Club statistics
            $table->integer('total_members')->default(0)->after('google_maps_url');
            $table->integer('number_of_tables')->default(0)->after('total_members');
            $table->decimal('average_ranking', 8, 2)->nullable()->after('number_of_tables');
            
            // Category counts
            $table->integer('u800_count')->default(0)->after('average_ranking');
            $table->integer('u900_count')->default(0)->after('u800_count');
            $table->integer('u901_u1000_count')->default(0)->after('u900_count');
            $table->integer('u1001_u1100_count')->default(0)->after('u901_u1000_count');
            $table->integer('u1101_u1200_count')->default(0)->after('u1001_u1100_count');
            $table->integer('u1201_u1300_count')->default(0)->after('u1101_u1200_count');
            $table->integer('u1301_u1400_count')->default(0)->after('u1201_u1300_count');
            $table->integer('u1401_u1500_count')->default(0)->after('u1301_u1400_count');
            $table->integer('u1501_u1600_count')->default(0)->after('u1401_u1500_count');
            $table->integer('u1601_u1700_count')->default(0)->after('u1501_u1600_count');
            $table->integer('u1701_u1800_count')->default(0)->after('u1601_u1700_count');
            $table->integer('u1801_u1900_count')->default(0)->after('u1701_u1800_count');
            $table->integer('u1901_u2000_count')->default(0)->after('u1801_u1900_count');
            $table->integer('u2001_u2100_count')->default(0)->after('u1901_u2000_count');
            $table->integer('u2101_u2200_count')->default(0)->after('u2001_u2100_count');
            $table->integer('over_u2200_count')->default(0)->after('u2101_u2200_count');
            
            // Representative information
            $table->string('representative_name')->nullable()->after('over_u2200_count');
            $table->string('representative_phone')->nullable()->after('representative_name');
            $table->string('representative_email')->nullable()->after('representative_phone');
            
            // Administrator 1
            $table->string('admin1_name')->nullable()->after('representative_email');
            $table->string('admin1_phone')->nullable()->after('admin1_name');
            $table->string('admin1_email')->nullable()->after('admin1_phone');
            
            // Administrator 2
            $table->string('admin2_name')->nullable()->after('admin1_email');
            $table->string('admin2_phone')->nullable()->after('admin2_name');
            $table->string('admin2_email')->nullable()->after('admin2_phone');
            
            // Administrator 3
            $table->string('admin3_name')->nullable()->after('admin2_email');
            $table->string('admin3_phone')->nullable()->after('admin3_name');
            $table->string('admin3_email')->nullable()->after('admin3_phone');
            
            // Additional metadata
            $table->json('ranking_history')->nullable()->after('admin3_email');
            $table->json('monthly_stats')->nullable()->after('ranking_history');
            $table->boolean('can_create_tournaments')->default(false)->after('monthly_stats');
            $table->text('description')->nullable()->after('can_create_tournaments');
            $table->date('founded_date')->nullable()->after('description');
            
            // Indexes for better performance
            $table->index(['club_code']);
            $table->index(['country', 'province', 'city']);
            $table->index(['average_ranking']);
            $table->index(['total_members']);
            $table->index(['can_create_tournaments']);
        });

        // After adding the column, generate codes for existing clubs
        $clubs = \App\Models\Club::whereNull('club_code')->get();
        foreach ($clubs as $club) {
            do {
                $code = 'CLUB' . strtoupper(\Illuminate\Support\Str::random(6));
            } while (\App\Models\Club::where('club_code', $code)->exists());
            
            $club->update(['club_code' => $code]);
        }

        // Now make club_code unique
        Schema::table('clubs', function (Blueprint $table) {
            $table->unique('club_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['club_code']);
            $table->dropIndex(['country', 'province', 'city']);
            $table->dropIndex(['average_ranking']);
            $table->dropIndex(['total_members']);
            $table->dropIndex(['can_create_tournaments']);
            
            $table->dropColumn([
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
            ]);
        });
    }
};