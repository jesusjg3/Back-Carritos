<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'is_online',
        'last_update'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_online' => 'boolean',
        'last_update' => 'datetime',
    ];

    /**
     * Relación con el usuario (conductor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para obtener solo conductores online
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true)
                    ->where('last_update', '>=', now()->subMinutes(5));
    }

    /**
     * Scope para buscar conductores cercanos
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusInKm = 5)
    {
        // Usar fórmula de Haversine para calcular distancia
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($longitude)) 
                    + sin(radians($latitude)) 
                    * sin(radians(latitude))))";

        return $query->selectRaw("*, {$haversine} AS distance")
                    ->whereRaw("{$haversine} < ?", [$radiusInKm])
                    ->orderBy('distance');
    }
}
