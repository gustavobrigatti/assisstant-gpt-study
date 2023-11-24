<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'threadId', 'photo', 'name', 'lastname', 'description', 'email', 'password', 'status', 'office', 'google2fa_secret'
    ];

    protected $hidden = ['password', 'remember_token', 'google2fa_secret'];

    protected $casts = ['email_verified_at' => 'datetime'];
}
