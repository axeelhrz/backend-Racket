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
        Schema::table('tournament_participants', function (Blueprint $table) {
            // Add columns that don't exist yet
            if (!Schema::hasColumn('tournament_participants', 'member_id')) {
                $table->unsignedBigInteger('member_id')->nullable()->after('tournament_id');
            }
            if (!Schema::hasColumn('tournament_participants', 'user_name')) {
                $table->string('user_name')->nullable()->after('member_id');
            }
            if (!Schema::hasColumn('tournament_participants', 'user_email')) {
                $table->string('user_email')->nullable()->after('user_name');
            }
            if (!Schema::hasColumn('tournament_participants', 'user_phone')) {
                $table->string('user_phone')->nullable()->after('user_email');
            }
            if (!Schema::hasColumn('tournament_participants', 'ranking')) {
                $table->string('ranking')->nullable()->after('user_phone');
            }
            if (!Schema::hasColumn('tournament_participants', 'status')) {
                $table->enum('status', ['pending', 'confirmed', 'rejected', 'waiting_list'])->default('pending')->after('ranking');
            }
            if (!Schema::hasColumn('tournament_participants', 'registration_date')) {
                $table->timestamp('registration_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tournament_participants', 'notes')) {
                $table->text('notes')->nullable()->after('registration_date');
            }
            if (!Schema::hasColumn('tournament_participants', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_participants', function (Blueprint $table) {
            $table->dropColumn([
                'member_id',
                'user_name', 
                'user_email',
                'user_phone',
                'ranking',
                'status',
                'registration_date',
                'notes',
                'custom_fields'
            ]);
        });
    }
};