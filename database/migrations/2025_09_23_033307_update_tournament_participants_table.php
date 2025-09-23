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
                // Only add foreign key if members table exists
                if (Schema::hasTable('members')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                }
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
            
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('tournament_participants', 'status')) {
                $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified', 'pending', 'rejected', 'waiting_list'])
                      ->default('registered');
            }
            
            // Add registration_date if it doesn't exist
            if (!Schema::hasColumn('tournament_participants', 'registration_date')) {
                $table->timestamp('registration_date')->nullable();
            }
            
            // Add notes if it doesn't exist
            if (!Schema::hasColumn('tournament_participants', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            // Add custom_fields if it doesn't exist
            if (!Schema::hasColumn('tournament_participants', 'custom_fields')) {
                $table->json('custom_fields')->nullable();
            }
        });
        
        // Only update status column if it already exists
        if (Schema::hasColumn('tournament_participants', 'status')) {
            $this->updateStatusColumn();
        }
    }
    
    /**
     * Update the status column to include new enum values
     */
    private function updateStatusColumn(): void
    {
        try {
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
                
                // Update the enum to include all values
                \DB::statement("ALTER TABLE tournament_participants MODIFY COLUMN status ENUM('registered', 'confirmed', 'withdrawn', 'disqualified', 'pending', 'rejected', 'waiting_list') DEFAULT 'registered'");
            }
        } catch (\Exception $e) {
            // If updating status fails, log it but don't fail the migration
            \Log::warning('Could not update status column in tournament_participants: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_participants', function (Blueprint $table) {
            // Remove columns we might have added
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
            
            // Check if we should drop member_id (only if original create migration doesn't exist)
            $originalMigrationExists = file_exists(database_path('migrations/2025_08_18_023002_create_tournament_participants_table.php'));
            
            if (!$originalMigrationExists && Schema::hasColumn('tournament_participants', 'member_id')) {
                // Drop foreign key first if it exists
                try {
                    $table->dropForeign(['member_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
                $table->dropColumn('member_id');
            }
            
            // Only drop status if we added it
            if (!$originalMigrationExists && Schema::hasColumn('tournament_participants', 'status')) {
                $table->dropColumn('status');
            }
            
            // Only drop registration_date if we added it
            if (!$originalMigrationExists && Schema::hasColumn('tournament_participants', 'registration_date')) {
                $table->dropColumn('registration_date');
            }
            
            // Only drop notes if we added it
            if (!$originalMigrationExists && Schema::hasColumn('tournament_participants', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
