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
        // Basic required fields
        'name',
        'description',
        'code',
        'tournament_type',
        'start_date',
        'end_date',
        'registration_deadline',
        'max_participants',
        'current_participants',
        'entry_fee',
        'status',
        'tournament_format',
        'club_id',
        'league_id',
        'sport_id',
        
        // Location fields
        'country',
        'province',
        'city',
        'club_name',
        'club_address',
        'location',
        'image',
        
        // Tournament configuration
        'prize_pool',
        'rules',
        'matches_played',
        'matches_total',
        
        // Individual tournament fields
        'modality',
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
        'team_size',
        
        // Prize fields
        'first_prize',
        'second_prize',
        'third_prize',
        'fourth_prize',
        'fifth_prize',
        
        // Contact fields
        'contact',
        'phone',
        'ball_info',
        'contact_name',
        'contact_phone'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'entry_fee' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'current_participants' => 'integer',
        'max_participants' => 'integer',
        'matches_played' => 'integer',
        'matches_total' => 'integer',
        'code' => 'string',
        
        // Boolean fields
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
        
        // Array fields
        'categories' => 'array',
        
        // Integer fields
        'min_age' => 'integer',
        'max_age' => 'integer',
        'players_per_team' => 'integer',
        'max_ranking_between_players' => 'integer',
        'number_of_teams' => 'integer',
        'team_min_age' => 'integer',
        'team_max_age' => 'integer',
        'reminder_days' => 'integer',
        'team_reminder_days' => 'integer',
        'team_size' => 'integer'
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
     * Check if a tournament code already exists.
     */
    public static function codeExists($code, $excludeId = null)
    {
        $query = self::where('code', (string)$code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Generate a unique numeric tournament code.
     */
    public static function generateUniqueCode()
    {
        do {
            // Generate a 6-digit random number as string
            $code = (string)rand(100000, 999999);
        } while (self::codeExists($code));
        
        return $code;
    }

    /**
     * Get the status color for UI display
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'upcoming' => 'info',
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'error',
            default => 'default'
        };
    }

    /**
     * Get the status label for UI display
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'upcoming' => 'Próximo',
            'active' => 'Activo',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            default => ucfirst($this->status)
        };
    }
}