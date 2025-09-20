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
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('league_id')->constrained('leagues')->onDelete('cascade');
            $table->foreignId('sport_id')->constrained('sports')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_deadline');
            $table->integer('max_participants')->default(0);
            $table->integer('current_participants')->default(0);
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->decimal('prize_pool', 10, 2)->default(0);
            $table->string('tournament_format')->default('single_elimination');
            $table->string('location')->nullable();
            $table->text('rules')->nullable();
            $table->enum('status', ['upcoming', 'active', 'completed', 'cancelled'])->default('upcoming');
            $table->integer('matches_played')->default(0);
            $table->integer('matches_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
                'league_id',
                'sport_id',
                'start_date',
                'end_date',
                'registration_deadline',
                'max_participants',
                'current_participants',
                'entry_fee',
                'prize_pool',
                'tournament_format',
                'location',
                'rules',
                'status',
                'matches_played',
                'matches_total'
            ]);
        });
    }
};