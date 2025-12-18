<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_DONE = 'DONE';
    public const STATUS_OVERDUE = 'OVERDUE';

    protected $fillable = [
        'title','lead_id','opportunity_id',
        'due_date','status','assigned_to'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected $appends = [
        'computed_status',
    ];

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === self::STATUS_DONE) {
            return self::STATUS_DONE;
        }

        if ($this->due_date && $this->due_date->isPast()) {
            return self::STATUS_OVERDUE;
        }

        return $this->status ?? self::STATUS_IN_PROGRESS;
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) return;
        $term = "%$term%";
        $query->where('title', 'like', $term);
    }
}
