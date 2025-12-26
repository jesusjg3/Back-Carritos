<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripPosition extends Model
{
    protected $fillable = [
        'trip_id',
        'driver_id',
        'lat',
        'lng',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}



