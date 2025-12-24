<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    const REQUESTED = 1;
    const ACCEPTED = 2;
    const FINISHED = 3;

    protected $fillable = [
        'state_name',
    ];

    public function careers()
    {
        return $this->hasMany(Career::class);
    }
}
