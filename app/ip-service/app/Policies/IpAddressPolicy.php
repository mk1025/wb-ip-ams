<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IpAddress;
use App\Models\User;

class IpAddressPolicy
{
    public function update(User $user, IpAddress $ipAddress): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN || $ipAddress->owner_id === $user->id;
    }

    public function delete(User $user, IpAddress $_ipAddress): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN;
    }
}
