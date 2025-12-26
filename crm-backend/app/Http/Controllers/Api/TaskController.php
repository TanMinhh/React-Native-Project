<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
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

        $user = Auth::user();
        $data = $request->validated();

        /** @var \App\Models\User $user */
        if (!$user->isAdmin()) {
            $data['assigned_to'] = $user->id;
        } elseif (empty($data['assigned_to'])) {
            $data['assigned_to'] = $user->id;
        }

        $data['status'] = $data['status'] ?? Task::STATUS_IN_PROGRESS;

        $task = Task::create($data);
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
