<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRating extends Model
{
    protected $fillable = [
        'user_id',
        'score',
        'rating_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
