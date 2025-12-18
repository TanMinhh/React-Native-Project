<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = [
        'lead_id','stage','estimated_value',
        'expected_close_date','owner_id'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) return;
        $term = "%$term%";
        $query->whereHas('lead', function($q) use ($term) {
            $q->where('full_name', 'like', $term);
        });
    }
}
