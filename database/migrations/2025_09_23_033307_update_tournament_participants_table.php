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
            // Add user information columns (member_id already exists from original table)
            if (!Schema::hasColumn('tournament_participants', 'user_name')) {
                $table->string('user_name')->nullable()->after('member_id');
            }
            if (!Schema::hasColumn('tournament_participants', 'user_email')) {
                $table->string('user_email')->nullable()->after('user_name');
            }
            if (!Schema::hasColumn('tournament_participants', 'user_phone')) {
                $table->string('user_phone')->nullable()->after('user_email');
            }
            if (!Schema::hasColumn('tournament_participants', 'ranking')) {
                $table->string('ranking')->nullable()->after('user_phone');
            }
            
            // Update status enum to include new values while preserving existing ones
            if (Schema::hasColumn('tournament_participants', 'status')) {
                // Drop the existing status column and recreate with new enum values
                $table->dropColumn('status');
            }
            
            // Add the updated status column with all possible values
            $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified', 'pending', 'rejected', 'waiting_list'])
                  ->default('registered')
                  ->after('ranking');
            
            // Add new columns
            if (!Schema::hasColumn('tournament_participants', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_participants', function (Blueprint $table) {
            // Remove the columns we added (but keep member_id as it was in original table)
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
            
            // Restore original status enum
            if (Schema::hasColumn('tournament_participants', 'status')) {
                $table->dropColumn('status');
            }
            $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified'])
                  ->default('registered')
                  ->after('registration_date');
        });
    }
};