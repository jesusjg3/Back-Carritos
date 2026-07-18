<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'driver_id',
        'state_id',
        'origin_lat',
        'origin_lng',
        'origin_address',
        'destination_lat',
        'destination_lng',
        'destination_address',
        'distance',
        'passengers_count',
        'request_attempt',
    ];

    public function passengers()
    {
        return $this->belongsToMany(User::class, 'trip_passengers', 'trip_id', 'passenger_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function tripPassengers()
    {
        return $this->hasMany(TripPassenger::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function positions()
    {
        return $this->hasMany(TripPosition::class);
    }

    public function ratings()
    {
        return $this->hasMany(TripRating::class);
    }
}



