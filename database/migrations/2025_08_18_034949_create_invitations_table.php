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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            
            // Sender information (who sends the invitation)
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type'); // App\Models\League, App\Models\Club, etc.
            
            // Receiver information (who receives the invitation)
            $table->unsignedBigInteger('receiver_id');
            $table->string('receiver_type'); // App\Models\Club, App\Models\League, etc.
            
            // Invitation details
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->enum('type', ['league_to_club', 'club_to_league', 'club_to_member', 'member_to_club'])->default('league_to_club');
            
            // Additional metadata
            $table->json('metadata')->nullable(); // For storing additional data like invitation context
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['sender_id', 'sender_type']);
            $table->index(['receiver_id', 'receiver_type']);
            $table->index(['status']);
            $table->index(['type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};