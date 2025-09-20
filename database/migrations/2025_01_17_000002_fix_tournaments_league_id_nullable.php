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
            // Make league_id nullable since not all tournaments need to be associated with a league
            if (Schema::hasColumn('tournaments', 'league_id')) {
                $table->dropForeign(['league_id']);
                $table->dropColumn('league_id');
            }
            
            // Re-add as nullable
            $table->foreignId('league_id')->nullable()->constrained('leagues')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (Schema::hasColumn('tournaments', 'league_id')) {
                $table->dropForeign(['league_id']);
                $table->dropColumn('league_id');
            }
            
            // Re-add as non-nullable (original state)
            $table->foreignId('league_id')->constrained('leagues')->onDelete('cascade');
        });
    }
};