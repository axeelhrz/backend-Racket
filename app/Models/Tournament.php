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
        'matches_total',
        'club_id'
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
     * Get the matches for the tournament.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(Match::class);
    }

    /**
     * Get active participants.
     */
    public function activeParticipants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class)->where('status', 'active');
    }

    /**
     * Get completed matches.
     */
    public function completedMatches(): HasMany
    {
        return $this->hasMany(Match::class)->where('status', 'completed');
    }

    /**
     * Get pending matches.
     */
    public function pendingMatches(): HasMany
    {
        return $this->hasMany(Match::class)->where('status', 'pending');
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

    /**
     * Get tournament progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->matches_total == 0) {
            return 0;
        }

        return round(($this->matches_played / $this->matches_total) * 100, 2);
    }

    /**
     * Get tournament statistics.
     */
    public function getTournamentStatsAttribute(): array
    {
        $totalMatches = $this->matches()->count();
        $completedMatches = $this->completedMatches()->count();
        $pendingMatches = $this->pendingMatches()->count();
        $inProgressMatches = $this->matches()->where('status', 'in_progress')->count();

        return [
            'total_participants' => $this->participants()->count(),
            'active_participants' => $this->activeParticipants()->count(),
            'eliminated_participants' => $this->participants()->where('is_eliminated', true)->count(),
            'total_matches' => $totalMatches,
            'completed_matches' => $completedMatches,
            'pending_matches' => $pendingMatches,
            'in_progress_matches' => $inProgressMatches,
            'progress_percentage' => $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 2) : 0,
            'current_round' => $this->getCurrentRound(),
            'total_rounds' => $this->getTotalRounds()
        ];
    }

    /**
     * Get current round number.
     */
    public function getCurrentRound(): int
    {
        $lastCompletedRound = $this->matches()
            ->where('status', 'completed')
            ->max('round_number') ?? 0;

        $nextPendingRound = $this->matches()
            ->where('status', 'pending')
            ->min('round_number') ?? ($lastCompletedRound + 1);

        return max($lastCompletedRound, $nextPendingRound);
    }

    /**
     * Get total number of rounds.
     */
    public function getTotalRounds(): int
    {
        return $this->matches()->max('round_number') ?? 0;
    }

    /**
     * Check if tournament has bracket generated.
     */
    public function getHasBracketAttribute(): bool
    {
        return $this->matches()->count() > 0;
    }

    /**
     * Check if tournament is ready to start.
     */
    public function getIsReadyToStartAttribute(): bool
    {
        return $this->participants()->count() >= 2 && 
               $this->status === 'upcoming' && 
               $this->has_bracket;
    }

    /**
     * Check if tournament is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        if ($this->status === 'completed') {
            return true;
        }

        // Check if all matches are completed
        $totalMatches = $this->matches()->count();
        $completedMatches = $this->completedMatches()->count();

        return $totalMatches > 0 && $totalMatches === $completedMatches;
    }

    /**
     * Get tournament winner.
     */
    public function getWinnerAttribute(): ?TournamentParticipant
    {
        if (!$this->is_completed) {
            return null;
        }

        // For elimination tournaments, winner is the participant who won the final match
        if (in_array($this->tournament_format, ['single_elimination', 'double_elimination'])) {
            $finalMatch = $this->matches()
                ->where('status', 'completed')
                ->orderBy('round_number', 'desc')
                ->first();

            return $finalMatch ? $finalMatch->winner : null;
        }

        // For round robin, winner is participant with most wins
        if ($this->tournament_format === 'round_robin') {
            return $this->participants()
                ->withCount(['wonMatches' => function ($query) {
                    $query->where('tournament_id', $this->id);
                }])
                ->orderBy('won_matches_count', 'desc')
                ->first();
        }

        return null;
    }

    /**
     * Get tournament podium (top 3).
     */
    public function getPodiumAttribute(): array
    {
        if (!$this->is_completed) {
            return [];
        }

        // For elimination tournaments
        if (in_array($this->tournament_format, ['single_elimination', 'double_elimination'])) {
            $podium = [];
            
            // Winner (1st place)
            $winner = $this->winner;
            if ($winner) {
                $podium[1] = $winner;
            }

            // Runner-up (2nd place) - loser of final match
            $finalMatch = $this->matches()
                ->where('status', 'completed')
                ->orderBy('round_number', 'desc')
                ->first();

            if ($finalMatch) {
                $runnerId = $finalMatch->winner_id === $finalMatch->player1_id 
                    ? $finalMatch->player2_id 
                    : $finalMatch->player1_id;
                
                if ($runnerId) {
                    $podium[2] = TournamentParticipant::find($runnerId);
                }
            }

            // Third place - losers of semi-finals
            $semiMatches = $this->matches()
                ->where('status', 'completed')
                ->where('round_number', $this->getTotalRounds() - 1)
                ->get();

            $thirdPlaceCandidates = [];
            foreach ($semiMatches as $match) {
                $loserId = $match->winner_id === $match->player1_id 
                    ? $match->player2_id 
                    : $match->player1_id;
                
                if ($loserId) {
                    $thirdPlaceCandidates[] = TournamentParticipant::find($loserId);
                }
            }

            if (!empty($thirdPlaceCandidates)) {
                $podium[3] = $thirdPlaceCandidates[0]; // Could implement tie-breaker logic here
            }

            return $podium;
        }

        // For round robin tournaments
        if ($this->tournament_format === 'round_robin') {
            $participants = $this->participants()
                ->withCount(['wonMatches' => function ($query) {
                    $query->where('tournament_id', $this->id);
                }])
                ->orderBy('won_matches_count', 'desc')
                ->take(3)
                ->get();

            $podium = [];
            foreach ($participants as $index => $participant) {
                $podium[$index + 1] = $participant;
            }

            return $podium;
        }

        return [];
    }

    /**
     * Start the tournament.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'active'
        ]);
    }

    /**
     * Complete the tournament.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed'
        ]);
    }

    /**
     * Cancel the tournament.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled'
        ]);

        // Cancel all pending matches
        $this->matches()->where('status', 'pending')->update([
            'status' => 'cancelled',
            'notes' => $reason ? "Torneo cancelado: {$reason}" : 'Torneo cancelado'
        ]);
    }

    /**
     * Update tournament statistics.
     */
    public function updateStats(): void
    {
        $stats = $this->tournament_stats;
        
        $this->update([
            'current_participants' => $stats['active_participants'],
            'matches_played' => $stats['completed_matches'],
            'matches_total' => $stats['total_matches']
        ]);

        // Auto-complete tournament if all matches are done
        if ($this->is_completed && $this->status !== 'completed') {
            $this->complete();
        }
    }
}