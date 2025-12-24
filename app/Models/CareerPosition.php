<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerPosition extends Model
{
    protected $fillable = [
        'career_id',
        'driver_id',
        'lat',
        'lng',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
