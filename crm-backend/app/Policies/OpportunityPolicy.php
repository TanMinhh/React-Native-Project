<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function view(User $user, Opportunity $opportunity): bool
    {
        if ($user->isStaff()) {
            return false;
        }

        return $user->isAdmin() || $opportunity->owner_id === $user->id;
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        if ($user->isStaff()) {
            return false;
        }

        return $user->isAdmin() || $opportunity->owner_id === $user->id;
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        if ($user->isStaff()) {
            return false;
        }

        return $user->isAdmin() || $opportunity->owner_id === $user->id;
    }
}
