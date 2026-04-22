<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tabs extends Model
{
    use SoftDeletes;
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



