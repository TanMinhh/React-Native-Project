<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password','role','notifications_enabled','theme','manager_id','team_id'
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
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

    /**
     * Get the manager of this user (direct manager)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get all users managed by this user (for backwards compatibility)
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Get the team this user belongs to
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the team managed by this user (if they are a manager)
     */
    public function managedTeam(): HasMany
    {
        return $this->hasMany(Team::class, 'manager_id');
    }

    /**
     * Get team members through team (for managers)
     */
    public function getTeamSalesMembers()
    {
        if ($this->isOwner()) {
            // Get all staff in the same team
            return User::where('team_id', $this->team_id)
                ->where('role', self::STAFF)
                ->get();
        }
        return collect();
    }
}
