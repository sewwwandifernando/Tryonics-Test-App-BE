<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Carbon\Carbon;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable  // Changed from Model to Authenticatable
{
    use HasFactory, HasRoles, HasApiTokens, Notifiable;  // Added HasApiTokens and Notifiable

    protected $guard_name = 'api'; 

    protected $fillable = [
        'name',
        'email',           // Added
        'password',        // Added
        'mobileNumber',
        'address',
        'dateOfBirth',
        'age',
    ];

    // Hide sensitive fields from JSON responses
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Cast attributes
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',  // Automatically hash password (Laravel 10+)
    ];

    // Keep age appended for JSON responses
    protected $appends = ['age'];

    // Virtual age for JSON responses
    public function getAgeAttribute()
    {
        return Carbon::parse($this->dateOfBirth)->age;
    }

    // Automatically update age before saving to DB
    protected static function booted()
    {
        static::saving(function ($user) {
            if ($user->dateOfBirth) {
                $user->age = Carbon::parse($user->dateOfBirth)->age;
            }
        });
    }

    /**
     * Get all posts for this user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}