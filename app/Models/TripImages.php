<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripImages extends Model
{
    protected $fillable = [
        'trip_id',
        'image_url',
        'order',
    ];

    public function trip()
    {
        return $this->belongsTo(Trips::class, 'trip_id');
    }
}
