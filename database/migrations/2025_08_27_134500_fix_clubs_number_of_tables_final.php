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
        try {
            // First, check if the column exists
            if (Schema::hasColumn('clubs', 'number_of_tables')) {
                // Update any existing null values to 0
                DB::table('clubs')->whereNull('number_of_tables')->update(['number_of_tables' => 0]);
                
                // Make the column nullable with a default value
                Schema::table('clubs', function (Blueprint $table) {
                    $table->integer('number_of_tables')->default(0)->nullable()->change();
                });
            }
            
            // Also fix other potentially problematic columns
            if (Schema::hasColumn('clubs', 'can_create_tournaments')) {
                DB::table('clubs')->whereNull('can_create_tournaments')->update(['can_create_tournaments' => false]);
                Schema::table('clubs', function (Blueprint $table) {
                    $table->boolean('can_create_tournaments')->default(false)->nullable()->change();
                });
            }
            
            if (Schema::hasColumn('clubs', 'total_members')) {
                DB::table('clubs')->whereNull('total_members')->update(['total_members' => 0]);
                Schema::table('clubs', function (Blueprint $table) {
                    $table->integer('total_members')->default(0)->nullable()->change();
                });
            }
            
        } catch (\Exception $e) {
            // Log the error but don't fail the migration
            \Log::error('Migration error: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't reverse this migration as it fixes critical issues
    }
};