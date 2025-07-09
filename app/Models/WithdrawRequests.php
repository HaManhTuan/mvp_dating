<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawRequests extends Model
{
    protected $fillable = ['user_id', 'credits', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
