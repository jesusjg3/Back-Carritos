<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tabs extends Model
{
    protected $fillable = [
        'rol_id',
        'tab_name',
        'tab_icon',
        'tab_order',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }
}
