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
            // Make sport_id nullable since not all tournaments need to be associated with a specific sport
            if (Schema::hasColumn('tournaments', 'sport_id')) {
                $table->dropForeign(['sport_id']);
                $table->dropColumn('sport_id');
            }
            
            // Re-add as nullable
            $table->foreignId('sport_id')->nullable()->constrained('sports')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (Schema::hasColumn('tournaments', 'sport_id')) {
                $table->dropForeign(['sport_id']);
                $table->dropColumn('sport_id');
            }
            
            // Re-add as non-nullable (original state)
            $table->foreignId('sport_id')->constrained('sports')->onDelete('cascade');
        });
    }
};