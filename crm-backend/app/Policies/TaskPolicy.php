<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin() || $task->assigned_to === $user->id) {
            return true;
        }

        // Manager can view tasks of team members
        if ($user->isOwner() && $task->assigned_to) {
            $assignee = User::find($task->assigned_to);
            return $assignee && $assignee->manager_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOwner() || $user->isStaff();
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isAdmin() || $task->assigned_to === $user->id) {
            return true;
        }

        if ($user->isOwner() && $task->assigned_to) {
            $assignee = User::find($task->assigned_to);
            return $assignee && $assignee->manager_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->isAdmin() || $task->assigned_to === $user->id) {
            return true;
        }

        if ($user->isOwner() && $task->assigned_to) {
            $assignee = User::find($task->assigned_to);
            return $assignee && $assignee->manager_id === $user->id;
        }

        return false;
    }
}
