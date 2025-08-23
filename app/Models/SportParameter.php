<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'param_key',
        'param_type',
        'param_value',
    ];

    protected $casts = [
        'sport_id' => 'integer',
        'param_type' => 'string',
    ];

    protected $appends = ['typed_value'];

    /**
     * Get the sport that owns the parameter.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get the parameter value with proper type casting.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->param_type) {
            'number' => (float) $this->param_value,
            'boolean' => filter_var($this->param_value, FILTER_VALIDATE_BOOLEAN),
            default => $this->param_value,
        };
    }

    /**
     * Set the parameter value with proper type validation.
     */
    public function setParamValueAttribute($value)
    {
        $this->attributes['param_value'] = match ($this->param_type) {
            'number' => (string) (float) $value,
            'boolean' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    /**
     * Scope a query to filter by sport.
     */
    public function scopeBySport($query, $sportId)
    {
        return $query->where('sport_id', $sportId);
    }

    /**
     * Scope a query to filter by parameter type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('param_type', $type);
    }
}