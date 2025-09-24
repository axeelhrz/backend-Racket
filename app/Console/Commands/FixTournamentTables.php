<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class FixTournamentTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tournament:fix-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix tournament tables in production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking and fixing tournament tables...');

        // Check if tournament_participants table exists
        if (!Schema::hasTable('tournament_participants')) {
            $this->warn('tournament_participants table does not exist. Creating...');
            $this->createTournamentParticipantsTable();
        } else {
            $this->info('tournament_participants table exists.');
            $this->checkTournamentParticipantsColumns();
        }

        // Check if matches table exists
        if (!Schema::hasTable('matches')) {
            $this->warn('matches table does not exist. Creating...');
            $this->createMatchesTable();
        } else {
            $this->info('matches table exists.');
            $this->checkMatchesColumns();
        }

        $this->info('Tournament tables check completed!');
    }

    private function createTournamentParticipantsTable()
    {
        try {
            Schema::create('tournament_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tournament_id');
                $table->unsignedBigInteger('member_id');
                $table->timestamp('registration_date')->useCurrent();
                $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified'])->default('registered');
                $table->integer('seed')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                // Add foreign key constraints if tables exist
                if (Schema::hasTable('tournaments')) {
                    $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade');
                }
                if (Schema::hasTable('members')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                }
                
                // Ensure a member can only participate once per tournament
                $table->unique(['tournament_id', 'member_id']);
                
                // Indexes for better performance
                $table->index(['tournament_id', 'status']);
                $table->index(['member_id', 'status']);
            });
            
            $this->info('✅ tournament_participants table created successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Error creating tournament_participants table: ' . $e->getMessage());
        }
    }

    private function createMatchesTable()
    {
        try {
            Schema::create('matches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tournament_id');
                $table->integer('round');
                $table->integer('match_number');
                $table->unsignedBigInteger('participant1_id')->nullable();
                $table->unsignedBigInteger('participant2_id')->nullable();
                $table->unsignedBigInteger('winner_id')->nullable();
                $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'bye'])->default('scheduled');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('score')->nullable();
                $table->integer('participant1_score')->nullable();
                $table->integer('participant2_score')->nullable();
                $table->text('notes')->nullable();
                $table->integer('court_number')->nullable();
                $table->string('referee')->nullable();
                $table->string('match_format')->nullable();
                $table->json('sets_data')->nullable();
                $table->integer('duration_minutes')->nullable();
                $table->integer('bracket_position')->nullable();
                $table->unsignedBigInteger('next_match_id')->nullable();
                $table->boolean('is_bye')->default(false);
                $table->timestamps();

                // Add foreign key constraints if tables exist
                if (Schema::hasTable('tournaments')) {
                    $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade');
                }
                if (Schema::hasTable('tournament_participants')) {
                    $table->foreign('participant1_id')->references('id')->on('tournament_participants')->onDelete('set null');
                    $table->foreign('participant2_id')->references('id')->on('tournament_participants')->onDelete('set null');
                    $table->foreign('winner_id')->references('id')->on('tournament_participants')->onDelete('set null');
                }
                $table->foreign('next_match_id')->references('id')->on('matches')->onDelete('set null');

                $table->index(['tournament_id', 'round']);
                $table->index(['tournament_id', 'status']);
                $table->index(['scheduled_at']);
            });
            
            $this->info('✅ matches table created successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Error creating matches table: ' . $e->getMessage());
        }
    }

    private function checkTournamentParticipantsColumns()
    {
        $requiredColumns = [
            'id', 'tournament_id', 'member_id', 'registration_date', 
            'status', 'seed', 'notes', 'created_at', 'updated_at'
        ];

        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('tournament_participants', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $this->warn('Missing columns in tournament_participants: ' . implode(', ', $missingColumns));
            $this->addMissingTournamentParticipantsColumns($missingColumns);
        } else {
            $this->info('✅ All required columns exist in tournament_participants table.');
        }
    }

    private function checkMatchesColumns()
    {
        $requiredColumns = [
            'id', 'tournament_id', 'round', 'match_number', 'participant1_id', 
            'participant2_id', 'winner_id', 'status', 'score', 'participant1_score',
            'participant2_score', 'bracket_position', 'next_match_id', 'is_bye'
        ];

        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('matches', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $this->warn('Missing columns in matches: ' . implode(', ', $missingColumns));
            $this->addMissingMatchesColumns($missingColumns);
        } else {
            $this->info('✅ All required columns exist in matches table.');
        }
    }

    private function addMissingTournamentParticipantsColumns($missingColumns)
    {
        try {
            Schema::table('tournament_participants', function (Blueprint $table) use ($missingColumns) {
                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'tournament_id':
                            $table->unsignedBigInteger('tournament_id');
                            break;
                        case 'member_id':
                            $table->unsignedBigInteger('member_id');
                            break;
                        case 'registration_date':
                            $table->timestamp('registration_date')->useCurrent();
                            break;
                        case 'status':
                            $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified'])->default('registered');
                            break;
                        case 'seed':
                            $table->integer('seed')->nullable();
                            break;
                        case 'notes':
                            $table->text('notes')->nullable();
                            break;
                        case 'created_at':
                        case 'updated_at':
                            if (!Schema::hasColumn('tournament_participants', 'created_at') && 
                                !Schema::hasColumn('tournament_participants', 'updated_at')) {
                                $table->timestamps();
                            }
                            break;
                    }
                }
            });
            $this->info('✅ Added missing columns to tournament_participants table.');
        } catch (\Exception $e) {
            $this->error('❌ Error adding columns to tournament_participants: ' . $e->getMessage());
        }
    }

    private function addMissingMatchesColumns($missingColumns)
    {
        try {
            Schema::table('matches', function (Blueprint $table) use ($missingColumns) {
                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'tournament_id':
                            $table->unsignedBigInteger('tournament_id');
                            break;
                        case 'round':
                            $table->integer('round');
                            break;
                        case 'match_number':
                            $table->integer('match_number');
                            break;
                        case 'participant1_id':
                            $table->unsignedBigInteger('participant1_id')->nullable();
                            break;
                        case 'participant2_id':
                            $table->unsignedBigInteger('participant2_id')->nullable();
                            break;
                        case 'winner_id':
                            $table->unsignedBigInteger('winner_id')->nullable();
                            break;
                        case 'status':
                            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'bye'])->default('scheduled');
                            break;
                        case 'score':
                            $table->string('score')->nullable();
                            break;
                        case 'participant1_score':
                            $table->integer('participant1_score')->nullable();
                            break;
                        case 'participant2_score':
                            $table->integer('participant2_score')->nullable();
                            break;
                        case 'bracket_position':
                            $table->integer('bracket_position')->nullable();
                            break;
                        case 'next_match_id':
                            $table->unsignedBigInteger('next_match_id')->nullable();
                            break;
                        case 'is_bye':
                            $table->boolean('is_bye')->default(false);
                            break;
                    }
                }
            });
            $this->info('✅ Added missing columns to matches table.');
        } catch (\Exception $e) {
            $this->error('❌ Error adding columns to matches: ' . $e->getMessage());
        }
    }
}