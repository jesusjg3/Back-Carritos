<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'is_active',
        'latitude',
        'longitude',
        'is_online',
        'last_location_update',
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
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_online' => 'boolean',
            'last_location_update' => 'datetime',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function TripsAsPassengers()
    {
        return $this->hasMany(Trip::class, 'passenger_id');
    }

    public function TripsAsDrivers()
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->rol->rol_name ?? null,
            'is_active' => $this->is_active
        ];
    }

    /**
     * Scope para obtener solo conductores online
     */
    public function scopeOnlineDrivers($query)
    {
        return $query->whereHas('rol', function($q) {
            $q->where('rol_name', 'conductor');
        })
        ->where('is_online', true)
        ->where('is_active', true)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->where('last_location_update', '>=', now()->subMinutes(5));
    }

    /**
     * Scope para buscar conductores cercanos usando Haversine
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusInKm = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($longitude)) 
                    + sin(radians($latitude)) 
                    * sin(radians(latitude))))";

        return $query->selectRaw("users.*, {$haversine} AS distance")
                    ->whereRaw("{$haversine} < ?", [$radiusInKm])
                    ->orderBy('distance');
    }
}



