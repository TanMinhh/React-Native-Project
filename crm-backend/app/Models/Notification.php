<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    // Notification types
    public const TYPE_LEAD = 'LEAD';
    public const TYPE_TASK = 'TASK';
    public const TYPE_SYSTEM = 'SYSTEM';
    public const TYPE_TASK_ASSIGNED = 'TASK_ASSIGNED';
    public const TYPE_LEAD_ASSIGNED = 'LEAD_ASSIGNED';
    public const TYPE_NO_FOLLOW_UP = 'NO_FOLLOW_UP';
    public const TYPE_TASK_OVERDUE = 'TASK_OVERDUE';

    protected $fillable = [
        'type', 'content', 'payload', 'is_read', 'user_id'
    ];

    protected $casts = [
        'payload' => 'array',
        'is_read' => 'bool',
    ];

    protected $appends = ['type_label'];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the type label in Vietnamese.
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_LEAD => 'Khách hàng',
            self::TYPE_TASK => 'Công việc',
            self::TYPE_SYSTEM => 'Hệ thống',
            self::TYPE_TASK_ASSIGNED => 'Giao công việc',
            self::TYPE_LEAD_ASSIGNED => 'Giao khách hàng',
            self::TYPE_NO_FOLLOW_UP => 'Cần theo dõi',
            self::TYPE_TASK_OVERDUE => 'Công việc quá hạn',
        ];

        return $labels[$this->type] ?? $this->type;
    }
}
