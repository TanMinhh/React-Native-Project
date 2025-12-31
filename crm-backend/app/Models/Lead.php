<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_LEAD = 'LEAD';
    public const STATUS_CONTACTED = 'CONTACTED';
    public const STATUS_CARING = 'CARING';
    public const STATUS_NO_NEED = 'NO_NEED';
    public const STATUS_PURCHASED = 'PURCHASED';

    protected $fillable = [
        'full_name','email','phone_number','company','source',
        'status','owner_id','assigned_to','assigned_by','assigned_at','last_activity_at','unread_by_owner','team_id'
    ];

    protected $casts = [
        'unread_by_owner' => 'bool',
        'assigned_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) return;
        $term = "%$term%";
        $query->where(function($q) use ($term) {
            $q->where('full_name', 'like', $term)
              ->orWhere('email', 'like', $term)
              ->orWhere('company', 'like', $term)
              ->orWhere('phone_number', 'like', $term);
        });
    }
}
