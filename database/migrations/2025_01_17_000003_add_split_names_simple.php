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
        Schema::table('quick_registrations', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('quick_registrations', 'second_name')) {
                $table->string('second_name')->nullable()->after('first_name');
            }
            
            if (!Schema::hasColumn('quick_registrations', 'second_last_name')) {
                $table->string('second_last_name')->nullable()->after('last_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('quick_registrations', 'second_name')) {
                $table->dropColumn('second_name');
            }
            
            if (Schema::hasColumn('quick_registrations', 'second_last_name')) {
                $table->dropColumn('second_last_name');
            }
        });
    }
};