<?php

namespace App\Policies;

use App\Models\FloodReport;
use App\Models\User;

class AuthorityResponsePolicy
{
    public function create(User $user, FloodReport $report): bool
    {
        return $user->isAuthority();
    }
}
