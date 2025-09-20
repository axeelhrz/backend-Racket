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
        // Check if the column exists first
        if (Schema::hasColumn('tournaments', 'modality')) {
            // Drop and recreate the column as string
            Schema::table('tournaments', function (Blueprint $table) {
                $table->dropColumn('modality');
            });
        }
        
        // Add the column as string
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('modality')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (Schema::hasColumn('tournaments', 'modality')) {
                $table->dropColumn('modality');
            }
        });
    }
};