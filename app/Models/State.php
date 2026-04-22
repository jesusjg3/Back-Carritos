<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use SoftDeletes;

    const REQUESTED = 1;
    const ACCEPTED = 2;
    const FINISHED = 3;
    const STARTED = 4;
    const CANCELLED = 5;

    protected $fillable = [
        'state_name',
    ];

    public function Trips()
    {
        return $this->hasMany(Trip::class);
    }
}



