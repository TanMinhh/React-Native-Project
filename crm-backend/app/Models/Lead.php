<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_LEAD = 'LEAD';
    public const STATUS_CONTACTING = 'CONTACTING';
    public const STATUS_INTERESTED = 'INTERESTED';
    public const STATUS_NO_NEED = 'NO_NEED';
    public const STATUS_PURCHASED = 'PURCHASED';

    protected $fillable = [
        'full_name','email','phone_number','company','source',
        'status','owner_id','assigned_to','assigned_by','assigned_at','last_activity_at','unread_by_owner'
    ];

    protected $casts = [
        'unread_by_owner' => 'bool',
        'assigned_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities()
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
