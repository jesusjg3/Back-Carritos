<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisconnectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'reason',
        'status',
    ];

    /**
     * Get the driver that made the request.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
