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
        // First, update any existing null values to 0
        DB::table('clubs')->whereNull('number_of_tables')->update(['number_of_tables' => 0]);
        
        // Then modify the column to ensure it's not nullable and has a default value
        Schema::table('clubs', function (Blueprint $table) {
            $table->integer('number_of_tables')->default(0)->nullable(false)->change();
        });
        
        // Also fix other potentially problematic columns
        DB::table('clubs')->whereNull('can_create_tournaments')->update(['can_create_tournaments' => false]);
        DB::table('clubs')->whereNull('total_members')->update(['total_members' => 0]);
        
        Schema::table('clubs', function (Blueprint $table) {
            $table->boolean('can_create_tournaments')->default(false)->nullable(false)->change();
            $table->integer('total_members')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->integer('number_of_tables')->nullable()->change();
            $table->boolean('can_create_tournaments')->nullable()->change();
            $table->integer('total_members')->nullable()->change();
        });
    }
};