<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->isAdmin() ?? false;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Owner;
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->isAdmin() ?? false;
    }
}
