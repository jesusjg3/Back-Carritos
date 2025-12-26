<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'state_id',
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',
        'distance',
    ];

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
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



