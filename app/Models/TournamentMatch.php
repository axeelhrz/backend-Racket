<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'round',
        'match_number',
        'participant1_id',
        'participant2_id',
        'winner_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'score',
        'participant1_score',
        'participant2_score',
        'notes',
        'court_number',
        'referee',
        'match_format',
        'sets_data',
        'duration_minutes',
        'bracket_position',
        'next_match_id',
        'is_bye'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'sets_data' => 'array',
        'duration_minutes' => 'integer',
        'round' => 'integer',
        'match_number' => 'integer',
        'court_number' => 'integer',
        'bracket_position' => 'integer',
        'participant1_score' => 'integer',
        'participant2_score' => 'integer',
        'is_bye' => 'boolean'
    ];

    /**
     * Get the tournament that owns the match.
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get the first participant.
     */
    public function participant1(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant1_id');
    }

    /**
     * Get the second participant.
     */
    public function participant2(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant2_id');
    }

    /**
     * Get the winner participant.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'winner_id');
    }

    /**
     * Get the next match in the bracket.
     */
    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'next_match_id');
    }

    public function previousMatches()
    {
        return $this->hasMany(TournamentMatch::class, 'next_match_id');
    }

    public function getStatusColorAttribute(): string
    {
        switch ($this->status) {
            case 'scheduled':
                return 'info';
            case 'in_progress':
                return 'warning';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'error';
            case 'bye':
                return 'default';
            default:
                return 'default';
        }
    }

    public function getStatusLabelAttribute(): string
    {
        switch ($this->status) {
            case 'scheduled':
                return 'Programado';
            case 'in_progress':
                return 'En Progreso';
            case 'completed':
                return 'Completado';
            case 'cancelled':
                return 'Cancelado';
            case 'bye':
                return 'Bye';
            default:
                return ucfirst($this->status);
        }
    }

    public function isReady(): bool
    {
        return $this->participant1_id && $this->participant2_id && $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->winner_id;
    }

    public function getDisplayNameAttribute(): string
    {
        $roundNames = [
            1 => 'Primera Ronda',
            2 => 'Segunda Ronda',
            3 => 'Tercera Ronda',
            4 => 'Cuartos de Final',
            5 => 'Semifinal',
            6 => 'Final'
        ];

        $roundName = $roundNames[$this->round] ?? "Ronda {$this->round}";
        return "{$roundName} - Partido {$this->match_number}";
    }

    public function getParticipantNamesAttribute(): array
    {
        return [
            'participant1' => $this->participant1?->member?->full_name ?? 'TBD',
            'participant2' => $this->participant2?->member?->full_name ?? 'TBD',
            'winner' => $this->winner?->member?->full_name ?? null
        ];
    }

    public function scopeByTournament($query, $tournamentId)
    {
        return $query->where('tournament_id', $tournamentId);
    }

    public function scopeByRound($query, $round)
    {
        return $query->where('round', $round);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }
}