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
        // First, let's check if the tournaments table exists and has the basic structure
        if (!Schema::hasTable('tournaments')) {
            Schema::create('tournaments', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        // Now add all required columns systematically
        Schema::table('tournaments', function (Blueprint $table) {
            // Basic tournament information
            if (!Schema::hasColumn('tournaments', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('tournaments', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tournaments', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('description');
            }
            if (!Schema::hasColumn('tournaments', 'tournament_type')) {
                $table->string('tournament_type')->default('individual')->after('code');
            }
            if (!Schema::hasColumn('tournaments', 'tournament_format')) {
                $table->string('tournament_format')->default('single_elimination')->after('tournament_type');
            }

            // Dates
            if (!Schema::hasColumn('tournaments', 'start_date')) {
                $table->date('start_date')->after('tournament_format');
            }
            if (!Schema::hasColumn('tournaments', 'end_date')) {
                $table->date('end_date')->after('start_date');
            }
            if (!Schema::hasColumn('tournaments', 'registration_deadline')) {
                $table->date('registration_deadline')->after('end_date');
            }

            // Participants and fees
            if (!Schema::hasColumn('tournaments', 'max_participants')) {
                $table->integer('max_participants')->default(0)->after('registration_deadline');
            }
            if (!Schema::hasColumn('tournaments', 'current_participants')) {
                $table->integer('current_participants')->default(0)->after('max_participants');
            }
            if (!Schema::hasColumn('tournaments', 'entry_fee')) {
                $table->decimal('entry_fee', 10, 2)->default(0)->after('current_participants');
            }
            if (!Schema::hasColumn('tournaments', 'prize_pool')) {
                $table->decimal('prize_pool', 10, 2)->default(0)->after('entry_fee');
            }

            // Status and progress
            if (!Schema::hasColumn('tournaments', 'status')) {
                $table->enum('status', ['upcoming', 'active', 'completed', 'cancelled', 'draft', 'open', 'in_progress'])->default('upcoming')->after('prize_pool');
            }
            if (!Schema::hasColumn('tournaments', 'matches_played')) {
                $table->integer('matches_played')->default(0)->after('status');
            }
            if (!Schema::hasColumn('tournaments', 'matches_total')) {
                $table->integer('matches_total')->default(0)->after('matches_played');
            }

            // Location fields
            if (!Schema::hasColumn('tournaments', 'location')) {
                $table->string('location')->nullable()->after('matches_total');
            }
            if (!Schema::hasColumn('tournaments', 'country')) {
                $table->string('country')->nullable()->after('location');
            }
            if (!Schema::hasColumn('tournaments', 'province')) {
                $table->string('province')->nullable()->after('country');
            }
            if (!Schema::hasColumn('tournaments', 'city')) {
                $table->string('city')->nullable()->after('province');
            }
            if (!Schema::hasColumn('tournaments', 'club_name')) {
                $table->string('club_name')->nullable()->after('city');
            }
            if (!Schema::hasColumn('tournaments', 'club_address')) {
                $table->text('club_address')->nullable()->after('club_name');
            }
            if (!Schema::hasColumn('tournaments', 'image')) {
                $table->text('image')->nullable()->after('club_address');
            }

            // Foreign keys - handle these carefully
            if (!Schema::hasColumn('tournaments', 'league_id')) {
                $table->foreignId('league_id')->nullable()->constrained('leagues')->onDelete('set null')->after('image');
            }
            if (!Schema::hasColumn('tournaments', 'sport_id')) {
                $table->foreignId('sport_id')->nullable()->constrained('sports')->onDelete('set null')->after('league_id');
            }
            if (!Schema::hasColumn('tournaments', 'club_id')) {
                $table->foreignId('club_id')->nullable()->constrained('clubs')->onDelete('cascade')->after('sport_id');
            }

            // Individual tournament fields
            if (!Schema::hasColumn('tournaments', 'modality')) {
                $table->string('modality')->nullable()->after('club_id'); // singles, doubles
            }
            if (!Schema::hasColumn('tournaments', 'match_type')) {
                $table->string('match_type')->nullable()->after('modality');
            }
            if (!Schema::hasColumn('tournaments', 'seeding_type')) {
                $table->string('seeding_type')->nullable()->after('match_type');
            }
            if (!Schema::hasColumn('tournaments', 'ranking_filter')) {
                $table->boolean('ranking_filter')->default(false)->after('seeding_type');
            }
            if (!Schema::hasColumn('tournaments', 'min_ranking')) {
                $table->string('min_ranking')->nullable()->after('ranking_filter');
            }
            if (!Schema::hasColumn('tournaments', 'max_ranking')) {
                $table->string('max_ranking')->nullable()->after('min_ranking');
            }
            if (!Schema::hasColumn('tournaments', 'age_filter')) {
                $table->boolean('age_filter')->default(false)->after('max_ranking');
            }
            if (!Schema::hasColumn('tournaments', 'min_age')) {
                $table->integer('min_age')->nullable()->after('age_filter');
            }
            if (!Schema::hasColumn('tournaments', 'max_age')) {
                $table->integer('max_age')->nullable()->after('min_age');
            }
            if (!Schema::hasColumn('tournaments', 'gender')) {
                $table->enum('gender', ['male', 'female', 'mixed'])->nullable()->after('max_age');
            }
            if (!Schema::hasColumn('tournaments', 'affects_ranking')) {
                $table->boolean('affects_ranking')->default(true)->after('gender');
            }
            if (!Schema::hasColumn('tournaments', 'draw_lottery')) {
                $table->boolean('draw_lottery')->default(true)->after('affects_ranking');
            }
            if (!Schema::hasColumn('tournaments', 'system_invitation')) {
                $table->boolean('system_invitation')->default(true)->after('draw_lottery');
            }
            if (!Schema::hasColumn('tournaments', 'scheduled_reminder')) {
                $table->boolean('scheduled_reminder')->default(false)->after('system_invitation');
            }
            if (!Schema::hasColumn('tournaments', 'reminder_days')) {
                $table->integer('reminder_days')->nullable()->after('scheduled_reminder');
            }

            // Team tournament fields
            if (!Schema::hasColumn('tournaments', 'team_modality')) {
                $table->string('team_modality')->nullable()->after('reminder_days');
            }
            if (!Schema::hasColumn('tournaments', 'team_match_type')) {
                $table->string('team_match_type')->nullable()->after('team_modality');
            }
            if (!Schema::hasColumn('tournaments', 'team_elimination_type')) {
                $table->string('team_elimination_type')->nullable()->after('team_match_type');
            }
            if (!Schema::hasColumn('tournaments', 'players_per_team')) {
                $table->integer('players_per_team')->nullable()->after('team_elimination_type');
            }
            if (!Schema::hasColumn('tournaments', 'max_ranking_between_players')) {
                $table->integer('max_ranking_between_players')->nullable()->after('players_per_team');
            }
            if (!Schema::hasColumn('tournaments', 'categories')) {
                $table->json('categories')->nullable()->after('max_ranking_between_players');
            }
            if (!Schema::hasColumn('tournaments', 'number_of_teams')) {
                $table->integer('number_of_teams')->nullable()->after('categories');
            }
            if (!Schema::hasColumn('tournaments', 'team_seeding_type')) {
                $table->string('team_seeding_type')->nullable()->after('number_of_teams');
            }
            if (!Schema::hasColumn('tournaments', 'team_ranking_filter')) {
                $table->boolean('team_ranking_filter')->default(false)->after('team_seeding_type');
            }
            if (!Schema::hasColumn('tournaments', 'team_min_ranking')) {
                $table->string('team_min_ranking')->nullable()->after('team_ranking_filter');
            }
            if (!Schema::hasColumn('tournaments', 'team_max_ranking')) {
                $table->string('team_max_ranking')->nullable()->after('team_min_ranking');
            }
            if (!Schema::hasColumn('tournaments', 'team_age_filter')) {
                $table->boolean('team_age_filter')->default(false)->after('team_max_ranking');
            }
            if (!Schema::hasColumn('tournaments', 'team_min_age')) {
                $table->integer('team_min_age')->nullable()->after('team_age_filter');
            }
            if (!Schema::hasColumn('tournaments', 'team_max_age')) {
                $table->integer('team_max_age')->nullable()->after('team_min_age');
            }
            if (!Schema::hasColumn('tournaments', 'team_gender')) {
                $table->enum('team_gender', ['male', 'female', 'mixed'])->nullable()->after('team_max_age');
            }
            if (!Schema::hasColumn('tournaments', 'team_affects_ranking')) {
                $table->boolean('team_affects_ranking')->default(true)->after('team_gender');
            }
            if (!Schema::hasColumn('tournaments', 'team_draw_lottery')) {
                $table->boolean('team_draw_lottery')->default(true)->after('team_affects_ranking');
            }
            if (!Schema::hasColumn('tournaments', 'team_system_invitation')) {
                $table->boolean('team_system_invitation')->default(true)->after('team_draw_lottery');
            }
            if (!Schema::hasColumn('tournaments', 'team_scheduled_reminder')) {
                $table->boolean('team_scheduled_reminder')->default(false)->after('team_system_invitation');
            }
            if (!Schema::hasColumn('tournaments', 'team_reminder_days')) {
                $table->integer('team_reminder_days')->nullable()->after('team_scheduled_reminder');
            }
            if (!Schema::hasColumn('tournaments', 'team_size')) {
                $table->integer('team_size')->nullable()->after('team_reminder_days');
            }
            if (!Schema::hasColumn('tournaments', 'gender_restriction')) {
                $table->enum('gender_restriction', ['male', 'female', 'mixed'])->nullable()->after('team_size');
            }
            if (!Schema::hasColumn('tournaments', 'skill_level')) {
                $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'professional'])->nullable()->after('gender_restriction');
            }

            // Prize fields
            if (!Schema::hasColumn('tournaments', 'first_prize')) {
                $table->string('first_prize', 500)->nullable()->after('skill_level');
            }
            if (!Schema::hasColumn('tournaments', 'second_prize')) {
                $table->string('second_prize', 500)->nullable()->after('first_prize');
            }
            if (!Schema::hasColumn('tournaments', 'third_prize')) {
                $table->string('third_prize', 500)->nullable()->after('second_prize');
            }
            if (!Schema::hasColumn('tournaments', 'fourth_prize')) {
                $table->string('fourth_prize', 500)->nullable()->after('third_prize');
            }
            if (!Schema::hasColumn('tournaments', 'fifth_prize')) {
                $table->string('fifth_prize', 500)->nullable()->after('fourth_prize');
            }

            // Contact fields
            if (!Schema::hasColumn('tournaments', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('fifth_prize');
            }
            if (!Schema::hasColumn('tournaments', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('tournaments', 'ball_info')) {
                $table->text('ball_info')->nullable()->after('contact_phone');
            }
            if (!Schema::hasColumn('tournaments', 'contact')) {
                $table->string('contact')->nullable()->after('ball_info');
            }
            if (!Schema::hasColumn('tournaments', 'phone')) {
                $table->string('phone', 50)->nullable()->after('contact');
            }

            // Additional fields
            if (!Schema::hasColumn('tournaments', 'rules')) {
                $table->text('rules')->nullable()->after('phone');
            }
        });

        // Update the status enum to include all possible values
        try {
            DB::statement("ALTER TABLE tournaments MODIFY COLUMN status ENUM('upcoming', 'active', 'completed', 'cancelled', 'draft', 'open', 'in_progress') DEFAULT 'upcoming'");
        } catch (\Exception $e) {
            // If the column doesn't exist or can't be modified, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a comprehensive fix migration, so we won't implement a full rollback
        // as it could break existing data. Instead, we'll just drop the table if needed.
        Schema::dropIfExists('tournaments');
    }
};