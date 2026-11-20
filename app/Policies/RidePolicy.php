<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Ride;
use App\Models\User;

class RidePolicy
{
    public function create(User $user): bool
    {
        return $user->role === Role::Callcenter && $user->tokenCan('rides:book');
    }

    public function viewAny(User $user): bool
    {
        return $user->role === Role::TaxiCompany && $user->tokenCan('rides:read');
    }

    public function view(User $user, Ride $ride): bool
    {
        return $this->viewAny($user) && $user->taxi_company_id === $ride->taxi_company_id;
    }
}
