<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Get the parameters for the sport.
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(SportParameter::class);
    }

    /**
     * Get parameters grouped by type.
     */
    public function getParametersByType()
    {
        return $this->parameters->groupBy('param_type');
    }

    /**
     * Get a specific parameter value.
     */
    public function getParameterValue(string $key)
    {
        $parameter = $this->parameters->where('param_key', $key)->first();
        
        if (!$parameter) {
            return null;
        }

        return match ($parameter->param_type) {
            'number' => (float) $parameter->param_value,
            'boolean' => filter_var($parameter->param_value, FILTER_VALIDATE_BOOLEAN),
            default => $parameter->param_value,
        };
    }

    /**
     * Set a parameter value.
     */
    public function setParameter(string $key, string $type, $value): SportParameter
    {
        return $this->parameters()->updateOrCreate(
            ['param_key' => $key],
            [
                'param_type' => $type,
                'param_value' => (string) $value,
            ]
        );
    }
}