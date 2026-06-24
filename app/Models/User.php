<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_admin',
        'is_active', 'role', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_admin'       => 'boolean',
        'is_active'      => 'boolean',
        'last_login_at'  => 'datetime',
        'email_verified_at' => 'datetime',
        'password'       => 'hashed',
    ];
}
