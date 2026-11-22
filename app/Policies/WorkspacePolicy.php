<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class WorkspacePolicy
{
    public function dashboard(User $user): bool
    {
        return $user->tokenCan('dashboard:read');
    }

    public function residents(User $user): bool
    {
        return $user->role->canViewResidents();
    }

    public function bookRide(User $user): bool
    {
        return $user->role === Role::Callcenter;
    }

    public function rides(User $user): bool
    {
        return $user->role === Role::TaxiCompany;
    }

    public function municipality(User $user): bool
    {
        return $user->role === Role::Municipality;
    }
}
