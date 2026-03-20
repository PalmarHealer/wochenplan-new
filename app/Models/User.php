<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'display_name',
        'name',
        'email',
        'password',
        'last_login_at',
        'api_token_last_rotated_at',
        'api_token_last_8',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'api_token_last_rotated_at' => 'datetime',
        ];
    }

    public function hasRecentLoginForApi(int $months = 6): bool
    {
        return $this->last_login_at !== null
            && $this->last_login_at->greaterThanOrEqualTo(now()->subMonths($months));
    }

    public function lastLoginExpiredForApi(int $months = 6): bool
    {
        return ! $this->hasRecentLoginForApi($months);
    }
}
