<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QuickRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_code',
        'first_name',
        'second_name',
        'last_name',
        'second_last_name',
        'doc_id',
        'email',
        'phone',
        'birth_date',
        'gender',
        'country',
        'province',
        'city',
        'league',
        'club_name',
        'club_role',
        'ranking',
        'federation',
        'playing_side',
        'playing_style',
        'racket_brand',
        'racket_model',
        'racket_custom_brand',
        'racket_custom_model',
        'drive_rubber_brand',
        'drive_rubber_model',
        'drive_rubber_type',
        'drive_rubber_color',
        'drive_rubber_sponge',
        'drive_rubber_hardness',
        'drive_rubber_custom_brand',
        'drive_rubber_custom_model',
        'backhand_rubber_brand',
        'backhand_rubber_model',
        'backhand_rubber_type',
        'backhand_rubber_color',
        'backhand_rubber_sponge',
        'backhand_rubber_hardness',
        'backhand_rubber_custom_brand',
        'backhand_rubber_custom_model',
        'notes',
        'photo_path',
        'status',
        'contacted_at',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'contacted_at' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'full_name',
        'age',
        'days_waiting',
        'racket_summary',
        'drive_rubber_summary',
        'backhand_rubber_summary',
        'location_summary',
        'club_summary',
        'status_label',
        'status_color',
        'playing_side_label',
        'playing_style_label'
    ];

    /**
     * Boot method to generate registration code automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->registration_code) {
                $model->registration_code = self::generateRegistrationCode();
            }
        });
    }

    /**
     * Generate sequential registration code.
     */
    private static function generateRegistrationCode(): string
    {
        $lastRegistration = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastRegistration ? ($lastRegistration->id + 1) : 1;
        return 'CensoCodigo' . $nextNumber;
    }

    /**
     * Get the person's full name with support for split names.
     */
    public function getFullNameAttribute(): string
    {
        $names = array_filter([
            $this->first_name,
            $this->second_name,
            $this->last_name,
            $this->second_last_name
        ]);
        
        return implode(' ', $names);
    }

    /**
     * Get the person's age.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->diffInYears(now());
    }

    /**
     * Get days waiting since registration.
     */
    public function getDaysWaitingAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get racket summary.
     */
    public function getRacketSummaryAttribute(): array
    {
        return [
            'brand' => $this->racket_custom_brand ?: $this->racket_brand,
            'model' => $this->racket_custom_model ?: $this->racket_model,
        ];
    }

    /**
     * Get drive rubber summary.
     */
    public function getDriveRubberSummaryAttribute(): array
    {
        return [
            'brand' => $this->drive_rubber_custom_brand ?: $this->drive_rubber_brand,
            'model' => $this->drive_rubber_custom_model ?: $this->drive_rubber_model,
            'type' => $this->drive_rubber_type,
            'color' => $this->drive_rubber_color,
            'sponge' => $this->drive_rubber_sponge,
            'hardness' => $this->drive_rubber_hardness,
        ];
    }

    /**
     * Get backhand rubber summary.
     */
    public function getBackhandRubberSummaryAttribute(): array
    {
        return [
            'brand' => $this->backhand_rubber_custom_brand ?: $this->backhand_rubber_brand,
            'model' => $this->backhand_rubber_custom_model ?: $this->backhand_rubber_model,
            'type' => $this->backhand_rubber_type,
            'color' => $this->backhand_rubber_color,
            'sponge' => $this->backhand_rubber_sponge,
            'hardness' => $this->backhand_rubber_hardness,
        ];
    }

    /**
     * Scope a query to only include pending registrations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include contacted registrations.
     */
    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    /**
     * Scope a query to only include approved registrations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to filter by club.
     */
    public function scopeByClub($query, $clubName)
    {
        return $query->where('club_name', $clubName);
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeByLocation($query, $province = null, $city = null)
    {
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
    public function scopeByPlayingStyle($query, $playingSide = null, $playingStyle = null)
    {
        if ($playingSide) {
            $query->where('playing_side', $playingSide);
        }
        if ($playingStyle) {
            $query->where('playing_style', $playingStyle);
        }
        return $query;
    }

    /**
     * Mark as contacted.
     */
    public function markAsContacted(): bool
    {
        return $this->update([
            'status' => 'contacted',
            'contacted_at' => now(),
        ]);
    }

    /**
     * Mark as approved.
     */
    public function markAsApproved(): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function markAsRejected(): bool
    {
        return $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Get gender label.
     */
    public function getGenderLabelAttribute(): ?string
    {
        if (!$this->gender) {
            return null;
        }

        return match($this->gender) {
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            default => 'No especificado',
        };
    }

    /**
     * Get playing side label.
     */
    public function getPlayingSideLabelAttribute(): ?string
    {
        if (!$this->playing_side) {
            return null;
        }

        return match($this->playing_side) {
            'derecho' => 'Derecho',
            'zurdo' => 'Zurdo',
            default => 'No especificado',
        };
    }

    /**
     * Get playing style label.
     */
    public function getPlayingStyleLabelAttribute(): ?string
    {
        if (!$this->playing_style) {
            return null;
        }

        return match($this->playing_style) {
            'clasico' => 'Clásico',
            'lapicero' => 'Lapicero',
            default => 'No especificado',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'contacted' => 'Contactado',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            default => 'Desconocido',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'contacted' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if registration is recent (within 24 hours).
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->diffInHours(now()) <= 24;
    }

    /**
     * Get location summary.
     */
    public function getLocationSummaryAttribute(): string
    {
        return $this->city . ', ' . $this->province;
    }

    /**
     * Get club and federation summary.
     */
    public function getClubSummaryAttribute(): string
    {
        $summary = $this->club_name ?: 'Sin club';
        if ($this->federation) {
            $summary .= ' - ' . $this->federation;
        }
        return $summary;
    }

    /**
     * Get club role label.
     */
    public function getClubRoleLabelAttribute(): string
    {
        return match($this->club_role) {
            'administrador' => 'Administrador del Club',
            'dueño' => 'Dueño del Club',
            'ninguno' => 'Ninguno',
            default => 'No especificado',
        };
    }

    /**
     * Get club information with role.
     */
    public function getClubInfoAttribute(): array
    {
        return [
            'name' => $this->club_name,
            'role' => $this->club_role,
            'role_label' => $this->club_role_label,
        ];
    }
}