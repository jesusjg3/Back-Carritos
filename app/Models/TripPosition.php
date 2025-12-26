<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripPosition extends Model
{
    protected $fillable = [
        'Trip_id',
        'driver_id',
        'lat',
        'lng',
    ];

    public function Trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}



