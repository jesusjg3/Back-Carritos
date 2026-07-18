<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripPassenger extends Pivot
{
    use SoftDeletes;

    protected $table = 'trip_passengers';

    public $incrementing = true;

    protected $fillable = [
        'trip_id',
        'passenger_id',
        'status',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }
}
