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
        // Handle MySQL-specific column modifications
        if (DB::getDriverName() === 'mysql') {
            // Check if we're using MySQL and the column exists
            if (Schema::hasColumn('clubs', 'number_of_tables')) {
                // First, update any null values to 0
                DB::statement('UPDATE clubs SET number_of_tables = 0 WHERE number_of_tables IS NULL');
                
                // Then modify the column to allow null values with a default
                DB::statement('ALTER TABLE clubs MODIFY COLUMN number_of_tables INT DEFAULT 0');
            }
            
            // Also ensure other critical columns have proper defaults
            if (Schema::hasColumn('clubs', 'can_create_tournaments')) {
                DB::statement('UPDATE clubs SET can_create_tournaments = 0 WHERE can_create_tournaments IS NULL');
                DB::statement('ALTER TABLE clubs MODIFY COLUMN can_create_tournaments TINYINT(1) DEFAULT 0');
            }
            
            if (Schema::hasColumn('clubs', 'total_members')) {
                DB::statement('UPDATE clubs SET total_members = 0 WHERE total_members IS NULL');
                DB::statement('ALTER TABLE clubs MODIFY COLUMN total_members INT DEFAULT 0');
            }
        } else {
            // For SQLite and other databases, just update null values
            // SQLite doesn't support MODIFY COLUMN, but the columns should already have proper defaults
            // from the original migration
            
            if (Schema::hasColumn('clubs', 'number_of_tables')) {
                DB::statement('UPDATE clubs SET number_of_tables = 0 WHERE number_of_tables IS NULL');
            }
            
            if (Schema::hasColumn('clubs', 'can_create_tournaments')) {
                DB::statement('UPDATE clubs SET can_create_tournaments = 0 WHERE can_create_tournaments IS NULL');
            }
            
            if (Schema::hasColumn('clubs', 'total_members')) {
                DB::statement('UPDATE clubs SET total_members = 0 WHERE total_members IS NULL');
            }
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