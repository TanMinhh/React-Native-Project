<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type','content','payload','is_read','user_id'
    ];

    protected $casts = [
        'payload' => 'array',
        'is_read' => 'bool',
    ];
}
