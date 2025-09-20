<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_type',
        'value',
        'normalized_value',
        'usage_count',
        'first_used_at',
        'last_used_at'
    ];

    protected $casts = [
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Normalizar valor para búsquedas
     */
    public static function normalizeValue(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * Agregar o actualizar un campo personalizado
     */
    public static function addOrUpdate(string $fieldType, string $value): self
    {
        $normalizedValue = self::normalizeValue($value);
        $now = Carbon::now();

        $field = self::where('field_type', $fieldType)
                    ->where('normalized_value', $normalizedValue)
                    ->first();

        if ($field) {
            // Ya existe, incrementar contador y actualizar última vez usado
            $field->increment('usage_count');
            $field->update(['last_used_at' => $now]);
            return $field;
        }

        // No existe, crear nuevo
        return self::create([
            'field_type' => $fieldType,
            'value' => trim($value), // Mantener formato original
            'normalized_value' => $normalizedValue,
            'usage_count' => 1,
            'first_used_at' => $now,
            'last_used_at' => $now
        ]);
    }

    /**
     * Obtener todos los valores para un tipo de campo
     */
    public static function getValuesForType(string $fieldType): array
    {
        return self::where('field_type', $fieldType)
                  ->orderBy('usage_count', 'desc')
                  ->orderBy('value')
                  ->pluck('value')
                  ->toArray();
    }

    /**
     * Buscar valores similares
     */
    public static function findSimilar(string $fieldType, string $value): array
    {
        $normalizedValue = self::normalizeValue($value);
        
        return self::where('field_type', $fieldType)
                  ->where('normalized_value', 'LIKE', "%{$normalizedValue}%")
                  ->orderBy('usage_count', 'desc')
                  ->orderBy('value')
                  ->pluck('value')
                  ->toArray();
    }

    /**
     * Verificar si un valor ya existe
     */
    public static function exists(string $fieldType, string $value): bool
    {
        $normalizedValue = self::normalizeValue($value);
        
        return self::where('field_type', $fieldType)
                  ->where('normalized_value', $normalizedValue)
                  ->exists();
    }
}