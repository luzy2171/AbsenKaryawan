<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * Cek apakah user adalah Superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

/**
      * Cek apakah user adalah Admin (termasuk superadmin).
      */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    /**
      * Cek apakah user adalah Approval (termasuk admin dan superadmin).
      */
    public function isApprover(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'approval']);
    }

    /**
      * Cek apakah user adalah Approver murni (termasuk superadmin, tapi tidak admin).
      */
    public function isTrueApprover(): bool
    {
        return in_array($this->role, ['superadmin', 'approval']);
    }

    /**
      * Cek apakah user dapat mengubah data (admin atau superadmin).
      */
    public function canEdit(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
