<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'country',
        'roleable_id',
        'roleable_type',
        // Campos específicos para Liga
        'league_name',
        'province',
        'logo_path',
        // Campos específicos para Club
        'club_name',
        'parent_league_id',
        'city',
        'address',
        // Campos específicos para Miembro
        'full_name',
        'parent_club_id',
        'birth_date',
        'gender',
        'rubber_type',
        'ranking',
        'photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
        ];
    }

    /**
     * Get the owning roleable model (League, Club, or Member).
     */
    public function roleable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent league for club users.
     */
    public function parentLeague(): BelongsTo
    {
        return $this->belongsTo(League::class, 'parent_league_id');
    }

    /**
     * Get the parent club for member users.
     */
    public function parentClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'parent_club_id');
    }

    /**
     * Get the league entity if user is a league admin.
     */
    public function leagueEntity(): HasOne
    {
        return $this->hasOne(League::class, 'user_id');
    }

    /**
     * Get the club entity if user is a club admin.
     */
    public function clubEntity(): HasOne
    {
        return $this->hasOne(Club::class, 'user_id');
    }

    /**
     * Get the member entity if user is a member.
     */
    public function memberEntity(): HasOne
    {
        return $this->hasOne(Member::class, 'user_id');
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is a league admin.
     */
    public function isLeague(): bool
    {
        return $this->role === 'liga';
    }

    /**
     * Check if user is a club admin.
     */
    public function isClub(): bool
    {
        return $this->role === 'club';
    }

    /**
     * Check if user is a member.
     */
    public function isMember(): bool
    {
        return $this->role === 'miembro';
    }

    /**
     * Get role-specific information.
     */
    public function getRoleInfoAttribute(): array
    {
        switch ($this->role) {
            case 'super_admin':
                return [
                    'type' => 'super_admin',
                    'name' => 'Super Administrador',
                    'description' => 'Acceso completo al sistema',
                ];
            case 'liga':
                return [
                    'type' => 'liga',
                    'name' => $this->league_name,
                    'province' => $this->province,
                    'logo_path' => $this->logo_path,
                    'entity' => $this->leagueEntity,
                ];
            case 'club':
                return [
                    'type' => 'club',
                    'name' => $this->club_name,
                    'city' => $this->city,
                    'address' => $this->address,
                    'logo_path' => $this->logo_path,
                    'parent_league' => $this->parentLeague,
                    'entity' => $this->clubEntity,
                ];
            case 'miembro':
                return [
                    'type' => 'miembro',
                    'full_name' => $this->full_name,
                    'birth_date' => $this->birth_date,
                    'gender' => $this->gender,
                    'rubber_type' => $this->rubber_type,
                    'ranking' => $this->ranking,
                    'photo_path' => $this->photo_path,
                    'parent_club' => $this->parentClub,
                    'entity' => $this->memberEntity,
                ];
            default:
                return [];
        }
    }

    /**
     * Scope a query to only include users of a specific role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to include role-specific relationships.
     */
    public function scopeWithRoleInfo($query)
    {
        return $query->with([
            'parentLeague',
            'parentClub',
            'leagueEntity',
            'clubEntity',
            'memberEntity'
        ]);
    }

    /**
     * Create the corresponding entity based on user role.
     */
    public function createRoleEntity(): void
    {
        // Super admin no necesita entidad específica
        if ($this->role === 'super_admin') {
            return;
        }

        switch ($this->role) {
            case 'liga':
                $league = League::create([
                    'user_id' => $this->id,
                    'name' => $this->league_name,
                    'region' => $this->province,
                    'province' => $this->province,
                    'logo_path' => $this->logo_path,
                    'status' => 'active',
                ]);
                $this->update([
                    'roleable_id' => $league->id,
                    'roleable_type' => League::class,
                ]);
                break;

            case 'club':
                $club = Club::create([
                    'user_id' => $this->id,
                    'league_id' => $this->parent_league_id,
                    'name' => $this->club_name,
                    'city' => $this->city,
                    'address' => $this->address,
                    'logo_path' => $this->logo_path,
                    'status' => 'active',
                ]);
                $this->update([
                    'roleable_id' => $club->id,
                    'roleable_type' => Club::class,
                ]);
                break;

            case 'miembro':
                $member = Member::create([
                    'user_id' => $this->id,
                    'club_id' => $this->parent_club_id,
                    'first_name' => explode(' ', $this->full_name)[0] ?? '',
                    'last_name' => implode(' ', array_slice(explode(' ', $this->full_name), 1)) ?: '',
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'birthdate' => $this->birth_date,
                    'gender' => $this->gender === 'masculino' ? 'male' : 'female',
                    'rubber_type' => $this->rubber_type,
                    'ranking' => $this->ranking,
                    'photo_path' => $this->photo_path,
                    'status' => 'active',
                ]);
                $this->update([
                    'roleable_id' => $member->id,
                    'roleable_type' => Member::class,
                ]);
                break;
        }
    }
}