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
        Schema::table('matches', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('matches', 'participant1_score')) {
                $table->integer('participant1_score')->nullable()->after('score');
            }
            
            if (!Schema::hasColumn('matches', 'participant2_score')) {
                $table->integer('participant2_score')->nullable()->after('participant1_score');
            }
            
            if (!Schema::hasColumn('matches', 'bracket_position')) {
                $table->integer('bracket_position')->nullable()->after('match_number');
            }
            
            if (!Schema::hasColumn('matches', 'next_match_id')) {
                $table->unsignedBigInteger('next_match_id')->nullable()->after('bracket_position');
                $table->foreign('next_match_id')->references('id')->on('matches')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('matches', 'is_bye')) {
                $table->boolean('is_bye')->default(false)->after('next_match_id');
            }
            
            if (!Schema::hasColumn('matches', 'sets_data')) {
                $table->json('sets_data')->nullable()->after('score');
            }
            
            if (!Schema::hasColumn('matches', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('sets_data');
            }
            
            if (!Schema::hasColumn('matches', 'match_format')) {
                $table->string('match_format')->nullable()->after('referee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('matches', 'next_match_id')) {
                $table->dropForeign(['next_match_id']);
            }
            
            // Drop columns in reverse order
            $columnsToRemove = [
                'match_format',
                'duration_minutes',
                'sets_data',
                'is_bye',
                'next_match_id',
                'bracket_position',
                'participant2_score',
                'participant1_score'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('matches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};