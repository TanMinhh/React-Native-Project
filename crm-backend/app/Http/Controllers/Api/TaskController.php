<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Task::with('assignedUser');

        /** @var \App\Models\User $user */
        if ($user->isAdmin()) {
            // no filter
        } elseif ($user->isOwner()) {
            $teamIds = $user->teamMembers()->pluck('id')->toArray();
            $query->where(function($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)
                  ->orWhereIn('assigned_to', $teamIds);
            });
        } else {
            $query->where('assigned_to', $user->id);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($leadId = $request->lead_id) {
            $query->where('lead_id', $leadId);
        }

        if ($when = $request->when) {
            if ($when === 'today') {
                $query->whereDate('due_date', now()->toDateString());
            } elseif ($when === 'overdue') {
                $query->whereDate('due_date', '<', now()->toDateString())
                      ->where('status', '!=', Task::STATUS_DONE);
            } elseif ($when === 'upcoming') {
                $query->whereDate('due_date', '>', now()->toDateString());
            }
        }

        return TaskResource::collection($query->orderBy('due_date')->paginate(10));
    }

    public function store(TaskRequest $request)
    {
        $this->authorize('create', Task::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = $request->validated();

        // Manager can assign to others, staff can only assign to self
        if ($user->isOwner() && !empty($data['assigned_to'])) {
            // Keep the assigned_to from request
        } elseif (!$user->isAdmin()) {
            $data['assigned_to'] = $user->id;
        } elseif (empty($data['assigned_to'])) {
            $data['assigned_to'] = $user->id;
        }

        $data['status'] = $data['status'] ?? Task::STATUS_IN_PROGRESS;
        $data['created_by'] = $user->id;

        // Set team_id from lead if available
        if (!empty($data['lead_id'])) {
            $lead = Lead::find($data['lead_id']);
            if ($lead) {
                $data['team_id'] = $lead->team_id;
            }
        }

        $task = Task::create($data);

        // Create notification for task assignment (if assigned to different user)
        $assignedTo = $data['assigned_to'];
        if ($assignedTo && $assignedTo != $user->id) {
            Notification::create([
                'user_id' => $assignedTo,
                'type' => Notification::TYPE_TASK_ASSIGNED,
                'content' => "Bạn được giao công việc: {$task->title}",
                'payload' => [
                    'task_id' => $task->id,
                    'lead_id' => $data['lead_id'] ?? null,
                    'assigned_by' => $user->id,
                    'assigned_by_name' => $user->name,
                ],
            ]);
        }

        // Create activity for task assignment if lead_id exists
        if (!empty($data['lead_id'])) {
            $assignedUser = $task->assignedUser;
            Activity::create([
                'type' => 'TASK',
                'title' => 'Giao công việc',
                'content' => "Công việc: {$task->title}" . ($assignedUser ? " - Giao cho: {$assignedUser->name}" : ""),
                'lead_id' => $data['lead_id'],
                'user_id' => $user->id,
                'happened_at' => now(),
            ]);

            // Update lead's last_activity_at
            $lead = Lead::find($data['lead_id']);
            if ($lead) {
                $lead->update(['last_activity_at' => now()]);
            }
        }

        return new TaskResource($task);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);
        return new TaskResource($task->load('assignedUser'));
    }

    public function update(TaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);
        $data = $request->validated();
        $user = Auth::user();

        /** @var \App\Models\User $user */
        if (!$user->isAdmin()) {
            $data['assigned_to'] = $user->id;
        }

        $task->update($data);
        return new TaskResource($task);
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->noContent();
    }
}
