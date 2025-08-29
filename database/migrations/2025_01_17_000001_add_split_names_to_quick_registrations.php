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
            // Add new name fields
            $table->string('second_name')->nullable()->after('first_name');
            $table->string('second_last_name')->nullable()->after('last_name');
            
            // Add index for better search performance
            $table->index(['first_name', 'second_name', 'last_name', 'second_last_name'], 'full_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_registrations', function (Blueprint $table) {
            $table->dropIndex('full_name_index');
            $table->dropColumn(['second_name', 'second_last_name']);
        });
    }
};