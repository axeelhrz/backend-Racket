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
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->timestamp('registration_date')->useCurrent();
            $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified'])->default('registered');
            $table->integer('seed')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Ensure a member can only participate once per tournament
            $table->unique(['tournament_id', 'member_id']);
            
            // Indexes for better performance
            $table->index(['tournament_id', 'status']);
            $table->index(['member_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_participants');
    }
};