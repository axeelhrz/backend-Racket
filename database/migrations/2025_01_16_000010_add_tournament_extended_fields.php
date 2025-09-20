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
            // Basic tournament fields
            $table->string('code')->nullable()->after('id');
            $table->string('type')->default('individual')->after('code'); // individual or team
            $table->string('country')->default('Argentina')->after('type');
            $table->string('province')->nullable()->after('country');
            $table->string('city')->nullable()->after('province');
            $table->string('club_name')->nullable()->after('city');
            $table->string('club_address')->nullable()->after('club_name');
            $table->foreignId('club_id')->nullable()->constrained('clubs')->onDelete('cascade')->after('club_address');
            $table->text('image')->nullable()->after('club_id');
            
            // Tournament type mapping
            $table->string('tournament_type')->nullable()->after('tournament_format'); // single_elimination, double_elimination, round_robin, swiss, etc.
            
            // Individual tournament fields
            $table->boolean('modality')->default(true)->after('tournament_type'); // true for singles, false for doubles
            $table->string('match_type')->default('best_of_3')->after('modality');
            $table->string('seeding_type')->default('ranking')->after('match_type');
            $table->boolean('ranking_filter')->default(false)->after('seeding_type');
            $table->integer('min_ranking')->nullable()->after('ranking_filter');
            $table->integer('max_ranking')->nullable()->after('min_ranking');
            $table->boolean('age_filter')->default(false)->after('max_ranking');
            $table->integer('min_age')->nullable()->after('age_filter');
            $table->integer('max_age')->nullable()->after('min_age');
            $table->string('gender')->default('mixed')->after('max_age');
            $table->boolean('affects_ranking')->default(true)->after('gender');
            $table->boolean('draw_lottery')->default(true)->after('affects_ranking');
            $table->boolean('system_invitation')->default(true)->after('draw_lottery');
            $table->boolean('scheduled_reminder')->default(false)->after('system_invitation');
            $table->integer('reminder_days')->default(7)->after('scheduled_reminder');
            
            // Team tournament fields
            $table->string('team_modality')->default('singles')->after('reminder_days');
            $table->string('team_match_type')->default('best_2_of_3')->after('team_modality');
            $table->string('team_elimination_type')->default('groups')->after('team_match_type');
            $table->integer('players_per_team')->default(2)->after('team_elimination_type');
            $table->integer('max_ranking_between_players')->default(1000)->after('players_per_team');
            $table->json('categories')->nullable()->after('max_ranking_between_players');
            $table->integer('number_of_teams')->default(8)->after('categories');
            $table->string('team_seeding_type')->default('random')->after('number_of_teams');
            $table->boolean('team_ranking_filter')->default(false)->after('team_seeding_type');
            $table->integer('team_min_ranking')->nullable()->after('team_ranking_filter');
            $table->integer('team_max_ranking')->nullable()->after('team_min_ranking');
            $table->boolean('team_age_filter')->default(false)->after('team_max_ranking');
            $table->integer('team_min_age')->nullable()->after('team_age_filter');
            $table->integer('team_max_age')->nullable()->after('team_min_age');
            $table->string('team_gender')->default('mixed')->after('team_max_age');
            $table->boolean('team_affects_ranking')->default(true)->after('team_gender');
            $table->boolean('team_draw_lottery')->default(true)->after('team_affects_ranking');
            $table->boolean('team_system_invitation')->default(true)->after('team_draw_lottery');
            $table->boolean('team_scheduled_reminder')->default(false)->after('team_system_invitation');
            $table->integer('team_reminder_days')->default(7)->after('team_scheduled_reminder');
            
            // Prize fields
            $table->text('first_prize')->nullable()->after('team_reminder_days');
            $table->text('second_prize')->nullable()->after('first_prize');
            $table->text('third_prize')->nullable()->after('second_prize');
            $table->text('fourth_prize')->nullable()->after('third_prize');
            $table->text('fifth_prize')->nullable()->after('fourth_prize');
            
            // Contact fields
            $table->string('contact')->nullable()->after('fifth_prize');
            $table->string('phone')->nullable()->after('contact');
            $table->text('ball_info')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'type', 'country', 'province', 'city', 'club_name', 'club_address', 'club_id', 'image',
                'tournament_type', 'modality', 'match_type', 'seeding_type', 'ranking_filter', 'min_ranking', 'max_ranking',
                'age_filter', 'min_age', 'max_age', 'gender', 'affects_ranking', 'draw_lottery', 'system_invitation',
                'scheduled_reminder', 'reminder_days', 'team_modality', 'team_match_type', 'team_elimination_type',
                'players_per_team', 'max_ranking_between_players', 'categories', 'number_of_teams', 'team_seeding_type',
                'team_ranking_filter', 'team_min_ranking', 'team_max_ranking', 'team_age_filter', 'team_min_age',
                'team_max_age', 'team_gender', 'team_affects_ranking', 'team_draw_lottery', 'team_system_invitation',
                'team_scheduled_reminder', 'team_reminder_days', 'first_prize', 'second_prize', 'third_prize',
                'fourth_prize', 'fifth_prize', 'contact', 'phone', 'ball_info'
            ]);
        });
    }
};