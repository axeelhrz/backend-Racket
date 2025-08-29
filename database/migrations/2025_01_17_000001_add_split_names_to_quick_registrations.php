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
            
            // Add individual indexes instead of composite index to avoid MySQL key length limit
            $table->index('first_name');
            $table->index('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_registrations', function (Blueprint $table) {
            $table->dropIndex(['first_name']);
            $table->dropIndex(['last_name']);
            $table->dropColumn(['second_name', 'second_last_name']);
        });
    }
};