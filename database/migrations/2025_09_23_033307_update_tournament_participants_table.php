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
        Schema::table('tournament_participants', function (Blueprint $table) {
            // Add member_id if it doesn't exist (with foreign key constraint)
            if (!Schema::hasColumn('tournament_participants', 'member_id')) {
                $table->unsignedBigInteger('member_id')->nullable();
                $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            }
            
            // Add user information columns
            if (!Schema::hasColumn('tournament_participants', 'user_name')) {
                $table->string('user_name')->nullable();
            }
            if (!Schema::hasColumn('tournament_participants', 'user_email')) {
                $table->string('user_email')->nullable();
            }
            if (!Schema::hasColumn('tournament_participants', 'user_phone')) {
                $table->string('user_phone')->nullable();
            }
            if (!Schema::hasColumn('tournament_participants', 'ranking')) {
                $table->string('ranking')->nullable();
            }
            
            // Add custom_fields if it doesn't exist
            if (!Schema::hasColumn('tournament_participants', 'custom_fields')) {
                $table->json('custom_fields')->nullable();
            }
            
            // Update status enum if needed - but be careful with existing data
            // We'll handle this separately to avoid data loss
        });
        
        // Handle status column update separately to preserve existing data
        $this->updateStatusColumn();
    }
    
    /**
     * Update the status column to include new enum values
     */
    private function updateStatusColumn(): void
    {
        // Check current status column type
        $columns = \DB::select("SHOW COLUMNS FROM tournament_participants WHERE Field = 'status'");
        
        if (!empty($columns)) {
            $currentType = $columns[0]->Type;
            
            // If it's already the enum we want, skip
            if (strpos($currentType, 'pending') !== false && 
                strpos($currentType, 'rejected') !== false && 
                strpos($currentType, 'waiting_list') !== false) {
                return;
            }
        }
        
        // Update the enum to include all values
        \DB::statement("ALTER TABLE tournament_participants MODIFY COLUMN status ENUM('registered', 'confirmed', 'withdrawn', 'disqualified', 'pending', 'rejected', 'waiting_list') DEFAULT 'registered'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_participants', function (Blueprint $table) {
            // Remove columns we added, but be careful about member_id
            $columns_to_drop = [];
            
            if (Schema::hasColumn('tournament_participants', 'user_name')) {
                $columns_to_drop[] = 'user_name';
            }
            if (Schema::hasColumn('tournament_participants', 'user_email')) {
                $columns_to_drop[] = 'user_email';
            }
            if (Schema::hasColumn('tournament_participants', 'user_phone')) {
                $columns_to_drop[] = 'user_phone';
            }
            if (Schema::hasColumn('tournament_participants', 'ranking')) {
                $columns_to_drop[] = 'ranking';
            }
            if (Schema::hasColumn('tournament_participants', 'custom_fields')) {
                $columns_to_drop[] = 'custom_fields';
            }
            
            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
            
            // Only drop member_id if it was added by this migration
            // Check if the original create table migration exists
            $originalMigrationExists = file_exists(database_path('migrations/2025_08_18_023002_create_tournament_participants_table.php'));
            
            if (!$originalMigrationExists && Schema::hasColumn('tournament_participants', 'member_id')) {
                $table->dropForeign(['member_id']);
                $table->dropColumn('member_id');
            }
        });
        
        // Restore original status enum
        \DB::statement("ALTER TABLE tournament_participants MODIFY COLUMN status ENUM('registered', 'confirmed', 'withdrawn', 'disqualified') DEFAULT 'registered'");
    }
};