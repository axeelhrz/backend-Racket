<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'league_id',
        'club_code',
        'name',
        'ruc',
        'city',
        'country',
        'province',
        'address',
        'latitude',
        'longitude',
        'google_maps_url',
        'logo_path',
        'status',
        'total_members',
        'average_ranking',
        // Category counts
        'u800_count',
        'u900_count',
        'u901_u1000_count',
        'u1001_u1100_count',
        'u1101_u1200_count',
        'u1201_u1300_count',
        'u1301_u1400_count',
        'u1401_u1500_count',
        'u1501_u1600_count',
        'u1601_u1700_count',
        'u1701_u1800_count',
        'u1801_u1900_count',
        'u1901_u2000_count',
        'u2001_u2100_count',
        'u2101_u2200_count',
        'over_u2200_count',
        // Representative
        'representative_name',
        'representative_phone',
        'representative_email',
        // Administrators
        'admin1_name',
        'admin1_phone',
        'admin1_email',
        'admin2_name',
        'admin2_phone',
        'admin2_email',
        'admin3_name',
        'admin3_phone',
        'admin3_email',
        // Additional fields
        'ranking_history',
        'monthly_stats',
        'description',
        'founded_date',
    ];

    protected $casts = [
        'league_id' => 'integer',
        'status' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_members' => 'integer',
        'average_ranking' => 'decimal:2',
        'u800_count' => 'integer',
        'u900_count' => 'integer',
        'u901_u1000_count' => 'integer',
        'u1001_u1100_count' => 'integer',
        'u1101_u1200_count' => 'integer',
        'u1201_u1300_count' => 'integer',
        'u1301_u1400_count' => 'integer',
        'u1401_u1500_count' => 'integer',
        'u1501_u1600_count' => 'integer',
        'u1601_u1700_count' => 'integer',
        'u1701_u1800_count' => 'integer',
        'u1801_u1900_count' => 'integer',
        'u1901_u2000_count' => 'integer',
        'u2001_u2100_count' => 'integer',
        'u2101_u2200_count' => 'integer',
        'over_u2200_count' => 'integer',
        'ranking_history' => 'array',
        'monthly_stats' => 'array',
        'founded_date' => 'date',
    ];

    protected $appends = [
        'full_address',
        'members_count',
        'admin_info',
        'category_distribution',
        'contact_info',
        'location_info'
    ];

    protected $attributes = [
        'total_members' => 0,
        'u800_count' => 0,
        'u900_count' => 0,
        'u901_u1000_count' => 0,
        'u1001_u1100_count' => 0,
        'u1101_u1200_count' => 0,
        'u1201_u1300_count' => 0,
        'u1301_u1400_count' => 0,
        'u1401_u1500_count' => 0,
        'u1501_u1600_count' => 0,
        'u1601_u1700_count' => 0,
        'u1701_u1800_count' => 0,
        'u1801_u1900_count' => 0,
        'u1901_u2000_count' => 0,
        'u2001_u2100_count' => 0,
        'u2101_u2200_count' => 0,
        'over_u2200_count' => 0,
        'status' => 'active',
        'country' => 'Ecuador',
    ];

    /**
     * Boot method to generate club code automatically and set defaults.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->club_code) {
                $model->club_code = self::generateClubCode();
            }
            
            // Ensure critical fields have default values
            if (is_null($model->total_members)) {
                $model->total_members = 0;
            }
            
            // Additional safety checks for other potentially problematic fields
            if (is_null($model->status)) {
                $model->status = 'active';
            }
            if (is_null($model->country)) {
                $model->country = 'Ecuador';
            }
        });

        static::saving(function ($model) {
            // Double-check critical fields before saving
            if (is_null($model->total_members)) {
                $model->total_members = 0;
            }
        });
    }

    /**
     * Generate unique club code.
     */
    private static function generateClubCode(): string
    {
        do {
            $code = 'CLUB' . strtoupper(Str::random(6));
        } while (self::where('club_code', $code)->exists());
        
        return $code;
    }

    /**
     * Create a new club with safe defaults for all required fields.
     */
    public static function createSafely(array $attributes = [])
    {
        // Ensure all critical fields have safe defaults
        $safeAttributes = array_merge([
            'total_members' => 0,
            'status' => 'active',
            'country' => 'Ecuador',
            // Initialize all count fields to 0
            'u800_count' => 0,
            'u900_count' => 0,
            'u901_u1000_count' => 0,
            'u1001_u1100_count' => 0,
            'u1101_u1200_count' => 0,
            'u1201_u1300_count' => 0,
            'u1301_u1400_count' => 0,
            'u1401_u1500_count' => 0,
            'u1501_u1600_count' => 0,
            'u1601_u1700_count' => 0,
            'u1701_u1800_count' => 0,
            'u1801_u1900_count' => 0,
            'u1901_u2000_count' => 0,
            'u2001_u2100_count' => 0,
            'u2101_u2200_count' => 0,
            'over_u2200_count' => 0,
        ], $attributes);

        // Ensure no null values for critical fields
        foreach (['total_members'] as $field) {
            if (is_null($safeAttributes[$field])) {
                $safeAttributes[$field] = 0;
            }
        }

        return static::create($safeAttributes);
    }

    /**
     * Get the user that owns this club.
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
     * Get the league that owns the club.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the members for the club.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get active members for the club.
     */
    public function activeMembers(): HasMany
    {
        return $this->hasMany(Member::class)->where('status', 'active');
    }

    /**
     * Get member users that belong to this club.
     */
    public function memberUsers(): HasMany
    {
        return $this->hasMany(User::class, 'parent_club_id')
                    ->where('role', 'miembro');
    }

    /**
     * Scope a query to only include active clubs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by league.
     */
    public function scopeByLeague($query, $leagueId)
    {
        return $query->where('league_id', $leagueId);
    }

    /**
     * Scope a query to include full club information.
     */
    public function scopeWithFullInfo($query)
    {
        return $query->with([
            'user',
            'league.user',
            'members.user',
            'memberUsers'
        ]);
    }

    /**
     * Get the club's admin information.
     */
    public function getAdminInfoAttribute(): ?array
    {
        // Load user relationship if not already loaded
        if (!$this->relationLoaded('user')) {
            $this->load('user');
        }

        if (!$this->user) {
            return null;
        }

        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'country' => $this->user->country,
            'city' => $this->user->city,
            'address' => $this->user->address,
        ];
    }

    /**
     * Get members count.
     */
    public function getMembersCountAttribute(): int
    {
        return $this->total_members ?: $this->members()->count();
    }

    /**
     * Get the full address including city.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address, 
            $this->city, 
            $this->province, 
            $this->country
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get category distribution.
     */
    public function getCategoryDistributionAttribute(): array
    {
        return [
            'U800' => $this->u800_count,
            'U900' => $this->u900_count,
            'U901-U1000' => $this->u901_u1000_count,
            'U1001-U1100' => $this->u1001_u1100_count,
            'U1101-U1200' => $this->u1101_u1200_count,
            'U1201-U1300' => $this->u1201_u1300_count,
            'U1301-U1400' => $this->u1301_u1400_count,
            'U1401-U1500' => $this->u1401_u1500_count,
            'U1501-U1600' => $this->u1501_u1600_count,
            'U1601-U1700' => $this->u1601_u1700_count,
            'U1701-U1800' => $this->u1701_u1800_count,
            'U1801-U1900' => $this->u1801_u1900_count,
            'U1901-U2000' => $this->u1901_u2000_count,
            'U2001-U2100' => $this->u2001_u2100_count,
            'U2101-U2200' => $this->u2101_u2200_count,
            'Over U2200' => $this->over_u2200_count,
        ];
    }

    /**
     * Get contact information.
     */
    public function getContactInfoAttribute(): array
    {
        return [
            'representative' => [
                'name' => $this->representative_name,
                'phone' => $this->representative_phone,
                'email' => $this->representative_email,
            ],
            'admin1' => [
                'name' => $this->admin1_name,
                'phone' => $this->admin1_phone,
                'email' => $this->admin1_email,
            ],
            'admin2' => [
                'name' => $this->admin2_name,
                'phone' => $this->admin2_phone,
                'email' => $this->admin2_email,
            ],
            'admin3' => [
                'name' => $this->admin3_name,
                'phone' => $this->admin3_phone,
                'email' => $this->admin3_email,
            ],
        ];
    }

    /**
     * Get location information.
     */
    public function getLocationInfoAttribute(): array
    {
        return [
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'google_maps_url' => $this->google_maps_url,
            'full_address' => $this->full_address,
        ];
    }

    /**
     * Calculate and update category counts based on members.
     */
    public function updateCategoryCounts(): void
    {
        $members = $this->members()->with('user')->get();
        
        $counts = [
            'u800_count' => 0,
            'u900_count' => 0,
            'u901_u1000_count' => 0,
            'u1001_u1100_count' => 0,
            'u1101_u1200_count' => 0,
            'u1201_u1300_count' => 0,
            'u1301_u1400_count' => 0,
            'u1401_u1500_count' => 0,
            'u1501_u1600_count' => 0,
            'u1601_u1700_count' => 0,
            'u1701_u1800_count' => 0,
            'u1801_u1900_count' => 0,
            'u1901_u2000_count' => 0,
            'u2001_u2100_count' => 0,
            'u2101_u2200_count' => 0,
            'over_u2200_count' => 0,
        ];

        $totalRanking = 0;
        $membersWithRanking = 0;

        foreach ($members as $member) {
            $ranking = $member->current_ranking ?? $member->initial_ranking ?? 0;
            
            if ($ranking > 0) {
                $totalRanking += $ranking;
                $membersWithRanking++;
            }

            // Categorize by ranking
            if ($ranking < 800) {
                $counts['u800_count']++;
            } elseif ($ranking < 900) {
                $counts['u900_count']++;
            } elseif ($ranking <= 1000) {
                $counts['u901_u1000_count']++;
            } elseif ($ranking <= 1100) {
                $counts['u1001_u1100_count']++;
            } elseif ($ranking <= 1200) {
                $counts['u1101_u1200_count']++;
            } elseif ($ranking <= 1300) {
                $counts['u1201_u1300_count']++;
            } elseif ($ranking <= 1400) {
                $counts['u1301_u1400_count']++;
            } elseif ($ranking <= 1500) {
                $counts['u1401_u1500_count']++;
            } elseif ($ranking <= 1600) {
                $counts['u1501_u1600_count']++;
            } elseif ($ranking <= 1700) {
                $counts['u1601_u1700_count']++;
            } elseif ($ranking <= 1800) {
                $counts['u1701_u1800_count']++;
            } elseif ($ranking <= 1900) {
                $counts['u1801_u1900_count']++;
            } elseif ($ranking <= 2000) {
                $counts['u1901_u2000_count']++;
            } elseif ($ranking <= 2100) {
                $counts['u2001_u2100_count']++;
            } elseif ($ranking <= 2200) {
                $counts['u2101_u2200_count']++;
            } else {
                $counts['over_u2200_count']++;
            }
        }

        // Calculate average ranking
        $averageRanking = $membersWithRanking > 0 ? $totalRanking / $membersWithRanking : null;

        // Update the club
        $this->update(array_merge($counts, [
            'total_members' => $members->count(),
            'average_ranking' => $averageRanking,
        ]));
    }

    /**
     * Add ranking history entry.
     */
    public function addRankingHistory(float $averageRanking, ?string $period = null): void
    {
        $history = $this->ranking_history ?? [];
        $period = $period ?? now()->format('Y-m');
        
        $history[$period] = $averageRanking;
        
        // Keep only last 12 months
        if (count($history) > 12) {
            $history = array_slice($history, -12, 12, true);
        }
        
        $this->update(['ranking_history' => $history]);
    }

    /**
     * Get ranking trend.
     */
    public function getRankingTrend(): array
    {
        $history = $this->ranking_history ?? [];
        
        if (count($history) < 2) {
            return ['trend' => 'stable', 'change' => 0];
        }
        
        $values = array_values($history);
        $current = end($values);
        $previous = prev($values);
        
        $change = $current - $previous;
        $trend = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable');
        
        return ['trend' => $trend, 'change' => round($change, 2)];
    }
}