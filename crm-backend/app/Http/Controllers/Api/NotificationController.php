<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return Notification::where('user_id',Auth::id())->get();
    }

    public function show(Notification $notification)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->isAdmin() && $notification->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this notification.');
        }
        return $notification;
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this notification.');
        }

        $notification->update(['is_read' => true]);
        return $notification;
    }

    public function badge()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return ['unread' => $count];
    }
}
