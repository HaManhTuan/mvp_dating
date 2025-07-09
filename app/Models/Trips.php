<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TripImages;
use App\Models\User;

class Trips extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(TripImages::class, 'trip_id');
    }
}
