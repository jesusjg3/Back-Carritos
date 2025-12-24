<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerRating extends Model
{
    protected $fillable = [
        'career_id',
        'emitter_id',
        'receiver_id',
        'rating',
        'comment',
    ];

    public function career()
    {
        return $this->belongsTo(Career::class);
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
