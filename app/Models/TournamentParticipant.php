<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'member_id',
        'registration_date',
        'status',
        'seed',
        'notes'
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'seed' => 'integer'
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
     * Get the status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'registered' => 'info',
            'confirmed' => 'success',
            'withdrawn' => 'warning',
            'disqualified' => 'error',
            default => 'default'
        };
    }

    /**
     * Get the status label for UI display
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'registered' => 'Registrado',
            'confirmed' => 'Confirmado',
            'withdrawn' => 'Retirado',
            'disqualified' => 'Descalificado',
            default => ucfirst($this->status)
        };
    }

    /**
     * Scope to get only active participants (registered or confirmed)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'confirmed']);
    }

    /**
     * Scope to get participants by tournament
     */
    public function scopeByTournament($query, $tournamentId)
    {
        return $query->where('tournament_id', $tournamentId);
    }
}