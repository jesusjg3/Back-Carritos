<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicles';

    protected $fillable = [
        'brand',
        'model',
        'plate',
        'color',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function driverProfiles()
    {
        return $this->hasMany(DriverProfile::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_vehicle');
    }
}
