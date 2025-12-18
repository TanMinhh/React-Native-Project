<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'NEW';
    public const STATUS_CONTACTING = 'CONTACTING';
    public const STATUS_AGREEMENT = 'AGREEMENT';
    public const STATUS_LOST = 'LOST';

    protected $fillable = [
        'full_name','email','phone_number','company',
        'status','owner_id','unread_by_owner'
    ];

    protected $casts = [
        'unread_by_owner' => 'bool',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
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
              ->orWhere('company', 'like', $term);
        });
    }
}
