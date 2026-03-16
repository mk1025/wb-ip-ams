<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class AuthAuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN;
    }
}
