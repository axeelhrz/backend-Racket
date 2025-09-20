<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        // Basic fields
        'name',
        'description',
        'code',
        'tournament_type',
        'country',
        'province',
        'city',
        'club_name',
        'club_address',
        'club_id',
        'image',
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
        'matches_total',
        
        // Individual tournament fields
        'modality',
        'elimination_type',
        'match_type',
        'seeding_type',
        'ranking_filter',
        'min_ranking',
        'max_ranking',
        'age_filter',
        'min_age',
        'max_age',
        'gender',
        'affects_ranking',
        'draw_lottery',
        'system_invitation',
        'scheduled_reminder',
        'reminder_days',
        
        // Team tournament fields
        'team_size',
        'team_modality',
        'team_match_type',
        'team_elimination_type',
        'players_per_team',
        'max_ranking_between_players',
        'categories',
        'number_of_teams',
        'team_seeding_type',
        'team_ranking_filter',
        'team_min_ranking',
        'team_max_ranking',
        'team_age_filter',
        'team_min_age',
        'team_max_age',
        'team_gender',
        'team_affects_ranking',
        'team_draw_lottery',
        'team_system_invitation',
        'team_scheduled_reminder',
        'team_reminder_days',
        'gender_restriction',
        'skill_level',
        
        // Prize fields
        'first_prize',
        'second_prize',
        'third_prize',
        'fourth_prize',
        'fifth_prize',
        
        // Contact fields
        'contact_name',
        'contact_phone',
        'ball_info'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
        'entry_fee' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'current_participants' => 'integer',
        'max_participants' => 'integer',
        'matches_played' => 'integer',
        'matches_total' => 'integer',
        'modality' => 'boolean',
        'ranking_filter' => 'boolean',
        'age_filter' => 'boolean',
        'affects_ranking' => 'boolean',
        'draw_lottery' => 'boolean',
        'system_invitation' => 'boolean',
        'scheduled_reminder' => 'boolean',
        'team_ranking_filter' => 'boolean',
        'team_age_filter' => 'boolean',
        'team_affects_ranking' => 'boolean',
        'team_draw_lottery' => 'boolean',
        'team_system_invitation' => 'boolean',
        'team_scheduled_reminder' => 'boolean',
        'categories' => 'array',
        'min_ranking' => 'integer',
        'max_ranking' => 'integer',
        'min_age' => 'integer',
        'max_age' => 'integer',
        'players_per_team' => 'integer',
        'max_ranking_between_players' => 'integer',
        'number_of_teams' => 'integer',
        'team_min_ranking' => 'integer',
        'team_max_ranking' => 'integer',
        'team_min_age' => 'integer',
        'team_max_age' => 'integer',
        'reminder_days' => 'integer',
        'team_reminder_days' => 'integer'
    ];

    /**
     * Get the league that owns the tournament.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the sport that owns the tournament.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get the club that owns the tournament.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the participants for the tournament.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    /**
     * Scope a query to only include tournaments for a specific league.
     */
    public function scopeForLeague($query, $leagueId)
    {
        return $query->where('league_id', $leagueId);
    }

    /**
     * Scope a query to only include tournaments for a specific club.
     */
    public function scopeForClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    /**
     * Scope a query to only include tournaments with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include tournaments of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'upcoming' => 'blue',
            'active' => 'green',
            'completed' => 'gray',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get the formatted entry fee.
     */
    public function getFormattedEntryFeeAttribute()
    {
        return '$' . number_format($this->entry_fee, 2);
    }

    /**
     * Get the formatted prize pool.
     */
    public function getFormattedPrizePoolAttribute()
    {
        return '$' . number_format($this->prize_pool, 2);
    }

    /**
     * Get the tournament type label.
     */
    public function getTypeLabel()
    {
        return $this->type === 'individual' ? 'Individual' : 'Por Equipos';
    }

    /**
     * Get the modality label for individual tournaments.
     */
    public function getModalityLabel()
    {
        return $this->modality ? 'Singles' : 'Dobles';
    }

    /**
     * Get the elimination type label.
     */
    public function getEliminationTypeLabel()
    {
        if ($this->type === 'team') {
            return match($this->team_elimination_type) {
                'groups' => 'Por Grupos',
                'direct_elimination' => 'Eliminación Directa',
                'round_robin' => 'Todos contra Todos',
                'mixed' => 'Mixto',
                default => $this->team_elimination_type
            };
        }

        return match($this->tournament_type) {
            'single_elimination' => 'Eliminación Simple',
            'double_elimination' => 'Eliminación Doble',
            'round_robin' => 'Todos contra Todos',
            'swiss' => 'Sistema Suizo',
            default => $this->tournament_type
        };
    }
}