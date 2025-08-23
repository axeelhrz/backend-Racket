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
        'matches_total' => 'integer'
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
     * Scope a query to only include tournaments with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
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
}