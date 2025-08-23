<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'message',
        'status',
        'type',
        'metadata',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the sender entity (League, Club, etc.)
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the receiver entity (Club, League, etc.)
     */
    public function receiver(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted invitations
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get rejected invitations
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get invitations by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get sent invitations for a specific entity
     */
    public function scopeSentBy($query, $senderId, $senderType)
    {
        return $query->where('sender_id', $senderId)
                    ->where('sender_type', $senderType);
    }

    /**
     * Scope to get received invitations for a specific entity
     */
    public function scopeReceivedBy($query, $receiverId, $receiverType)
    {
        return $query->where('receiver_id', $receiverId)
                    ->where('receiver_type', $receiverType);
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if invitation is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Accept the invitation
     */
    public function accept(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Handle the business logic based on invitation type
        $this->handleAcceptance();

        return true;
    }

    /**
     * Reject the invitation
     */
    public function reject(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Cancel the invitation (by sender)
     */
    public function cancel(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Handle the business logic when invitation is accepted
     */
    protected function handleAcceptance(): void
    {
        switch ($this->type) {
            case 'league_to_club':
                // When a league invites a club and it's accepted,
                // the club should be affiliated to the league
                if ($this->sender_type === 'App\Models\League' && $this->receiver_type === 'App\Models\Club') {
                    $club = $this->receiver;
                    $league = $this->sender;
                    
                    if ($club && $league) {
                        $club->update(['league_id' => $league->id]);
                    }
                }
                break;

            case 'club_to_league':
                // When a club requests to join a league and it's accepted,
                // the club should be affiliated to the league
                if ($this->sender_type === 'App\Models\Club' && $this->receiver_type === 'App\Models\League') {
                    $club = $this->sender;
                    $league = $this->receiver;
                    
                    if ($club && $league) {
                        $club->update(['league_id' => $league->id]);
                    }
                }
                break;

            // Add more cases as needed for other invitation types
        }
    }

    /**
     * Get formatted sender name
     */
    public function getSenderNameAttribute(): string
    {
        return $this->sender ? $this->sender->name : 'Unknown';
    }

    /**
     * Get formatted receiver name
     */
    public function getReceiverNameAttribute(): string
    {
        return $this->receiver ? $this->receiver->name : 'Unknown';
    }
}