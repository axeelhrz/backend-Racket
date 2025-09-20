<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_id',
        'first_name',
        'last_name',
        'doc_id',
        'email',
        'phone',
        'birthdate',
        'gender',
        'status',
        
        // Location information
        'country',
        'province',
        'city',
        
        // Playing style information
        'dominant_hand',
        'playing_side',
        'playing_style',
        
        // Racket information
        'racket_brand',
        'racket_model',
        'racket_custom_brand',
        'racket_custom_model',
        
        // Drive rubber information
        'drive_rubber_brand',
        'drive_rubber_model',
        'drive_rubber_type',
        'drive_rubber_color',
        'drive_rubber_sponge',
        'drive_rubber_hardness',
        'drive_rubber_custom_brand',
        'drive_rubber_custom_model',
        
        // Backhand rubber information
        'backhand_rubber_brand',
        'backhand_rubber_model',
        'backhand_rubber_type',
        'backhand_rubber_color',
        'backhand_rubber_sponge',
        'backhand_rubber_hardness',
        'backhand_rubber_custom_brand',
        'backhand_rubber_custom_model',
        
        // Additional information
        'notes',
        'ranking_position',
        'ranking_last_updated',
        'photo_path',
        
        // Legacy fields
        'rubber_type',
        'ranking',
    ];

    protected $casts = [
        'club_id' => 'integer',
        'birthdate' => 'date',
        'gender' => 'string',
        'status' => 'string',
        'dominant_hand' => 'string',
        'playing_side' => 'string',
        'playing_style' => 'string',
        'drive_rubber_type' => 'string',
        'drive_rubber_color' => 'string',
        'backhand_rubber_type' => 'string',
        'backhand_rubber_color' => 'string',
        'ranking_position' => 'integer',
        'ranking_last_updated' => 'date',
    ];

    protected $with = ['club'];

    protected $appends = ['full_name', 'age'];

    /**
     * Get the user that owns this member.
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
     * Get the club that owns the member.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the league through the club.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class, 'league_id', 'id')
                    ->through('club');
    }

    /**
     * Get the member's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the member's age.
     */
    public function getAgeAttribute(): ?int
    {
        // Check if birthdate exists and is not null
        if (!isset($this->attributes['birthdate']) || $this->attributes['birthdate'] === null) {
            return null;
        }

        try {
            // If birthdate is already a Carbon instance
            if ($this->birthdate instanceof \Carbon\Carbon) {
                return $this->birthdate->diffInYears(now());
            }

            // If birthdate is a string, try to parse it
            $birthdate = \Carbon\Carbon::parse($this->attributes['birthdate']);
            return $birthdate->diffInYears(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get birth_date attribute for frontend compatibility.
     */
    public function getBirthDateAttribute(): ?string
    {
        // Check if birthdate exists and is not null
        if (!isset($this->attributes['birthdate']) || $this->attributes['birthdate'] === null) {
            return null;
        }

        // If birthdate is already a Carbon instance
        if ($this->birthdate instanceof \Carbon\Carbon) {
            return $this->birthdate->format('Y-m-d');
        }

        // If birthdate is a string, try to parse it
        try {
            return \Carbon\Carbon::parse($this->attributes['birthdate'])->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the member's user information.
     */
    public function getUserInfoAttribute(): ?array
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
            'full_name' => $this->user->full_name,
            'birth_date' => $this->user->birth_date,
            'gender' => $this->user->gender,
            'rubber_type' => $this->user->rubber_type,
            'ranking' => $this->user->ranking,
            'photo_path' => $this->user->photo_path,
        ];
    }

    /**
     * Get the member's equipment summary.
     */
    public function getEquipmentSummaryAttribute(): array
    {
        return [
            'racket' => [
                'brand' => $this->racket_custom_brand ?: $this->racket_brand,
                'model' => $this->racket_custom_model ?: $this->racket_model,
            ],
            'drive_rubber' => [
                'brand' => $this->drive_rubber_custom_brand ?: $this->drive_rubber_brand,
                'model' => $this->drive_rubber_custom_model ?: $this->drive_rubber_model,
                'type' => $this->drive_rubber_type,
                'color' => $this->drive_rubber_color,
                'sponge' => $this->drive_rubber_sponge,
                'hardness' => $this->drive_rubber_hardness,
            ],
            'backhand_rubber' => [
                'brand' => $this->backhand_rubber_custom_brand ?: $this->backhand_rubber_brand,
                'model' => $this->backhand_rubber_custom_model ?: $this->backhand_rubber_model,
                'type' => $this->backhand_rubber_type,
                'color' => $this->backhand_rubber_color,
                'sponge' => $this->backhand_rubber_sponge,
                'hardness' => $this->backhand_rubber_hardness,
            ],
        ];
    }

    /**
     * Get the member's playing style summary.
     */
    public function getPlayingStyleSummaryAttribute(): array
    {
        return [
            'dominant_hand' => $this->dominant_hand,
            'playing_side' => $this->playing_side,
            'playing_style' => $this->playing_style,
        ];
    }

    /**
     * Get the member's location summary.
     */
    public function getLocationSummaryAttribute(): array
    {
        return [
            'country' => $this->country,
            'province' => $this->province,
            'city' => $this->city,
        ];
    }

    /**
     * Scope a query to only include active members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by club.
     */
    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    /**
     * Scope a query to filter by gender.
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeByLocation($query, $country = null, $province = null, $city = null)
    {
        if ($country) {
            $query->where('country', $country);
        }
        if ($province) {
            $query->where('province', $province);
        }
        if ($city) {
            $query->where('city', $city);
        }
        return $query;
    }

    /**
     * Scope a query to filter by playing style.
     */
    public function scopeByPlayingStyle($query, $dominantHand = null, $playingStyle = null)
    {
        if ($dominantHand) {
            $query->where('dominant_hand', $dominantHand);
        }
        if ($playingStyle) {
            $query->where('playing_style', $playingStyle);
        }
        return $query;
    }

    /**
     * Scope a query to include full member information.
     */
    public function scopeWithFullInfo($query)
    {
        return $query->with([
            'user',
            'club.user',
            'club.league.user'
        ]);
    }

    /**
     * Get the member's hierarchy information.
     */
    public function getHierarchyAttribute(): array
    {
        return [
            'member' => [
                'id' => $this->id,
                'name' => $this->full_name,
                'user' => $this->user_info,
            ],
            'club' => [
                'id' => $this->club->id,
                'name' => $this->club->name,
                'user' => $this->club->admin_info ?? null,
            ],
            'league' => [
                'id' => $this->club->league->id ?? null,
                'name' => $this->club->league->name ?? null,
                'user' => $this->club->league->admin_info ?? null,
            ],
        ];
    }
}