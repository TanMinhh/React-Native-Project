<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        /** @var User $user */
        $user = Auth::user();

        $leadQuery = Lead::query();
        $taskQuery = Task::query();
        $oppQuery = Opportunity::query();
        $activityQuery = Activity::query();
        $notificationQuery = Notification::where('user_id', $user->id);

        if ($user->isAdmin()) {
            // no restrictions
        } elseif ($user->isOwner()) {
            $teamIds = $user->teamMembers()->pluck('id')->toArray();
            $leadQuery->where(function($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('owner_id', $user->id)
                  ->orWhereIn('assigned_to', $teamIds);
            });
            $taskQuery->where(function($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)
                  ->orWhereIn('assigned_to', $teamIds);
            });
            $oppQuery->where(function($q) use ($user, $teamIds) {
                $q->where('owner_id', $user->id)
                  ->orWhereIn('owner_id', $teamIds);
            });
            $activityQuery->whereHas('lead', function($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)
                  ->orWhereIn('assigned_to', $teamIds);
            });
        } else {
            $leadQuery->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhere('owner_id', $user->id);
            });
            $taskQuery->where('assigned_to', $user->id);
            $oppQuery->where('owner_id', $user->id);
            $activityQuery->whereHas('lead', function($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhere('owner_id', $user->id);
            });
        }

        $today = now()->toDateString();
        $staleDate = now()->subDays(7);
        $startDate = now()->subDays(6)->startOfDay();

        return response()->json([
            'leads_total' => $leadQuery->count(),
            'leads_uncontacted' => (clone $leadQuery)->whereNull('last_activity_at')->count(),
            'leads_stale_7d' => (clone $leadQuery)->where(function($q) use ($staleDate) {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $staleDate);
            })->count(),
            'tasks_today' => (clone $taskQuery)->whereDate('due_date', $today)->count(),
            'tasks_overdue' => (clone $taskQuery)->whereDate('due_date', '<', $today)->where('status', '!=', Task::STATUS_DONE)->count(),
            'opportunities_by_stage' => (clone $oppQuery)->selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total','stage'),
            'recent_activities' => (clone $activityQuery)->orderByDesc('happened_at')->limit(10)->get(),
            'notifications_unread' => $notificationQuery->where('is_read', false)->count(),
            'leads_by_day' => (clone $leadQuery)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),
            'tasks_by_day' => (clone $taskQuery)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get(),
            'lead_conversion_rate' => [
                'purchased' => (clone $leadQuery)->where('status', Lead::STATUS_PURCHASED)->count(),
                'total' => (clone $leadQuery)->count(),
            ],
            'team_performance' => Team::with('manager')
                ->get()
                ->map(function ($team) use ($leadQuery, $taskQuery, $oppQuery) {
                    $teamLeadQuery = (clone $leadQuery)->where('team_id', $team->id);
                    $teamTaskQuery = (clone $taskQuery)->where('team_id', $team->id);
                    $teamOppQuery = (clone $oppQuery)->whereHas('lead', function($q) use ($team) {
                        $q->where('team_id', $team->id);
                    });

                    return [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'manager' => $team->manager?->name,
                        'leads' => $teamLeadQuery->count(),
                        'tasks' => $teamTaskQuery->count(),
                        'expected_revenue' => (float) $teamOppQuery->sum('expected_revenue'),
                    ];
                }),
        ]);
    }
}
