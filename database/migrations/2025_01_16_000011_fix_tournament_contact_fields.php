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
            // Rename contact fields to match the model expectations
            $table->renameColumn('contact', 'contact_name');
            $table->renameColumn('phone', 'contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // Revert the column names
            $table->renameColumn('contact_name', 'contact');
            $table->renameColumn('contact_phone', 'phone');
        });
    }
};