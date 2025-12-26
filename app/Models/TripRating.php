<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripRating extends Model
{
    protected $fillable = [
        'Trip_id',
        'emitter_id',
        'receiver_id',
        'rating',
        'comment',
    ];

    public function Trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function emitter()
    {
        return $this->belongsTo(User::class, 'emitter_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}



