<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'member_id',
        'registration_date',
        'status',
        'seed',
        'notes',
        'is_eliminated'
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'seed' => 'integer',
        'is_eliminated' => 'boolean'
    ];

    /**
     * Get the tournament that owns the participant.
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get the member that is participating.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get matches where this participant won.
     */
    public function wonMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'winner_id');
    }

    /**
     * Get matches where this participant played as participant1.
     */
    public function matchesAsParticipant1(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'participant1_id');
    }

    /**
     * Get matches where this participant played as participant2.
     */
    public function matchesAsParticipant2(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'participant2_id');
    }

    /**
     * Get all matches where this participant played.
     */
    public function allMatches()
    {
        return TournamentMatch::where(function ($query) {
            $query->where('participant1_id', $this->id)
                  ->orWhere('participant2_id', $this->id);
        });
    }

    /**
     * Get the status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        switch ($this->status) {
            case 'registered':
                return 'info';
            case 'confirmed':
                return 'success';
            case 'active':
                return 'success';
            case 'withdrawn':
                return 'warning';
            case 'disqualified':
                return 'error';
            case 'eliminated':
                return 'default';
            default:
                return 'default';
        }
    }

    /**
     * Get the status label for UI display
     */
    public function getStatusLabelAttribute(): string
    {
        switch ($this->status) {
            case 'registered':
                return 'Registrado';
            case 'confirmed':
                return 'Confirmado';
            case 'active':
                return 'Activo';
            case 'withdrawn':
                return 'Retirado';
            case 'disqualified':
                return 'Descalificado';
            case 'eliminated':
                return 'Eliminado';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Scope to get only active participants (registered, confirmed, or active)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'confirmed', 'active']);
    }

    /**
     * Scope to get participants by tournament
     */
    public function scopeByTournament($query, $tournamentId)
    {
        return $query->where('tournament_id', $tournamentId);
    }

    /**
     * Get participant's win count for a specific tournament
     */
    public function getWinCountAttribute(): int
    {
        return $this->wonMatches()->where('tournament_id', $this->tournament_id)->count();
    }

    /**
     * Get participant's loss count for a specific tournament
     */
    public function getLossCountAttribute(): int
    {
        return $this->allMatches()
            ->where('tournament_id', $this->tournament_id)
            ->where('status', 'completed')
            ->where('winner_id', '!=', $this->id)
            ->count();
    }

    /**
     * Get participant's match statistics
     */
    public function getMatchStatsAttribute(): array
    {
        $wins = $this->win_count;
        $losses = $this->loss_count;
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'total_matches' => $total,
            'win_percentage' => $total > 0 ? round(($wins / $total) * 100, 2) : 0
        ];
    }
}