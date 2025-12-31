<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($lead->assigned_to === $user->id || $lead->owner_id === $user->id) {
            return true;
        }

        // Manager can view leads of their team members
        if ($user->isOwner() && $lead->assignee && $lead->assignee->manager_id === $user->id) {
            return true;
        }

        return false;
    }

    public function update(User $user, Lead $lead)
    {
        return $this->view($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }
}
