<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripPassenger extends Pivot
{
    use SoftDeletes;

    const STATUS_REQUESTED = 'requested';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_BOARDED = 'boarded';
    const STATUS_DROPPED_OFF = 'dropped_off';
    const STATUS_CANCELLED = 'cancelled';

    protected $table = 'trip_passengers';

    public $incrementing = true;

    protected $fillable = [
        'trip_id',
        'passenger_id',
        'status',
        'pickup_lat',
        'pickup_lng',
        'pickup_address',
        'passengers_count',
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
