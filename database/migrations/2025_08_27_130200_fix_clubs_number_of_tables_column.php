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
        // Fix the number_of_tables column to allow null or have a default value
        if (Schema::hasColumn('clubs', 'number_of_tables')) {
            // First, update any null values to 0
            DB::table('clubs')->whereNull('number_of_tables')->update(['number_of_tables' => 0]);
            
            // Then modify the column to have a default value
            Schema::table('clubs', function (Blueprint $table) {
                $table->integer('number_of_tables')->default(0)->change();
            });
        } else {
            // If column doesn't exist, create it with default
            Schema::table('clubs', function (Blueprint $table) {
                $table->integer('number_of_tables')->default(0)->after('total_members');
            });
        }
        
        // Also ensure can_create_tournaments has a proper default
        if (Schema::hasColumn('clubs', 'can_create_tournaments')) {
            DB::table('clubs')->whereNull('can_create_tournaments')->update(['can_create_tournaments' => false]);
            
            Schema::table('clubs', function (Blueprint $table) {
                $table->boolean('can_create_tournaments')->default(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is a fix, so we don't need to reverse it
        // The original migration handles the column creation/removal
    }
};