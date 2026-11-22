<?php

namespace App\Policies;

use App\Models\Resident;
use App\Models\User;

class ResidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canViewResidents() && $user->tokenCan('residents:read');
    }

    public function view(User $user, Resident $resident): bool
    {
        return $this->viewAny($user) && $resident->municipality === Resident::MUNICIPALITY_UTRECHT;
    }
}
