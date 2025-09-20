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
        Schema::table('clubs', function (Blueprint $table) {
            if (Schema::hasColumn('clubs', 'can_create_tournaments')) {
                // For SQLite, we need to drop any indexes first
                if (DB::getDriverName() === 'sqlite') {
                    // Check if index exists and drop it
                    try {
                        DB::statement('DROP INDEX IF EXISTS clubs_can_create_tournaments_index');
                    } catch (\Exception $e) {
                        // Index might not exist, continue
                    }
                }
                
                $table->dropColumn('can_create_tournaments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            if (!Schema::hasColumn('clubs', 'can_create_tournaments')) {
                $table->boolean('can_create_tournaments')->default(false)->after('monthly_stats');
            }
        });
    }
};