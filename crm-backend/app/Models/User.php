<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password','role'
    ];

    protected $hidden = [
        'password',
    ];

    const ADMIN = 'admin';
    const OWNER = 'owner';
    const STAFF = 'staff';

    public function isAdmin()
    {
        return $this->role === self::ADMIN;
    }

    public function isOwner(): bool
    {
        return $this->role === self::OWNER;
    }

    public function isStaff(): bool
    {
        return $this->role === self::STAFF;
    }

    public function hasRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
