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
        Schema::table('clubs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['league_id']);
            
            // Modify the column to be nullable
            $table->unsignedBigInteger('league_id')->nullable()->change();
            
            // Add the foreign key constraint back with nullable support
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Drop the nullable foreign key constraint
            $table->dropForeign(['league_id']);
            
            // Revert to non-nullable
            $table->unsignedBigInteger('league_id')->nullable(false)->change();
            
            // Add back the original constraint
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
        });
    }
};
