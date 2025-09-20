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
            // Add status field if it doesn't exist
            if (!Schema::hasColumn('quick_registrations', 'status')) {
                $table->enum('status', ['pending', 'contacted', 'approved', 'rejected'])->default('pending')->after('notes');
            }
            
            // Add contacted_at field if it doesn't exist
            if (!Schema::hasColumn('quick_registrations', 'contacted_at')) {
                $table->timestamp('contacted_at')->nullable()->after('status');
            }
            
            // Add approved_at field if it doesn't exist
            if (!Schema::hasColumn('quick_registrations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('contacted_at');
            }
            
            // Add metadata field if it doesn't exist
            if (!Schema::hasColumn('quick_registrations', 'metadata')) {
                $table->json('metadata')->nullable()->after('approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_registrations', function (Blueprint $table) {
            $table->dropColumn(['status', 'contacted_at', 'approved_at', 'metadata']);
        });
    }
};