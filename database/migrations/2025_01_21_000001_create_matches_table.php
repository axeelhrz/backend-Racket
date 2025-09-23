<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->integer('round');
            $table->integer('match_number');
            $table->foreignId('participant1_id')->nullable()->constrained('tournament_participants')->onDelete('set null');
            $table->foreignId('participant2_id')->nullable()->constrained('tournament_participants')->onDelete('set null');
            $table->foreignId('winner_id')->nullable()->constrained('tournament_participants')->onDelete('set null');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'bye'])->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('score')->nullable();
            $table->text('notes')->nullable();
            $table->integer('court_number')->nullable();
            $table->string('referee')->nullable();
            $table->string('match_format')->nullable();
            $table->json('sets_data')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('bracket_position')->nullable();
            $table->foreignId('next_match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->boolean('is_bye')->default(false);
            $table->timestamps();

            $table->index(['tournament_id', 'round']);
            $table->index(['tournament_id', 'status']);
            $table->index(['scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};