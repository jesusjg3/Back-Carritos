<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $fillable = [
        'rol_name',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tabs()
    {
        return $this->hasMany(Tabs::class);
    }
}



