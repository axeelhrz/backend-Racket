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
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('quick_registrations', 'second_name')) {
                $table->string('second_name')->nullable()->after('first_name');
            }
            
            if (!Schema::hasColumn('quick_registrations', 'second_last_name')) {
                $table->string('second_last_name')->nullable()->after('last_name');
            }
            
            // Add individual indexes if they don't exist
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('quick_registrations');
            
            if (!isset($indexes['quick_registrations_first_name_index'])) {
                $table->index('first_name');
            }
            
            if (!isset($indexes['quick_registrations_last_name_index'])) {
                $table->index('last_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_registrations', function (Blueprint $table) {
            // Drop indexes if they exist
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('quick_registrations');
            
            if (isset($indexes['quick_registrations_first_name_index'])) {
                $table->dropIndex(['first_name']);
            }
            
            if (isset($indexes['quick_registrations_last_name_index'])) {
                $table->dropIndex(['last_name']);
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('quick_registrations', 'second_name')) {
                $table->dropColumn('second_name');
            }
            
            if (Schema::hasColumn('quick_registrations', 'second_last_name')) {
                $table->dropColumn('second_last_name');
            }
        });
    }
};