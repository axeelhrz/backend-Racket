<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'region',
        'province',
        'logo_path',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected $with = ['user'];

    /**
     * Get the user that owns this league.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user as a polymorphic relation.
     */
    public function userRole(): MorphOne
    {
        return $this->morphOne(User::class, 'roleable');
    }

    /**
     * Get the clubs for the league.
     */
    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    /**
     * Get active clubs for the league.
     */
    public function activeClubs(): HasMany
    {
        return $this->hasMany(Club::class)->where('status', 'active');
    }

    /**
     * Get all members through clubs.
     */
    public function members(): HasMany
    {
        return $this->hasManyThrough(Member::class, Club::class);
    }

    /**
     * Get active members through clubs.
     */
    public function activeMembers(): HasMany
    {
        return $this->hasManyThrough(Member::class, Club::class)
                    ->where('members.status', 'active');
    }

    /**
     * Get club users that belong to this league.
     */
    public function clubUsers(): HasMany
    {
        return $this->hasMany(User::class, 'parent_league_id')
                    ->where('role', 'club');
    }

    /**
     * Scope a query to only include active leagues.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to include full league information.
     */
    public function scopeWithFullInfo($query)
    {
        return $query->with([
            'user',
            'clubs.user',
            'clubs.members.user',
            'clubUsers'
        ]);
    }

    /**
     * Get the league's admin information.
     */
    public function getAdminInfoAttribute(): ?array
    {
        if (!$this->user) {
            return null;
        }

        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'country' => $this->user->country,
        ];
    }

    /**
     * Get clubs count.
     */
    public function getClubsCountAttribute(): int
    {
        return $this->clubs()->count();
    }

    /**
     * Get members count.
     */
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }
}