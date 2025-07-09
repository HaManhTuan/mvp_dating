<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * @method static \Illuminate\Database\Eloquent\Builder whereEmail(string $email)
 * @method static \Illuminate\Database\Eloquent\Builder whereIsSuperAdmin()
 */
class Admin extends Model implements AuthenticatableContract
{
    use HasApiTokens, Authenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isActivated()
    {
        return true; // Hoặc thêm logic kiểm tra trạng thái kích hoạt của Admin
    }

    public function isOwner()
    {
        return true; // Hoặc thêm logic kiểm tra vai trò Owner của Admin
    }
}
