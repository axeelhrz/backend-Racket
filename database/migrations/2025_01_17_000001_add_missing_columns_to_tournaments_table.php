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
        Schema::table('tournaments', function (Blueprint $table) {
            // Add missing basic columns if they don't exist
            if (!Schema::hasColumn('tournaments', 'code')) {
                $table->string('code', 50)->nullable()->unique();
            }
            
            if (!Schema::hasColumn('tournaments', 'country')) {
                $table->string('country')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'province')) {
                $table->string('province')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'city')) {
                $table->string('city')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'club_name')) {
                $table->string('club_name')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'club_address')) {
                $table->text('club_address')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'image')) {
                $table->text('image')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'club_id')) {
                $table->foreignId('club_id')->nullable()->constrained('clubs')->onDelete('cascade');
            }
            
            // Individual tournament fields
            if (!Schema::hasColumn('tournaments', 'modality')) {
                $table->string('modality')->nullable(); // singles, doubles
            }
            
            if (!Schema::hasColumn('tournaments', 'match_type')) {
                $table->string('match_type')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'seeding_type')) {
                $table->string('seeding_type')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'min_ranking')) {
                $table->string('min_ranking')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'max_ranking')) {
                $table->string('max_ranking')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'reminder_days')) {
                $table->integer('reminder_days')->nullable();
            }
            
            // Team tournament fields
            if (!Schema::hasColumn('tournaments', 'team_size')) {
                $table->integer('team_size')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'min_age')) {
                $table->integer('min_age')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'max_age')) {
                $table->integer('max_age')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'gender_restriction')) {
                $table->enum('gender_restriction', ['male', 'female', 'mixed'])->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'skill_level')) {
                $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'professional'])->nullable();
            }
            
            // Prize fields
            if (!Schema::hasColumn('tournaments', 'first_prize')) {
                $table->string('first_prize', 500)->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'second_prize')) {
                $table->string('second_prize', 500)->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'third_prize')) {
                $table->string('third_prize', 500)->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'fourth_prize')) {
                $table->string('fourth_prize', 500)->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'fifth_prize')) {
                $table->string('fifth_prize', 500)->nullable();
            }
            
            // Contact fields
            if (!Schema::hasColumn('tournaments', 'contact_name')) {
                $table->string('contact_name')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'ball_info')) {
                $table->text('ball_info')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'contact')) {
                $table->string('contact')->nullable();
            }
            
            if (!Schema::hasColumn('tournaments', 'phone')) {
                $table->string('phone', 50)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $columnsToRemove = [
                'code', 'country', 'province', 'city', 'club_name', 'club_address', 'image',
                'modality', 'match_type', 'seeding_type', 'min_ranking', 'max_ranking', 'reminder_days',
                'team_size', 'min_age', 'max_age', 'gender_restriction', 'skill_level',
                'first_prize', 'second_prize', 'third_prize', 'fourth_prize', 'fifth_prize',
                'contact_name', 'contact_phone', 'ball_info', 'contact', 'phone'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('tournaments', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Handle foreign key separately
            if (Schema::hasColumn('tournaments', 'club_id')) {
                $table->dropForeign(['club_id']);
                $table->dropColumn('club_id');
            }
        });
    }
};